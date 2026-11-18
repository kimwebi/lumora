<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseCreditsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'package' => ['required', 'string', Rule::in(array_keys(config('lumora.credits.packages', [])))],
            'purchase_token' => ['required', 'string', 'max:64', 'alpha_dash'],
        ];
    }
}
