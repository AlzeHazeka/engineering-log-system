<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaRule extends Model
{
    protected $fillable = [
        'type',
        'impact',
        'days',
    ];

    public const TYPE_BUG_RESOLUTION = 'bug_resolution';

    /**
     * @return array{low:int, medium:int, high:int, critical:int}
     */
    public static function bugResolutionDays(): array
    {
        $defaults = (array) config('sla.bug_resolution_days', []);

        $rows = self::query()
            ->select(['impact', 'days'])
            ->where('type', self::TYPE_BUG_RESOLUTION)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'low' => (int) ($defaults['low'] ?? 5),
                'medium' => (int) ($defaults['medium'] ?? 3),
                'high' => (int) ($defaults['high'] ?? 2),
                'critical' => (int) ($defaults['critical'] ?? 1),
            ];
        }

        $map = $rows->pluck('days', 'impact')->all();

        return [
            'low' => (int) ($map['low'] ?? ($defaults['low'] ?? 5)),
            'medium' => (int) ($map['medium'] ?? ($defaults['medium'] ?? 3)),
            'high' => (int) ($map['high'] ?? ($defaults['high'] ?? 2)),
            'critical' => (int) ($map['critical'] ?? ($defaults['critical'] ?? 1)),
        ];
    }

    public static function bugResolutionDaysForImpact(string $impact): int
    {
        $impact = $impact ?: 'medium';

        $row = self::query()
            ->select(['days'])
            ->where('type', self::TYPE_BUG_RESOLUTION)
            ->where('impact', $impact)
            ->first();

        if ($row) {
            return (int) $row->days;
        }

        return (int) config("sla.bug_resolution_days.$impact", 3);
    }
}

