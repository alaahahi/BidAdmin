<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $amount = fake()->numberBetween(2000, 30000);

        return [
            'provider' => 'copart',
            'invoice_number' => (string) fake()->unique()->numberBetween(40000000, 79999999),
            'description' => '2025 TOYOTA CAMRY LE',
            'type' => 'lot', 'status' => 'pending',
            'amount' => $amount, 'amount_due' => $amount,
            'invoice_date' => now()->toDateString(), 'due_date' => now()->addDays(3)->toDateString(),
        ];
    }
}
