<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCashierShiftExpensesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_cash' => ['nullable', 'integer', 'min:0'],
            'expense_total' => ['nullable', 'integer', 'min:0'],
            'expense_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
