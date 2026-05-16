<?php

namespace App\Modules\AI\Extractors;

use Anthropic\Client as AnthropicClient;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Messages\Base64PDFSource;
use Anthropic\Messages\DocumentBlockParam;
use Anthropic\RequestOptions;

class ClaudeExtractor implements AIExtractorContract
{
    use BuildsAIPrompt;

    private const MODEL = 'claude-haiku-4-5-20251001';

    public function extract(string $fileName, string $fileContent, string $categoryNames): array
    {
        $content = $this->buildContent($fileName, $fileContent, $categoryNames);

        // maxRetries: 0 — o job Laravel gerencia retentativas com backoff de 65s
        $client = new AnthropicClient(
            apiKey: env('ANTHROPIC_API_KEY'),
            requestOptions: RequestOptions::with(maxRetries: 0),
        );

        try {
            $response = $client->messages->create(
                model: self::MODEL,
                maxTokens: 4096,
                messages: [['role' => 'user', 'content' => $content]],
            );
        } catch (RateLimitException $e) {
            throw new AIRetryableException('Claude quota (429): ' . $e->getMessage(), 429, $e);
        } catch (APIConnectionException $e) {
            throw new AIRetryableException('Claude connection error: ' . $e->getMessage(), 0, $e);
        } catch (AuthenticationException $e) {
            // mantém 'invalid x-api-key' no texto para o frontend identificar corretamente
            throw new \RuntimeException('invalid x-api-key — verifique ANTHROPIC_API_KEY no .env: ' . $e->getMessage(), 401, $e);
        } catch (APIStatusException $e) {
            // 400, 422, 500+ etc. — não retentar
            throw new \RuntimeException('Claude API error (' . $e->getCode() . '): ' . $e->getMessage(), (int) $e->getCode(), $e);
        }

        $text = $response->content[0]->text ?? '';

        return $this->parseJson($text, 'Claude');
    }

    private function buildContent(string $fileName, string $fileContent, string $categoryNames): array|string
    {
        if (str_ends_with(strtolower($fileName), '.pdf')) {
            return [
                DocumentBlockParam::with(
                    source: Base64PDFSource::with(data: base64_encode($fileContent))
                ),
                ['type' => 'text', 'text' => $this->buildPrompt($categoryNames)],
            ];
        }

        return "Conteúdo do documento:\n\n{$fileContent}\n\n" . $this->buildPrompt($categoryNames);
    }
}
