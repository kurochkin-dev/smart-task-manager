<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'estimated_hours' => 'nullable|integer|min:1',
            'required_skills' => 'nullable|array',
            'complexity' => 'nullable|integer|min:1|max:5',
            'due_date' => 'nullable|date',
            'project_id' => 'required|exists:projects,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'created_by' => 'required|exists:users,id',
        ];
    }
}
