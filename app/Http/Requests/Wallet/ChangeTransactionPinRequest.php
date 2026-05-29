<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class ChangeTransactionPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'old_pin' => ['required', 'string', 'size:4', 'regex:/^[0-9]+$/'],
            'new_pin' => ['required', 'string', 'size:4', 'regex:/^[0-9]+$/', 'confirmed', 'different:old_pin'],
        ];
    }

    public function messages(): array
    {
        return [
            'old_pin.size' => 'The old PIN must be exactly 4 digits.',
            'new_pin.size' => 'The new PIN must be exactly 4 digits.',
            'new_pin.confirmed' => 'The new PIN confirmation does not match.',
            'new_pin.different' => 'The new PIN must be different from the old PIN.',
        ];
    }
}
