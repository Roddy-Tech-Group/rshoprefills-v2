<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class SetupTransactionPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pin' => ['required', 'string', 'size:4', 'regex:/^[0-9]+$/', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'pin.size' => 'The PIN must be exactly 4 digits.',
            'pin.regex' => 'The PIN can only contain numbers.',
            'pin.confirmed' => 'The PIN confirmation does not match.',
        ];
    }
}
