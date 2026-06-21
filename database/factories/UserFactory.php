<?php
namespace Database\Factories;

use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->unique()->numberBetween(1000000, 2000000000),
            'email' => $this->faker->unique()->safeEmail(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
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
