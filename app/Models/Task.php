<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    public const STATUSES = [
        'todo' => 'To Do',
        'in_progress' => 'In Progress',
        'review' => 'Review',
        'done' => 'Done',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    public const NEXT_ACTIONS = [
        'todo' => ['status' => 'in_progress', 'label' => 'Start Progress', 'icon' => 'bi-play-fill'],
        'in_progress' => ['status' => 'review', 'label' => 'Send to Review', 'icon' => 'bi-eye'],
        'review' => ['status' => 'done', 'label' => 'Mark Done', 'icon' => 'bi-check2'],
    ];

    protected $fillable = [
        'system_id',
        'title',
        'description',
        'status',
        'priority',
        'assignee_id',
        'created_by_id',
        'due_date',
        'completed_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Task $task) {
            if ($task->status === 'done' && $task->isDirty('status')) {
                $task->completed_at = now();
            }

            if ($task->status !== 'done') {
                $task->completed_at = null;
            }
        });
    }

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    public function notes()
    {
        return $this->hasMany(TaskNote::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activities()
    {
        return $this->hasMany(TaskActivity::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Keep the task status in sync with its notes:
     * - all notes done          -> task is done
     * - an incomplete note exists -> a done task reopens as in_progress
     */
    public function syncCompletionFromNotes(): void
    {
        $checkable = $this->notes()->whereIn('type', TaskNote::CHECKABLE_TYPES);

        if (! $checkable->exists()) {
            return;
        }

        $allDone = $checkable->clone()->where('is_done', false)->doesntExist();

        if ($allDone) {
            if ($this->status !== 'done') {
                $this->update(['status' => 'done']);
            }
        } elseif ($this->status === 'done') {
            $this->update(['status' => 'in_progress']);
        }
    }

    public function notesRemaining(): int
    {
        return $this->notes->whereIn('type', TaskNote::CHECKABLE_TYPES)->where('is_done', false)->count();
    }

    public function allNotesDone(): bool
    {
        return $this->notesRemaining() === 0;
    }

    public function nextStatusAction(): ?array
    {
        return self::NEXT_ACTIONS[$this->status] ?? null;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? 'To Do';
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? 'Medium';
    }
}
