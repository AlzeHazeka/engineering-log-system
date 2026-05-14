<?php

namespace Database\Factories;

use App\Models\Feature;
use App\Models\System;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feature>
 */
class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['planned', 'in_progress', 'done', 'on_hold']);
        $category = $this->faker->randomElement(['feature', 'improvement', 'maintenance']);

        $progress = match ($status) {
            'planned' => 0,
            'done' => 100,
            'on_hold' => $this->faker->numberBetween(5, 60),
            default => $this->faker->numberBetween(10, 95),
        };

        $startDate = $status === 'planned'
            ? null
            : $this->faker->dateTimeBetween('-45 days', '-3 days');

        $dueDate = $this->faker->boolean(70)
            ? $this->faker->dateTimeBetween('now', '+45 days')
            : null;

        $completedAt = $status === 'done'
            ? $this->faker->dateTimeBetween('-30 days', 'now')
            : null;

        return [
            'system_id' => System::factory(),
            'title' => $this->faker->randomElement([
                'Product Management',
                'Sales Transaction',
                'Reporting',
                'Leave & Approval Flow',
                'Attendance Sync',
                'Payroll Export',
                'Stock Receiving',
                'Stock Adjustment Audit',
                'Warehouse Location Map',
            ]),
            'description' => $this->faker->sentence(12),
            'status' => $status,
            'progress' => $progress,
            'category' => $category,
            'start_date' => $startDate?->format('Y-m-d'),
            'due_date' => $dueDate?->format('Y-m-d'),
            'completed_at' => $completedAt?->format('Y-m-d H:i:s'),
            'assigned_team' => $this->faker->randomElement([
                'POS Squad',
                'HR Tech',
                'Inventory Squad',
                'Platform',
            ]),
        ];
    }

    public function forSystem(System $system): static
    {
        return $this->state(fn () => ['system_id' => $system->id]);
    }
}
