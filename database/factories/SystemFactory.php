<?php

namespace Database\Factories;

use App\Models\System;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\System>
 */
class SystemFactory extends Factory
{
    protected $model = System::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true).' System';

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'status' => 'active',
            'description' => $this->faker->sentence(10),
            'repository_url' => null,
            'released_at' => $this->faker->boolean(30)
                ? $this->faker->dateTimeBetween('-2 years', '-1 day')->format('Y-m-d')
                : null,
        ];
    }
}

