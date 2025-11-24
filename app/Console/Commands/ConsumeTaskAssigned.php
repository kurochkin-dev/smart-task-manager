<?php

namespace App\Console\Commands;

use App\Services\RabbitMQService;
use App\Services\TaskService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConsumeTaskAssigned extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:consume-task-assigned';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume task.assigned events from RabbitMQ';

    /**
     * Execute the console command.
     */
    public function handle(RabbitMQService $rabbitmq, TaskService $taskService): int
    {
        $this->info('Starting to consume task.assigned events...');

        try {
            $rabbitmq->consume('task.assigned', function ($message) use ($taskService) {
                $data = json_decode($message->body, true);

                $this->info("Received task assignment: Task #{$data['task_id']} -> User #{$data['assignee_id']}");

                Log::info('Processing task.assigned event', $data);

                $taskService->assignTask(
                    $data['task_id'],
                    $data['assignee_id'],
                    [
                        'score' => $data['score'],
                        'reason' => $data['reason'],
                        'assigned_at' => $data['assigned_at'],
                    ]
                );

                $this->info("Task #{$data['task_id']} assigned to User #{$data['assignee_id']} successfully");
            });
        } catch (\Exception $e) {
            $this->error('Failed to consume messages: ' . $e->getMessage());
            Log::error('RabbitMQ consumer error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
