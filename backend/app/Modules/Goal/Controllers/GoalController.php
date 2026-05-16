<?php

namespace App\Modules\Goal\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Modules\Goal\Requests\GoalProgressRequest;
use App\Modules\Goal\Requests\GoalRequest;
use App\Modules\Goal\Services\GoalService;
use Illuminate\Http\JsonResponse;

class GoalController extends Controller
{
    public function __construct(private GoalService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->service->list(), 'status' => 200]);
    }

    public function store(GoalRequest $request): JsonResponse
    {
        $goal = $this->service->create($request->validated());
        return response()->json(['data' => $goal, 'message' => 'Meta criada.', 'status' => 201], 201);
    }

    public function update(GoalRequest $request, Goal $goal): JsonResponse
    {
        $updated = $this->service->update($goal, $request->validated());
        return response()->json(['data' => $updated, 'message' => 'Meta atualizada.', 'status' => 200]);
    }

    public function progress(GoalProgressRequest $request, Goal $goal): JsonResponse
    {
        $updated = $this->service->addProgress($goal, $request->validated('amount'));
        return response()->json(['data' => $updated, 'message' => 'Progresso registrado.', 'status' => 200]);
    }

    public function destroy(Goal $goal): JsonResponse
    {
        $this->service->delete($goal);
        return response()->json(['message' => 'Meta removida.', 'status' => 200]);
    }
}
