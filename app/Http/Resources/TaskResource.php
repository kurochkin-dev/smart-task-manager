<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'required_skills' => $this->required_skills,
            'complexity' => $this->complexity,
            'due_date' => $this->due_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'assigned_user' => new UserResource($this->whenLoaded('assignedUser')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'task_logs' => $this->whenLoaded('taskLogs'),
        ];
    }
}
