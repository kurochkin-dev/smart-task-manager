<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'skills' => $this->skills,
            'workload' => $this->workload,
            'max_workload' => $this->max_workload,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'created_tasks' => TaskResource::collection($this->whenLoaded('createdTasks')),
            'task_logs' => $this->whenLoaded('taskLogs'),
        ];
    }
}
