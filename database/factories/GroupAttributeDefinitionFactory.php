<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GroupAttributeDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupAttributeDefinition>
 */
class GroupAttributeDefinitionFactory extends Factory
{
    protected $model = GroupAttributeDefinition::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->lexify('attr_????'),
            'label' => fake()->words(2, true),
        ];
    }
}
