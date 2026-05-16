<?php

namespace App\Modules\Transaction\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description'    => 'required|string|max:255',
            'amount'         => 'required|integer|min:1',
            'type'           => 'required|in:income,expense',
            'date'           => 'required|date',
            'status'         => 'nullable|in:pending,confirmed,cancelled',
            'notes'          => 'nullable|string',
            'category_id'    => 'nullable|integer|exists:categories,id',
            'bank_account_id' => 'nullable|integer|exists:bank_accounts,id',
            'credit_card_id' => 'nullable|integer|exists:credit_cards,id',
            'installments'   => 'nullable|integer|between:1,48',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (empty($this->bank_account_id) && empty($this->credit_card_id)) {
                $v->errors()->add('bank_account_id', 'Informe uma conta bancária ou cartão de crédito.');
            }
        });
    }
}
