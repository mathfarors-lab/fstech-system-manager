<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskNote extends Model
{
    public const TYPES = [
        'note' => 'Note',
        'prompt' => 'Prompt',
        'comment' => 'Comment',
    ];

    public const CHECKABLE_TYPES = ['note', 'prompt'];

    protected $fillable = [
        'task_id',
        'body',
        'type',
        'is_done',
        'sort_order',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
        ];
    }

    public function isCheckable(): bool
    {
        return in_array($this->type, self::CHECKABLE_TYPES, true);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Note';
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
