<?php

namespace App\Modules\RecurringRule\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RecurringRule;
use App\Modules\RecurringRule\Requests\RecurringRuleRequest;
use App\Modules\RecurringRule\Services\RecurringRuleService;
use Illuminate\Http\JsonResponse;

class RecurringRuleController extends Controller
{
    public function __construct(private RecurringRuleService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->service->list(), 'status' => 200]);
    }

    public function store(RecurringRuleRequest $request): JsonResponse
    {
        $rule = $this->service->create($request->validated());
        return response()->json(['data' => $rule, 'message' => 'Regra criada.', 'status' => 201], 201);
    }

    public function update(RecurringRuleRequest $request, RecurringRule $recurringRule): JsonResponse
    {
        $updated = $this->service->update($recurringRule, $request->validated());
        return response()->json(['data' => $updated, 'message' => 'Regra atualizada.', 'status' => 200]);
    }

    public function toggle(RecurringRule $recurringRule): JsonResponse
    {
        $updated = $this->service->toggle($recurringRule);
        return response()->json(['data' => $updated, 'message' => 'Status atualizado.', 'status' => 200]);
    }

    public function destroy(RecurringRule $recurringRule): JsonResponse
    {
        $this->service->delete($recurringRule);
        return response()->json(['message' => 'Regra removida.', 'status' => 200]);
    }
}
