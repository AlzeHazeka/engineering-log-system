<?php

namespace Database\Factories;

use App\Models\Feature;
use App\Models\Log;
use App\Models\System;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Log>
 */
class LogFactory extends Factory
{
    protected $model = Log::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement([
            'progress',
            'progress',
            'progress',
            'bug',
            'fix',
            'deployment',
            'maintenance',
            'decision',
            'idea',
        ]);

        $impact = $type === 'bug'
            ? $this->faker->randomElement(['low', 'medium', 'high', 'critical'])
            : null;

        $status = match ($type) {
            'bug' => $this->faker->randomElement(['open', 'resolved', 'ignored']),
            'fix' => $this->faker->randomElement(['open', 'resolved']),
            'progress' => $this->faker->randomElement(['on_progress', 'done']),
            default => null,
        };

        $loggedAt = $this->faker->dateTimeBetween('-10 days', 'now');
        $resolvedAt = ($type === 'fix' && $status === 'resolved')
            ? (clone $loggedAt)->modify('+'.(string) $this->faker->numberBetween(0, 3).' days')
            : null;

        return [
            'system_id' => System::factory(),
            'feature_id' => null,
            'type' => $type,
            'impact' => $impact,
            'status' => $status,
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(3),
            'logged_at' => $loggedAt,
            'resolved_at' => $resolvedAt,
        ];
    }

    public function forSystem(System $system): static
    {
        return $this->state(fn () => [
            'system_id' => $system->id,
            'feature_id' => null,
        ]);
    }

    public function forFeature(Feature $feature): static
    {
        return $this->state(fn () => [
            'system_id' => $feature->system_id,
            'feature_id' => $feature->id,
        ]);
    }

    public function referencing(Log $log): static
    {
        return $this
            ->state(fn () => [
                'system_id' => $log->system_id,
                'feature_id' => $log->feature_id,
            ])
            ->afterCreating(function (Log $created) use ($log) {
                $created->references()->syncWithoutDetaching([$log->id]);
            });
    }

    public function bug(string $impact = 'high', string $status = 'open'): static
    {
        return $this->state(fn () => [
            'type' => 'bug',
            'impact' => $impact,
            'status' => $status,
        ]);
    }

    public function fixResolved(): static
    {
        return $this->state(fn () => [
            'type' => 'fix',
            'impact' => null,
            'status' => 'resolved',
        ]);
    }
}
