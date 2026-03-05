<?php

namespace App\Http\Controllers;

use App\Actions\Projects\CreateProjectAction;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $projects = Project::withCount('tasks')
            ->latest()
            ->cursorPaginate(15);

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request): ProjectResource
    {
        $project = (new CreateProjectAction)->handle($request->validated());

        $project->refresh()->loadCount('tasks');

        return new ProjectResource($project);
    }
}
