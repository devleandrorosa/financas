<?php

namespace App\Modules\Budget\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BudgetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => 'required|integer|exists:categories,id',
            'year'        => 'required|integer|digits:4',
            'month'       => 'required|integer|between:1,12',
            'amount'      => 'required|integer|min:1',
        ];
    }
}
