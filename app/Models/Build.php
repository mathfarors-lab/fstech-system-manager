<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Build extends Model
{
    public const STATUSES = [
        'planned' => 'Planned',
        'building' => 'Building',
        'deployed' => 'Deployed',
        'failed' => 'Failed',
        'rolled_back' => 'Rolled Back',
    ];

    protected $fillable = [
        'system_id',
        'version_id',
        'version_label',
        'environment',
        'status',
        'built_by_id',
        'built_at',
        'deployed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'built_at' => 'datetime',
            'deployed_at' => 'datetime',
        ];
    }

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    public function version()
    {
        return $this->belongsTo(Version::class);
    }

    public function builtBy()
    {
        return $this->belongsTo(User::class, 'built_by_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? 'Planned';
    }
}
