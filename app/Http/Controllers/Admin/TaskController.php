<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\System;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with(['system', 'assignee', 'notes']);

        if ($systemId = $request->query('system')) {
            $query->where('system_id', $systemId);
        }

        if ($assignee = $request->query('assignee')) {
            $query->where('assignee_id', $assignee);
        }

        $tasks = $query->orderBy('sort_order')->orderBy('id')->get()
            ->groupBy('status');

        $systems = System::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('admin.tasks.index', [
            'tasks' => $tasks,
            'systems' => $systems,
            'users' => $users,
            'filters' => $request->only(['system', 'assignee']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'system_id' => ['required', 'exists:systems,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', 'in:todo,in_progress,review,done'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $validated['created_by_id'] = $request->user()->id;
        $validated['sort_order'] = Task::where('system_id', $validated['system_id'])
            ->where('status', $validated['status'])
            ->max('sort_order') + 1;

        $task = Task::create($validated);

        TaskActivity::log($task, $request->user()->id, 'created', 'Task created');

        return back()->with('success', 'Task created.');
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'system_id' => ['required', 'exists:systems,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', 'in:todo,in_progress,review,done'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $oldStatus = $task->status;
        $task->update($validated);

        if ($oldStatus !== $task->status) {
            TaskActivity::log($task, $request->user()->id, 'status', 'Status changed '.(Task::STATUSES[$oldStatus] ?? $oldStatus).' → '.$task->statusLabel());
        } else {
            TaskActivity::log($task, $request->user()->id, 'updated', 'Task details updated');
        }

        return back()->with('success', 'Task updated.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:todo,in_progress,review,done'],
        ]);

        $oldStatus = $task->status;
        $task->update(['status' => $validated['status']]);

        if ($oldStatus !== $task->status) {
            TaskActivity::log($task, $request->user()->id, 'status', 'Status changed '.(Task::STATUSES[$oldStatus] ?? $oldStatus).' → '.$task->statusLabel());
        }

        if ($request->wantsJson()) {
            return response()->json(['status' => 'ok', 'task_status' => $task->status]);
        }

        return back()->with('success', "Task moved to \"{$task->statusLabel()}\".");
    }

    public function show(Task $task)
    {
        $task->load(['system', 'assignee', 'createdBy', 'notes.createdBy', 'activities.user']);

        return view('admin.tasks.show', [
            'task' => $task,
            'users' => User::orderBy('name')->get(),
            'systems' => System::orderBy('name')->get(),
        ]);
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return back()->with('success', 'Task deleted.');
    }
}
