<?php
namespace Database\Factories;

use App\Models\GroupAttributeDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupAttributeDefinitionFactory extends Factory
{
    protected $model = GroupAttributeDefinition::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->lexify('attr_????'),
            'label' => $this->faker->words(2, true),
        ];
    }
}
