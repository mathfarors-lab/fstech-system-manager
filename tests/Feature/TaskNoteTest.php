<?php

namespace Tests\Feature;

use App\Models\System;
use App\Models\Task;
use App\Models\TaskNote;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskNoteTest extends TestCase
{
    use RefreshDatabase;

    protected System $system;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminUserSeeder::class);
        $this->actingAs(User::where('email', 'developer@fstech.com')->first());

        $this->system = System::create([
            'name' => 'Notes App', 'slug' => 'notes-app', 'stack' => 'laravel',
            'environment' => 'development', 'status' => 'active',
        ]);
    }

    protected function makeTask(array $overrides = []): Task
    {
        return Task::create(array_merge([
            'system_id' => $this->system->id,
            'title' => 'Implement feature',
            'status' => 'todo',
            'priority' => 'medium',
        ], $overrides));
    }

    public function test_developer_can_add_note_to_task(): void
    {
        $task = $this->makeTask();

        $this->post(route('tasks.notes.store', $task), [
            'body' => 'Write unit tests for the KHQR flow',
        ])->assertRedirect();

        $this->assertDatabaseHas('task_notes', [
            'task_id' => $task->id,
            'body' => 'Write unit tests for the KHQR flow',
            'is_done' => false,
        ]);
    }

    public function test_task_with_multiple_notes_is_supported(): void
    {
        $task = $this->makeTask();

        $this->post(route('tasks.notes.store', $task), ['body' => 'Note one'])->assertRedirect();
        $this->post(route('tasks.notes.store', $task), ['body' => 'Note two'])->assertRedirect();
        $this->post(route('tasks.notes.store', $task), ['body' => 'Note three'])->assertRedirect();

        $this->assertCount(3, $task->fresh()->notes);
    }

    public function test_task_completes_when_all_notes_are_done(): void
    {
        $task = $this->makeTask();

        $this->post(route('tasks.notes.store', $task), ['body' => 'Step one']);
        $this->post(route('tasks.notes.store', $task), ['body' => 'Step two']);

        // Not done yet — task stays in its own status
        $note1 = TaskNote::where('body', 'Step one')->first();
        $note2 = TaskNote::where('body', 'Step two')->first();

        $this->patch(route('notes.toggle', $note1))->assertRedirect();
        $this->assertEquals('todo', $task->fresh()->status);

        // All notes ready -> task completes automatically
        $this->patch(route('notes.toggle', $note2))->assertRedirect();
        $this->assertEquals('done', $task->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_reopening_a_note_reopens_a_done_task(): void
    {
        $task = $this->makeTask(['status' => 'done']);

        $note = TaskNote::create([
            'task_id' => $task->id,
            'body' => 'Only note',
            'is_done' => true,
        ]);

        // Task was manually done; all notes done, so it stays done
        $this->assertEquals('done', $task->status);

        // Uncheck the note -> task reopens
        $this->patch(route('notes.toggle', $note))->assertRedirect();

        $this->assertEquals('in_progress', $task->fresh()->status);
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_adding_a_note_to_a_done_task_reopens_it(): void
    {
        $task = $this->makeTask(['status' => 'done']);

        $this->post(route('tasks.notes.store', $task), ['body' => 'New unfinished work'])
            ->assertRedirect();

        $this->assertEquals('in_progress', $task->fresh()->status);
    }

    public function test_deleting_last_undone_note_completes_task(): void
    {
        $task = $this->makeTask(['status' => 'in_progress']);

        $doneNote = TaskNote::create(['task_id' => $task->id, 'body' => 'Done step', 'is_done' => true]);
        $pendingNote = TaskNote::create(['task_id' => $task->id, 'body' => 'Pending step', 'is_done' => false]);

        $this->delete(route('notes.destroy', $pendingNote))->assertRedirect();

        // Remaining note is done -> task completes
        $this->assertEquals('done', $task->fresh()->status);
    }

    public function test_task_without_notes_is_not_affected_by_sync(): void
    {
        $task = $this->makeTask(['status' => 'review']);

        $task->syncCompletionFromNotes();

        $this->assertEquals('review', $task->fresh()->status);
    }

    public function test_note_body_is_required(): void
    {
        $task = $this->makeTask();

        $this->post(route('tasks.notes.store', $task), ['body' => ''])
            ->assertSessionHasErrors('body');
    }

    public function test_developer_can_edit_a_note(): void
    {
        $task = $this->makeTask();
        $note = TaskNote::create(['task_id' => $task->id, 'body' => 'Original text']);

        $this->patch(route('notes.update', $note), [
            'body' => 'Edited text',
            'type' => 'prompt',
        ])->assertRedirect();

        $this->assertDatabaseHas('task_notes', [
            'id' => $note->id,
            'body' => 'Edited text',
            'type' => 'prompt',
        ]);
    }

    public function test_comment_notes_do_not_drive_task_completion(): void
    {
        $task = $this->makeTask(['status' => 'todo']);
        $comment = TaskNote::create(['task_id' => $task->id, 'body' => 'FYI only', 'type' => 'comment']);

        // Toggling a comment is rejected
        $this->patch(route('notes.toggle', $comment))->assertRedirect();
        $this->assertFalse($comment->fresh()->is_done);

        // Comments alone never complete the task
        $task->syncCompletionFromNotes();
        $this->assertEquals('todo', $task->fresh()->status);

        // Deleting the last checkable note completes the task even with comments left
        TaskNote::create(['task_id' => $task->id, 'body' => 'Pending step', 'is_done' => false]);
        TaskNote::create(['task_id' => $task->id, 'body' => 'Done step', 'is_done' => true]);

        $pending = TaskNote::where('body', 'Pending step')->first();
        $this->delete(route('notes.destroy', $pending))->assertRedirect();

        $this->assertEquals('done', $task->fresh()->status);
        $this->assertDatabaseHas('task_notes', ['id' => $comment->id]);
    }

    public function test_notes_store_accepts_type(): void
    {
        $task = $this->makeTask();

        $this->post(route('tasks.notes.store', $task), [
            'body' => 'Use KHQR payment here',
            'type' => 'prompt',
        ])->assertRedirect();

        $this->assertDatabaseHas('task_notes', [
            'task_id' => $task->id,
            'body' => 'Use KHQR payment here',
            'type' => 'prompt',
        ]);
    }
}
