<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    protected $fillable = [
        'system_id',
        'version',
        'release_date',
        'changelog',
    ];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
        ];
    }

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    public function builds()
    {
        return $this->hasMany(Build::class);
    }
}
