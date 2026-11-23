<?php

declare(strict_types=1);

namespace Database\Factories;

use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->numberBetween(1000000, 2000000000),
            'email' => fake()->unique()->safeEmail(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'rating' => 1,
            'rating_short' => 'OBS',
            'rating_long' => 'Observer',
            'pilot_rating' => 'P0',
            'pilot_rating_short' => 'P0',
            'pilot_rating_long' => 'No Pilot Rating',
            'region' => 'EUR',
            'division' => 'EUD',
            'accepted_privacy' => 1,
        ];
    }
}
