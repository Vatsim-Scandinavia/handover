<?php
namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(2),
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'is_admin_group' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(['is_admin_group' => true]);
    }
}
