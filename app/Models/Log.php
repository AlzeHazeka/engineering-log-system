<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Models\SlaRule;

class Log extends Model
{
    /** @use HasFactory<\Database\Factories\LogFactory> */
    use HasFactory;

    protected $fillable = [
        'system_id',
        'feature_id',
        'type',
        'impact',
        'status',
        'title',
        'description',
        'logged_at',
        'resolved_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'resolved_at' => 'datetime',
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

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }

    public function references()
    {
        return $this->belongsToMany(
            self::class,
            'log_references',
            'log_id',
            'reference_log_id'
        )->withTimestamps();
    }

    public function referencedBy()
    {
        return $this->belongsToMany(
            self::class,
            'log_references',
            'reference_log_id',
            'log_id'
        )->withTimestamps();
    }

    public function scopeBug($query)
    {
        return $query->where('type', 'bug');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function getDurationInDays(): ?int
    {
        // Treat `logged_at` as the source-of-truth for the actual event date.
        // `resolved_at` exists for backward-compatibility / optional tracking.
        $endAt = $this->logged_at ?? $this->resolved_at;

        if (!$endAt) {
            return null;
        }

        return $this->logged_at?->diffInDays($endAt);
    }

    public function isResolvedOnTime(): ?bool
    {
        if ($this->type !== 'fix' || $this->status !== 'resolved') {
            return null;
        }

        // Prefer impact from referenced bugs (worst case).
        $impactRank = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        $impactFromRefs = $this->references
            ?->where('type', 'bug')
            ?->pluck('impact')
            ?->filter()
            ?->map(fn ($v) => (string) $v)
            ?->sortByDesc(fn ($v) => $impactRank[$v] ?? 0)
            ?->first();

        $impact = $impactFromRefs ?: ($this->impact ?? 'medium');

        $slaDays = $this->getSlaDays();
        $durationDays = $this->getSlaDurationInDays();

        if ($slaDays === null || $durationDays === null) {
            return null;
        }

        return $durationDays <= $slaDays;
    }

    public function getSlaDays(): ?int
    {
        if ($this->type !== 'fix' || $this->status !== 'resolved') {
            return null;
        }

        $impactRank = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        $impactFromRefs = $this->references
            ?->where('type', 'bug')
            ?->pluck('impact')
            ?->filter()
            ?->map(fn ($v) => (string) $v)
            ?->sortByDesc(fn ($v) => $impactRank[$v] ?? 0)
            ?->first();

        $impact = $impactFromRefs ?: ($this->impact ?? 'medium');

        return SlaRule::bugResolutionDaysForImpact((string) $impact);
    }

    public function getSlaDurationInDays(): ?int
    {
        if ($this->type !== 'fix' || $this->status !== 'resolved') {
            return null;
        }

        $endAt = $this->logged_at ?? $this->resolved_at;
        if (!$endAt) {
            return null;
        }

        $startAt = $this->references?->where('type', 'bug')?->min('logged_at');
        $startAt = $startAt ? Carbon::parse($startAt) : $this->logged_at;

        if (!$startAt) {
            return null;
        }

        return $startAt->diffInDays($endAt);
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
