<?php

namespace App\Modules\Category\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'      => 'required|string|max:255',
            'type'      => 'required|in:expense,income',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'color'     => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }
}
