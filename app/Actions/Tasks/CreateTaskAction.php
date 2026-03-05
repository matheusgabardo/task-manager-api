<?php

namespace App\Actions\Tasks;

use App\Models\Project;
use App\Models\Task;

class CreateTaskAction
{
    public function handle(Project $project, array $data): Task
    {
        return $project->tasks()->create($data);
    }
}
