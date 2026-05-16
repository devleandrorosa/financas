<?php

namespace App\Modules\Category\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function list(): Collection
    {
        return Category::with('children')->whereNull('parent_id')->get();
    }

    public function all(): Collection
    {
        return Category::orderBy('name')->get();
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);
        return $category->fresh();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}
