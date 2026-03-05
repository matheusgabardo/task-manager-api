<?php

namespace App\Actions\Tasks;

use App\Models\Task;

class DeleteTaskAction
{
    public function handle(Task $task): void
    {
        $task->delete();
    }
}
