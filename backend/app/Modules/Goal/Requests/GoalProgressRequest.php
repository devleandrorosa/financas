<?php

namespace App\Modules\Goal\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoalProgressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => 'required|integer|min:1',
        ];
    }
}
