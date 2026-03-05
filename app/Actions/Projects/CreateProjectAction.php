<?php

namespace App\Actions\Projects;

use App\Models\Project;

class CreateProjectAction
{
    public function handle(array $data): Project
    {
        return Project::create($data);
    }
}
