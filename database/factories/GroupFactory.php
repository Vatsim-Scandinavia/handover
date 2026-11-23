<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'is_admin_group' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(['is_admin_group' => true]);
    }
}
