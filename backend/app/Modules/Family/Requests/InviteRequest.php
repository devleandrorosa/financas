<?php

namespace App\Modules\Family\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InviteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'role'  => ['sometimes', 'in:admin,member'],
        ];
    }
}
