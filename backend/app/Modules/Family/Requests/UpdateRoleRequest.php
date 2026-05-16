<?php

namespace App\Modules\Family\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'role' => ['required', 'in:admin,member'],
        ];
    }
}
