<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashierShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opening_cash' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            // kosong = semua studio photobox
            'photobox_studio_ids' => ['nullable', 'array', 'max:50'],
            'photobox_studio_ids.*' => ['integer', 'distinct', 'exists:photobox_studios,id'],
        ];
    }
}
