<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePetRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'status'     => ['nullable', 'in:available,pending,sold'],
            'photo_urls' => ['nullable', 'string'],
            'tags'       => ['nullable', 'string'],
            'category'   => ['nullable', 'string', 'max:255'],
        ];
    }
}
