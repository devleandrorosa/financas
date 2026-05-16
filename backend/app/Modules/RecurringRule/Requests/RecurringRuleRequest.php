<?php

namespace App\Modules\RecurringRule\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecurringRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description'    => 'required|string|max:255',
            'amount'         => 'required|integer|min:1',
            'type'           => 'required|in:income,expense',
            'frequency'      => 'required|in:daily,weekly,monthly,yearly',
            'start_date'     => 'required|date',
            'end_date'       => 'nullable|date|after:start_date',
            'category_id'    => 'nullable|integer|exists:categories,id',
            'bank_account_id' => 'nullable|integer|exists:bank_accounts,id',
            'active'         => 'nullable|boolean',
        ];
    }
}
