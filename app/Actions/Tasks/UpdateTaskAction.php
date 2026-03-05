<?php

namespace App\Actions\Tasks;

use App\Models\Task;

class UpdateTaskAction
{
    public function handle(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->refresh();
    }
}
