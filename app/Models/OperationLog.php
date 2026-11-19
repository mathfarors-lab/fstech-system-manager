<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationLog extends Model
{
    public const TYPES = [
        'backup' => 'Backup',
        'maintenance' => 'Maintenance',
        'issue' => 'Issue',
        'other' => 'Other',
    ];

    protected $table = 'operation_logs';

    protected $fillable = [
        'system_id',
        'type',
        'title',
        'details',
        'performed_by_id',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
        ];
    }

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Other';
    }
}
