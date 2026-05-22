<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class ResetTransactionPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'new_pin' => ['required', 'string', 'size:4', 'regex:/^[0-9]+$/', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_pin.size' => 'The new PIN must be exactly 4 digits.',
            'new_pin.confirmed' => 'The new PIN confirmation does not match.',
        ];
    }
}
