<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'status',
        'description',
        'repository_url',
    ];

    public function logs()
    {
        return $this->hasMany(Log::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
