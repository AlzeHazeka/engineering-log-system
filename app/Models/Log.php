<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = [
        'system_id',
        'type',
        'impact',
        'title',
        'description',
        'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_datetime',
        'formatted_created_at',
        'formatted_updated_at',
        'formatted_time',
    ];

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Formatted Accessors
    |--------------------------------------------------------------------------
    */

    public function getFormattedDatetimeAttribute()
    {
        return $this->logged_at?->locale('id')
            ?->translatedFormat('l, d M Y H:i');
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at?->locale('id')
            ?->translatedFormat('l, d M Y H:i');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at?->locale('id')
            ?->translatedFormat('l, d M Y H:i');
    }

    public function getFormattedTimeAttribute()
    {
        return $this->logged_at?->format('H:i');
    }
}