<?php

namespace App\Modules\Goal\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'type'           => 'nullable|in:savings,debt,purchase,emergency',
            'target_amount'  => 'required|integer|min:1',
            'current_amount' => 'nullable|integer|min:0',
            'deadline'       => 'nullable|date',
            'notes'          => 'nullable|string',
        ];
    }
}
