<?php

namespace App\Jobs;

use App\Core\Services\TenantProvisioningService;
use App\Models\AIImportItem;
use App\Models\AIImportSession;
use App\Modules\AI\Extractors\AIExtractorContract;
use App\Modules\AI\Extractors\AIRetryableException;
use App\Modules\AI\Extractors\ClaudeExtractor;
use App\Modules\AI\Extractors\GeminiExtractor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessAIImportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;
    public int $timeout = 90;

    public function backoff(): array
    {
        return [65, 65, 65];
    }

    public function __construct(
        private int $sessionId,
        private string $familySlug,
    ) {}

    public function handle(): void
    {
        $schema = TenantProvisioningService::schemaName($this->familySlug);
        DB::statement("SET search_path = \"{$schema}\", public");

        $session = AIImportSession::find($this->sessionId);

        if (! $session) {
            return;
        }

        try {
            $fileContent = Storage::disk('local')->get($session->file_path);

            if ($fileContent === null) {
                throw new \RuntimeException("Arquivo não encontrado: {$session->file_path}");
            }

            $categories    = DB::select("SELECT name FROM \"{$schema}\".categories WHERE parent_id IS NULL");
            $categoryNames = implode(', ', array_column($categories, 'name'));

            $items = $this->makeExtractor()->extract($session->original_name, $fileContent, $categoryNames);

            $this->persistItems($session, $items);
            $session->update(['status' => 'completed']);
        } catch (AIRetryableException $e) {
            Log::warning('AI import retryable error', [
                'session' => $this->sessionId,
                'attempt' => $this->attempts(),
                'error'   => $e->getMessage(),
            ]);
            throw $e; // Laravel retenta via backoff; sessão permanece 'processing'
        } catch (\Throwable $e) {
            Log::error('AI import failed', ['session' => $this->sessionId, 'error' => $e->getMessage()]);
            $session->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }

    public function failed(\Throwable $e): void
    {
        $schema = TenantProvisioningService::schemaName($this->familySlug);
        DB::statement("SET search_path = \"{$schema}\", public");

        $session = AIImportSession::find($this->sessionId);
        if ($session && $session->status !== 'completed') {
            $msg = ($e instanceof AIRetryableException)
                ? 'Quota da API de IA esgotada após múltiplas tentativas. Tente novamente mais tarde.'
                : $e->getMessage();

            $session->update(['status' => 'failed', 'error_message' => $msg]);
        }
    }

    private function makeExtractor(): AIExtractorContract
    {
        return match (env('AI_PROVIDER', 'claude')) {
            'gemini' => new GeminiExtractor(),
            default  => new ClaudeExtractor(),
        };
    }

    private function persistItems(AIImportSession $session, array $items): void
    {
        foreach ($items as $item) {
            if (empty($item['description']) || empty($item['date'])) {
                continue;
            }

            AIImportItem::create([
                'session_id'  => $session->id,
                'description' => $item['description'],
                'amount'      => max(1, (int) ($item['amount'] ?? 0)),
                'type'        => in_array($item['type'] ?? '', ['income', 'expense']) ? $item['type'] : 'expense',
                'date'        => $item['date'],
                'status'      => 'pending',
            ]);
        }
    }
}
