<?php

namespace App\Jobs;

use App\Models\Task;
use App\Services\RabbitMQService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishTaskCreatedEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Task $task;

    /**
     * Create a new job instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     */
    public function handle(RabbitMQService $rabbitmq): void
    {
        $task = $this->task->fresh(['project']);

        $eventData = [
            'task_id' => $task->id,
            'title' => $task->title,
            'description' => $task->description ?? '',
            'priority' => $task->priority,
            'project_id' => $task->project_id,
            'skills' => $task->required_skills ?? [],
            'created_at' => $task->created_at->toIso8601String(),
        ];

        $rabbitmq->publish('tasks', 'task.created', $eventData);
    }
}
