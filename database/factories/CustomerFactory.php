<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => strtoupper(fake()->name()),
            'code' => 'C-'.strtoupper(Str::random(6)),
            'login_code' => strtoupper(fake()->unique()->bothify('????????')),
            'email' => fake()->unique()->safeEmail(),
            'phone_code' => '+964',
            'phone' => '750'.fake()->numerify('#######'),
            'address' => fake()->city().', Iraq',
            'bid_limit' => fake()->randomElement([null, 5000, 10000, 25000]),
            'car_limit' => fake()->randomElement([null, 2, 5, 10]),
            'credit_limit' => fake()->randomElement([null, 20000, 50000]),
            'tags' => [],
        ];
    }
}
