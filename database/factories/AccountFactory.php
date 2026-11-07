<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provider' => 'copart',
            'region' => 'USA',
            'type' => 'ENTERPRISE_BIDDER',
            'category' => 'A',
            'username' => (string) fake()->unique()->numberBetween(100000, 999999),
            'password' => 'secret-'.fake()->password(8, 12),
            'company' => strtoupper(fake()->company()),
            'balance_due' => 0,
            'status' => 'active',
            'action_status' => 'ok',
            'tags' => [],
            'last_synced_at' => now(),
        ];
    }
}
