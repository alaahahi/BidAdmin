<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BrokerFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->company(), 'email' => fake()->unique()->companyEmail(), 'covered' => false];
    }
}
