<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskNote;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskNoteController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'type' => ['nullable', 'in:'.implode(',', array_keys(TaskNote::TYPES))],
        ]);

        $validated['type'] = $validated['type'] ?? 'note';
        $validated['created_by_id'] = $request->user()->id;
        $validated['sort_order'] = $task->notes()->max('sort_order') + 1;

        $task->notes()->create($validated);

        // An added note is incomplete by definition, so reopen a done task
        $task->syncCompletionFromNotes();

        $typeLabel = TaskNote::TYPES[$validated['type']] ?? 'Note';
        TaskActivity::log($task, $request->user()->id, 'note_added', "{$typeLabel} added: ".Str::limit($validated['body'], 60));

        return back()->with('success', 'Note added.');
    }

    public function update(Request $request, TaskNote $note)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'type' => ['nullable', 'in:'.implode(',', array_keys(TaskNote::TYPES))],
        ]);

        $oldType = $note->type;
        $note->update($validated);

        $message = 'Note updated: '.Str::limit($note->body, 60);
        if (isset($validated['type']) && $validated['type'] !== $oldType) {
            $message = 'Note changed to '.($note->typeLabel()).': '.Str::limit($note->body, 60);
        }

        TaskActivity::log($note->task, $request->user()->id, 'note_edited', $message);
        $note->task->syncCompletionFromNotes();

        return back()->with('success', 'Note updated.');
    }

    public function toggle(Request $request, TaskNote $note)
    {
        if (! $note->isCheckable()) {
            return back()->with('error', 'Comments cannot be marked as done.');
        }

        $note->update(['is_done' => ! $note->is_done]);
        $note->task->syncCompletionFromNotes();

        TaskActivity::log(
            $note->task,
            $request->user()->id,
            $note->is_done ? 'note_done' : 'note_reopened',
            ($note->is_done ? 'Note completed' : 'Note reopened').': '.Str::limit($note->body, 60)
        );

        return back()->with('success', $note->is_done ? 'Note completed.' : 'Note reopened.');
    }

    public function destroy(Request $request, TaskNote $note)
    {
        $task = $note->task;
        $body = $note->body;
        $note->delete();
        $task->syncCompletionFromNotes();

        TaskActivity::log($task, $request->user()->id, 'note_deleted', 'Note deleted: '.Str::limit($body, 60));

        return back()->with('success', 'Note deleted.');
    }
}
