<?php

namespace App\Modules\AI\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AIImportSession;
use App\Modules\AI\Requests\AIImportUploadRequest;
use App\Modules\AI\Services\AIImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIImportController extends Controller
{
    public function __construct(private AIImportService $service) {}

    public function upload(AIImportUploadRequest $request): JsonResponse
    {
        $user   = $request->user();
        $family = $user->family;

        if (! $family) {
            return response()->json(['message' => 'Usuário sem família associada.', 'status' => 403], 403);
        }

        $session = $this->service->createSession(
            file: $request->file('file'),
            userId: $user->id,
            familySlug: $family->slug,
        );

        return response()->json([
            'data'    => ['session_id' => $session->id, 'status' => $session->status],
            'message' => 'Arquivo recebido. Processando...',
            'status'  => 202,
        ], 202);
    }

    public function status(AIImportSession $session): JsonResponse
    {
        return response()->json([
            'data'   => $this->service->getSession($session),
            'status' => 200,
        ]);
    }

    public function confirm(Request $request, AIImportSession $session): JsonResponse
    {
        $request->validate([
            'items'          => ['required', 'array'],
            'items.*.id'     => ['required', 'integer'],
            'items.*.status' => ['required', 'in:accepted,rejected'],
        ]);

        if ($session->status !== 'completed') {
            return response()->json(['message' => 'A sessão ainda não foi processada.', 'status' => 422], 422);
        }

        $result = $this->service->confirm($session, $request->input('items'));

        return response()->json([
            'data'    => $result,
            'message' => "{$result['accepted']} transação(ões) importada(s).",
            'status'  => 200,
        ]);
    }
}
