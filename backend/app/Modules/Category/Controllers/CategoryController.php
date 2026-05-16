<?php

namespace App\Modules\Category\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Modules\Category\Requests\CategoryRequest;
use App\Modules\Category\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->service->list(), 'status' => 200]);
    }

    public function flat(): JsonResponse
    {
        return response()->json(['data' => $this->service->all(), 'status' => 200]);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $category = $this->service->create($request->validated());
        return response()->json(['data' => $category, 'message' => 'Categoria criada.', 'status' => 201], 201);
    }

    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $updated = $this->service->update($category, $request->validated());
        return response()->json(['data' => $updated, 'message' => 'Categoria atualizada.', 'status' => 200]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->service->delete($category);
        return response()->json(['message' => 'Categoria removida.', 'status' => 200]);
    }
}
