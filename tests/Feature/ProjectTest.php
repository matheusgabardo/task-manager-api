<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_projects_with_tasks_count(): void
    {
        $project = Project::create(['name' => 'Test Project']);
        $project->tasks()->create(['title' => 'Task 1']);
        $project->tasks()->create(['title' => 'Task 2']);

        $response = $this->getJson('/api/projects');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Test Project')
            ->assertJsonPath('data.0.tasks_count', 2)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'description', 'status', 'tasks_count', 'created_at']],
            ]);
    }

    public function test_can_create_project_with_valid_data(): void
    {
        $response = $this->postJson('/api/projects', [
            'name' => 'New Project',
            'description' => 'A test project',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Project')
            ->assertJsonPath('data.description', 'A test project')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.tasks_count', 0);

        $this->assertDatabaseHas('projects', ['name' => 'New Project']);
    }

    public function test_cannot_create_project_without_name(): void
    {
        $response = $this->postJson('/api/projects', [
            'description' => 'Missing name',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_create_project_with_invalid_status(): void
    {
        $response = $this->postJson('/api/projects', [
            'name' => 'Test',
            'status' => 'invalid_status',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }
}
