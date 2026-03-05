<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\CreateTaskAction;
use App\Actions\Tasks\DeleteTaskAction;
use App\Actions\Tasks\UpdateTaskAction;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $tasks = $project->tasks()
            ->filter($request->only(['status', 'priority']))
            ->latest()
            ->cursorPaginate(15);

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request, Project $project): TaskResource
    {
        $task = (new CreateTaskAction)->handle($project, $request->validated());

        return new TaskResource($task->refresh());
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $task = (new UpdateTaskAction)->handle($task, $request->validated());

        return new TaskResource($task);
    }

    public function destroy(Task $task): Response
    {
        (new DeleteTaskAction)->handle($task);

        return response()->noContent();
    }
}
