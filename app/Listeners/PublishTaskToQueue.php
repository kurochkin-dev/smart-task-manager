<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Jobs\PublishTaskCreatedEvent;

class PublishTaskToQueue
{
    /**
     * Handle the event.
     */
    public function handle(TaskCreated $event): void
    {
        if (!$event->task->assignee_id) {
            PublishTaskCreatedEvent::dispatch($event->task);
        }
    }
}
