<?php

namespace App\Modules\Investment\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Modules\Investment\Requests\InvestmentRequest;
use App\Modules\Investment\Services\InvestmentService;
use Illuminate\Http\JsonResponse;

class InvestmentController extends Controller
{
    public function __construct(private InvestmentService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->service->list(), 'status' => 200]);
    }

    public function store(InvestmentRequest $request): JsonResponse
    {
        $investment = $this->service->create($request->validated());
        return response()->json(['data' => $investment, 'message' => 'Investimento criado.', 'status' => 201], 201);
    }

    public function update(InvestmentRequest $request, Investment $investment): JsonResponse
    {
        $updated = $this->service->update($investment, $request->validated());
        return response()->json(['data' => $updated, 'message' => 'Investimento atualizado.', 'status' => 200]);
    }

    public function destroy(Investment $investment): JsonResponse
    {
        $this->service->delete($investment);
        return response()->json(['message' => 'Investimento removido.', 'status' => 200]);
    }
}
