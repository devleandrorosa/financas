<?php

namespace App\Modules\AI\Extractors;

use Illuminate\Support\Facades\Http;

class GeminiExtractor implements AIExtractorContract
{
    use BuildsAIPrompt;

    private const MODEL = 'gemini-2.0-flash';

    public function extract(string $fileName, string $fileContent, string $categoryNames): array
    {
        $parts = $this->buildParts($fileName, $fileContent, $categoryNames);

        $response = Http::post(
            'https://generativelanguage.googleapis.com/v1beta/models/' . self::MODEL . ':generateContent?key=' . env('GEMINI_API_KEY'),
            ['contents' => [['parts' => $parts]]]
        );

        if ($response->status() === 429) {
            throw new AIRetryableException('Gemini quota (429): ' . $response->body(), 429);
        }

        if ($response->failed()) {
            throw new \RuntimeException('Gemini API error (' . $response->status() . '): ' . $response->body());
        }

        $text = $response->json('candidates.0.content.parts.0.text') ?? '';

        return $this->parseJson($text, 'Gemini');
    }

    private function buildParts(string $fileName, string $fileContent, string $categoryNames): array
    {
        if (str_ends_with(strtolower($fileName), '.pdf')) {
            return [
                [
                    'inline_data' => [
                        'mime_type' => 'application/pdf',
                        'data'      => base64_encode($fileContent),
                    ],
                ],
                ['text' => $this->buildPrompt($categoryNames)],
            ];
        }

        return [
            ['text' => "Conteúdo do documento:\n\n{$fileContent}\n\n" . $this->buildPrompt($categoryNames)],
        ];
    }
}
