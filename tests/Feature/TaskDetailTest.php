<?php

namespace Tests\Feature;

use App\Models\System;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskNote;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskDetailTest extends TestCase
{
    use RefreshDatabase;

    protected System $system;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminUserSeeder::class);
        $this->actingAs(User::where('email', 'developer@fstech.com')->first());

        $this->system = System::create([
            'name' => 'Detail App', 'slug' => 'detail-app', 'stack' => 'laravel',
            'environment' => 'development', 'status' => 'active',
        ]);
    }

    protected function makeTask(array $overrides = []): Task
    {
        return Task::create(array_merge([
            'system_id' => $this->system->id,
            'title' => 'Detail page task',
            'status' => 'todo',
            'priority' => 'medium',
        ], $overrides));
    }

    public function test_task_detail_page_renders(): void
    {
        $task = $this->makeTask();

        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Developer Notes')
            ->assertSee('History')
            ->assertSee($task->title);
    }

    public function test_creating_a_task_logs_created_activity(): void
    {
        $this->post(route('tasks.store'), [
            'system_id' => $this->system->id,
            'title' => 'New tracked task',
            'status' => 'todo',
            'priority' => 'medium',
        ])->assertRedirect();

        $task = Task::where('title', 'New tracked task')->first();

        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'type' => 'created',
            'message' => 'Task created',
        ]);
    }

    public function test_status_change_logs_activity(): void
    {
        $task = $this->makeTask();

        $this->patch(route('tasks.status', $task), ['status' => 'in_progress'])->assertRedirect();

        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'type' => 'status',
            'message' => 'Status changed To Do → In Progress',
        ]);
    }

    public function test_note_lifecycle_logs_activities(): void
    {
        $task = $this->makeTask();

        $this->post(route('tasks.notes.store', $task), ['body' => 'Tracked note'])->assertRedirect();
        $note = TaskNote::where('body', 'Tracked note')->first();

        $this->patch(route('notes.toggle', $note))->assertRedirect();
        $this->patch(route('notes.update', $note), ['body' => 'Tracked note edited'])->assertRedirect();

        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'type' => 'note_added',
            'message' => 'Note added: Tracked note',
        ]);
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'type' => 'note_done',
        ]);
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'type' => 'note_edited',
            'message' => 'Note updated: Tracked note edited',
        ]);

        $this->delete(route('notes.destroy', $note))->assertRedirect();
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'type' => 'note_deleted',
        ]);
    }

    public function test_show_page_lists_history_entries(): void
    {
        $task = $this->makeTask();
        TaskActivity::log($task, null, 'status', 'Status changed To Do → In Progress');

        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Status changed To Do → In Progress');
    }
}
