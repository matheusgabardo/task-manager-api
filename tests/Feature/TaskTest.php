<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create(['name' => 'Test Project']);
    }

    public function test_can_list_tasks_for_a_project(): void
    {
        $this->project->tasks()->create(['title' => 'Task 1']);
        $this->project->tasks()->create(['title' => 'Task 2']);

        $response = $this->getJson("/api/projects/{$this->project->id}/tasks");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'project_id', 'title', 'description', 'status', 'priority', 'due_date', 'is_overdue', 'created_at']],
            ]);
    }

    public function test_can_filter_tasks_by_status(): void
    {
        $this->project->tasks()->create(['title' => 'Todo Task', 'status' => 'todo']);
        $this->project->tasks()->create(['title' => 'Done Task', 'status' => 'done']);

        $response = $this->getJson("/api/projects/{$this->project->id}/tasks?status=todo");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Todo Task');
    }

    public function test_can_filter_tasks_by_priority(): void
    {
        $this->project->tasks()->create(['title' => 'High Task', 'priority' => 'high']);
        $this->project->tasks()->create(['title' => 'Low Task', 'priority' => 'low']);

        $response = $this->getJson("/api/projects/{$this->project->id}/tasks?priority=high");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'High Task');
    }

    public function test_can_create_task(): void
    {
        $response = $this->postJson("/api/projects/{$this->project->id}/tasks", [
            'title' => 'New Task',
            'priority' => 'high',
            'due_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'New Task')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.status', 'todo');

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'project_id' => $this->project->id,
        ]);
    }

    public function test_cannot_create_task_without_title(): void
    {
        $response = $this->postJson("/api/projects/{$this->project->id}/tasks", [
            'priority' => 'high',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_can_update_task_status(): void
    {
        $task = $this->project->tasks()->create(['title' => 'Task']);

        $response = $this->patchJson("/api/tasks/{$task->id}", [
            'status' => 'in_progress',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_can_update_task_priority(): void
    {
        $task = $this->project->tasks()->create(['title' => 'Task']);

        $response = $this->patchJson("/api/tasks/{$task->id}", [
            'priority' => 'high',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.priority', 'high');
    }

    public function test_update_requires_at_least_one_field(): void
    {
        $task = $this->project->tasks()->create(['title' => 'Task']);

        $response = $this->patchJson("/api/tasks/{$task->id}", []);

        $response->assertUnprocessable();
    }

    public function test_can_soft_delete_task(): void
    {
        $task = $this->project->tasks()->create(['title' => 'Task to delete']);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_soft_deleted_task_not_listed(): void
    {
        $task = $this->project->tasks()->create(['title' => 'Deleted Task']);
        $task->delete();

        $this->project->tasks()->create(['title' => 'Active Task']);

        $response = $this->getJson("/api/projects/{$this->project->id}/tasks");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Active Task');
    }

    public function test_overdue_task_has_is_overdue_true(): void
    {
        $this->project->tasks()->create([
            'title' => 'Overdue Task',
            'status' => 'todo',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->getJson("/api/projects/{$this->project->id}/tasks");

        $response->assertOk()
            ->assertJsonPath('data.0.is_overdue', true);
    }
}
