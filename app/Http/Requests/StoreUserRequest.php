<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['admin', 'manager', 'user'])],
            'skills' => 'nullable|array',
            'max_workload' => 'nullable|integer|min:1|max:200',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Пользователь с таким email уже существует.',
            'password.min' => 'Пароль должен содержать минимум 8 символов.',
            'role.in' => 'Роль должна быть: admin, manager или user.',
        ];
    }
}
