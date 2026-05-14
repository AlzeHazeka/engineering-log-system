<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    /** @use HasFactory<\Database\Factories\FeatureFactory> */
    use HasFactory;

    protected $fillable = [
        'system_id',
        'title',
        'description',
        'status',
        'progress',
        'category',
        'start_date',
        'due_date',
        'completed_at',
        'assigned_team',
    ];

    protected $casts = [
        'progress' => 'integer',
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    public function logs()
    {
        return $this->hasMany(Log::class);
    }

    public function recalculateProgressFromLogs(): void
    {
        $total = $this->logs()
            ->where(function ($q) {
                $q->where('type', 'progress')
                    ->orWhere(function ($q) {
                        $q->where('type', 'bug')
                            ->where('status', '!=', 'ignored');
                    });
            })
            ->count();

        if ($total === 0) {
            $this->updateQuietly([
                'progress' => 0,
            ]);
            return;
        }

        $completedProgress = $this->logs()
            ->where('type', 'progress')
            ->where('status', 'done')
            ->count();

        $completedBugs = $this->logs()
            ->where('type', 'bug')
            ->where('status', 'resolved')
            ->count();

        $completed = $completedProgress + $completedBugs;

        $progress = (int) round(($completed / $total) * 100);
        $progress = max(0, min(100, $progress));

        $update = ['progress' => $progress];

        if ($progress >= 100 && $this->status !== 'done') {
            $update['status'] = 'done';
            $update['completed_at'] = $this->completed_at ?? now()->toDateString();
        }

        $this->updateQuietly($update);
    }

    public function isCompletedOnTime(): ?bool
    {
        if (!$this->completed_at || !$this->due_date) {
            return null;
        }

        return $this->completed_at->lessThanOrEqualTo($this->due_date->copy()->endOfDay());
    }
}
