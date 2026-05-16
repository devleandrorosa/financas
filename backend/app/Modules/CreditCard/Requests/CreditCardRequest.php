<?php

namespace App\Modules\CreditCard\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreditCardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:255',
            'bank'         => 'required|string|max:255',
            'limit_amount' => 'required|integer|min:0',
            'closing_day'  => 'required|integer|between:1,31',
            'due_day'      => 'required|integer|between:1,31',
        ];
    }
}
