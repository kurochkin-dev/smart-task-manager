<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'estimated_hours' => 'nullable|integer|min:1',
            'actual_hours' => 'sometimes|integer|min:0',
            'required_skills' => 'nullable|array',
            'complexity' => 'sometimes|integer|min:1|max:5',
            'due_date' => 'nullable|date',
            'assigned_user_id' => 'nullable|exists:users,id',
        ];
    }
}
