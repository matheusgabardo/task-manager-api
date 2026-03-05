<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
