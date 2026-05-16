<?php

namespace App\Modules\Budget\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Modules\Budget\Requests\BudgetRequest;
use App\Modules\Budget\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(private BudgetService $service) {}

    public function index(Request $request): JsonResponse
    {
        $year  = (int) ($request->query('year',  now()->year));
        $month = (int) ($request->query('month', now()->month));

        $data = $this->service->listWithSpending($year, $month);
        return response()->json(['data' => $data, 'status' => 200]);
    }

    public function store(BudgetRequest $request): JsonResponse
    {
        $budget = $this->service->upsert($request->validated());
        return response()->json(['data' => $budget, 'message' => 'Orçamento salvo.', 'status' => 200]);
    }

    public function destroy(Budget $budget): JsonResponse
    {
        $this->service->delete($budget);
        return response()->json(['message' => 'Orçamento removido.', 'status' => 200]);
    }
}
