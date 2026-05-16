<?php

namespace App\Modules\BankAccount\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BankAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:255',
            'bank'    => 'required|string|max:255',
            'type'    => 'required|in:checking,savings,investment,wallet',
            'balance' => 'required|integer',
        ];
    }
}
