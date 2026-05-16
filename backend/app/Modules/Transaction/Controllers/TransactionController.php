<?php

namespace App\Modules\Transaction\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Modules\Transaction\Requests\TransactionFilterRequest;
use App\Modules\Transaction\Requests\TransactionRequest;
use App\Modules\Transaction\Services\TransactionService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $service) {}

    public function index(TransactionFilterRequest $request): JsonResponse
    {
        $data = $this->service->list($request->validated());
        return response()->json(['data' => $data, 'status' => 200]);
    }

    public function store(TransactionRequest $request): JsonResponse
    {
        $transaction = $this->service->create($request->validated());
        return response()->json(['data' => $transaction, 'message' => 'Transação criada.', 'status' => 201], 201);
    }

    public function update(TransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $updated = $this->service->update($transaction, $request->validated());
        return response()->json(['data' => $updated, 'message' => 'Transação atualizada.', 'status' => 200]);
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        $this->service->delete($transaction);
        return response()->json(['message' => 'Transação removida.', 'status' => 200]);
    }
}
