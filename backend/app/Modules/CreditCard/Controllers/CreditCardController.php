<?php

namespace App\Modules\CreditCard\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Modules\CreditCard\Requests\CreditCardRequest;
use App\Modules\CreditCard\Services\CreditCardService;
use Illuminate\Http\JsonResponse;

class CreditCardController extends Controller
{
    public function __construct(private CreditCardService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->service->list(), 'status' => 200]);
    }

    public function store(CreditCardRequest $request): JsonResponse
    {
        $card = $this->service->create($request->validated());
        return response()->json(['data' => $card, 'message' => 'Cartão criado.', 'status' => 201], 201);
    }

    public function update(CreditCardRequest $request, CreditCard $creditCard): JsonResponse
    {
        $updated = $this->service->update($creditCard, $request->validated());
        return response()->json(['data' => $updated, 'message' => 'Cartão atualizado.', 'status' => 200]);
    }

    public function destroy(CreditCard $creditCard): JsonResponse
    {
        $this->service->delete($creditCard);
        return response()->json(['message' => 'Cartão removido.', 'status' => 200]);
    }

    public function statements(CreditCard $creditCard): JsonResponse
    {
        $statements = $this->service->statements($creditCard);
        return response()->json(['data' => $statements, 'status' => 200]);
    }

    public function payStatement(CreditCardStatement $statement): JsonResponse
    {
        $updated = $this->service->payStatement($statement);
        return response()->json(['data' => $updated, 'message' => 'Fatura marcada como paga.', 'status' => 200]);
    }
}
