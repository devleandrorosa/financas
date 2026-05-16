<?php

namespace App\Modules\AI\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AIImportUploadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,csv,txt,xlsx,xls', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Selecione um arquivo.',
            'file.mimes'    => 'Formato aceito: PDF, CSV, TXT, XLSX.',
            'file.max'      => 'O arquivo deve ter no máximo 10 MB.',
        ];
    }
}
