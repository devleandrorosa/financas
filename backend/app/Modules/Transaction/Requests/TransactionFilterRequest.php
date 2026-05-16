<?php

namespace App\Modules\Transaction\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'year'           => 'nullable|integer|digits:4',
            'month'          => 'nullable|integer|between:1,12',
            'type'           => 'nullable|in:income,expense',
            'status'         => 'nullable|in:pending,confirmed,cancelled',
            'category_id'    => 'nullable|integer',
            'bank_account_id' => 'nullable|integer',
            'credit_card_id' => 'nullable|integer',
        ];
    }
}
