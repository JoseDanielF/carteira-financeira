<?php

namespace Database\Factories;

use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payer_wallet_id' => Wallet::factory(),
            'payee_wallet_id' => Wallet::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'type' => 'transfer',
            'status' => 'completed',
        ];
    }
}