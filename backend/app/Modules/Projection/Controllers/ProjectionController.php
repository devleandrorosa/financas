<?php

namespace App\Modules\Projection\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Projection\Services\ProjectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectionController extends Controller
{
    public function __construct(private ProjectionService $service) {}

    public function index(Request $request): JsonResponse
    {
        $months = (int) $request->query('months', 6);
        $months = max(1, min(24, $months));

        return response()->json([
            'data'   => $this->service->project($months),
            'status' => 200,
        ]);
    }
}
