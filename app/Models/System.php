<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    /** @use HasFactory<\Database\Factories\SystemFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'kind',
        'status',
        'stage',
        'description',
        'repository_url',
        'released_at',
    ];

    protected $casts = [
        'released_at' => 'date',
    ];

    public function getEffectiveStageAttribute()
    {
        return $this->stage ?? ($this->released_at ? 'production' : 'development');
    }

    public function features()
    {
        return $this->hasMany(Feature::class);
    }

    public function logs()
    {
        return $this->hasMany(Log::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
