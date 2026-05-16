<?php

namespace App\Modules\AI\Services;

use App\Jobs\ProcessAIImportJob;
use App\Models\AIImportItem;
use App\Models\AIImportSession;
use App\Models\Transaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AIImportService
{
    public function createSession(UploadedFile $file, int $userId, string $familySlug): AIImportSession
    {
        $path = $file->store('ai_imports', 'local');

        $session = AIImportSession::create([
            'user_id'       => $userId,
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'status'        => 'processing',
        ]);

        ProcessAIImportJob::dispatch($session->id, $familySlug);

        return $session;
    }

    public function getSession(AIImportSession $session): AIImportSession
    {
        return $session->load('items.category');
    }

    public function confirm(AIImportSession $session, array $items): array
    {
        // Carrega todos os items da sessão de uma vez (evita N+1)
        $sessionItems = AIImportItem::where('session_id', $session->id)
            ->get()
            ->keyBy('id');

        $accepted = 0;
        $rejected = 0;

        DB::transaction(function () use ($items, $sessionItems, &$accepted, &$rejected) {
            foreach ($items as $itemData) {
                $item = $sessionItems->get($itemData['id']);

                if (! $item) {
                    continue;
                }

                if (($itemData['status'] ?? '') === 'accepted') {
                    $amount = max(1, (int) ($itemData['amount'] ?? $item->amount));
                    $date   = $itemData['date'] ?? $item->date;

                    // Garante data válida antes de inserir
                    if (empty($date)) {
                        $date = now()->toDateString();
                    }

                    Transaction::create([
                        'description'     => trim($itemData['description'] ?? $item->description) ?: $item->description,
                        'amount'          => $amount,
                        'type'            => in_array($itemData['type'] ?? '', ['income', 'expense']) ? $itemData['type'] : $item->type,
                        'date'            => $date,
                        'status'          => 'confirmed',
                        'category_id'     => $itemData['category_id'] ?: null,
                        'bank_account_id' => $itemData['bank_account_id'] ?: null,
                        'credit_card_id'  => $itemData['credit_card_id'] ?: null,
                        'notes'           => null,
                    ]);

                    $item->update(['status' => 'accepted']);
                    $accepted++;
                } else {
                    $item->update(['status' => 'rejected']);
                    $rejected++;
                }
            }
        });

        return ['accepted' => $accepted, 'rejected' => $rejected];
    }
}
