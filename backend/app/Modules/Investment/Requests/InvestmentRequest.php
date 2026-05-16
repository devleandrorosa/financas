<?php

namespace App\Modules\Investment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvestmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:255',
            'type'         => 'required|string|max:50',
            'institution'  => 'required|string|max:255',
            'amount'       => 'required|integer|min:0',
            'purchased_at' => 'required|date',
            'maturity_at'  => 'nullable|date|after:purchased_at',
            'notes'        => 'nullable|string',
        ];
    }
}
