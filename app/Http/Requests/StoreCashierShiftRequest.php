<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashierShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $storeId = $this->input('photobox_store_id');

        return [
            'opening_cash' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            // toko kosong + studio kosong = semua studio. Toko saja = semua studio toko itu. Studio = hanya studio itu (harus milik toko bila toko dipilih).
            'photobox_store_id' => ['nullable', 'integer', 'exists:photobox_stores,id'],
            'photobox_studio_ids' => ['nullable', 'array', 'max:50'],
            'photobox_studio_ids.*' => [
                'integer', 'distinct',
                Rule::exists('photobox_studios', 'id')->when($storeId, fn ($rule) => $rule->where('store_id', (int) $storeId)),
            ],
        ];
    }
}
