<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class ProcessRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'approve' => ['required', 'boolean'],
            'rejection_reason' => ['required_if:approve,false', 'nullable', 'string', 'max:1000'],
        ];
    }
}
