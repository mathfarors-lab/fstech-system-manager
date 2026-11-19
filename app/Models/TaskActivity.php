<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskActivity extends Model
{
    public const TYPES = [
        'created' => 'Created',
        'status' => 'Status',
        'updated' => 'Updated',
        'note_added' => 'Note Added',
        'note_edited' => 'Note Edited',
        'note_done' => 'Note Done',
        'note_reopened' => 'Note Reopened',
        'note_deleted' => 'Note Deleted',
    ];

    protected $fillable = [
        'task_id',
        'user_id',
        'type',
        'message',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Updated';
    }

    public static function log(Task $task, ?int $userId, string $type, string $message): void
    {
        $task->activities()->create([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
        ]);
    }
}
