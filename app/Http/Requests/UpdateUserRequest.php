<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($this->route('user'))
            ],
            'password' => 'sometimes|string|min:8',
            'role' => ['sometimes', Rule::in(['admin', 'manager', 'user'])],
            'skills' => 'nullable|array',
            'workload' => 'sometimes|integer|min:0',
            'max_workload' => 'sometimes|integer|min:1|max:200',
        ];
    }
}
