<?php

namespace App\Modules\BankAccount\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Modules\BankAccount\Requests\BankAccountRequest;
use App\Modules\BankAccount\Services\BankAccountService;
use Illuminate\Http\JsonResponse;

class BankAccountController extends Controller
{
    public function __construct(private BankAccountService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->service->list(), 'status' => 200]);
    }

    public function store(BankAccountRequest $request): JsonResponse
    {
        $account = $this->service->create($request->validated());
        return response()->json(['data' => $account, 'message' => 'Conta criada.', 'status' => 201], 201);
    }

    public function update(BankAccountRequest $request, BankAccount $bankAccount): JsonResponse
    {
        $updated = $this->service->update($bankAccount, $request->validated());
        return response()->json(['data' => $updated, 'message' => 'Conta atualizada.', 'status' => 200]);
    }

    public function destroy(BankAccount $bankAccount): JsonResponse
    {
        $this->service->delete($bankAccount);
        return response()->json(['message' => 'Conta removida.', 'status' => 200]);
    }
}
