<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BidFactory extends Factory
{
    public function definition(): array
    {
        $amount = fake()->numberBetween(10, 300) * 50;

        return [
            'provider' => 'copart',
            'lot_number' => (string) fake()->numberBetween(40000000, 79999999),
            'vin' => strtoupper(fake()->bothify('#??##?##?#######')),
            'description' => fake()->randomElement(['2024 TOYOTA CAMRY SE', '2023 NISSAN ALTIMA SV', '2022 HONDA ACCORD EX']),
            'type' => 'prebid', 'phase' => 'preliminary',
            'amount' => $amount, 'max_bid' => $amount + 500, 'current_bid' => $amount,
            'status' => 'active', 'ip_address' => fake()->ipv4(), 'app' => 'extension', 'placed_at' => now(),
        ];
    }
}
