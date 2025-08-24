<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionReversalTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_reverse_a_deposit()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 50.00]);
        $deposit = Transaction::factory()->create([
            'payee_wallet_id' => $wallet->id,
            'amount' => 50.00,
            'type' => 'deposit'
        ]);

        $response = $this->actingAs($user)->postJson("/api/transactions/{$deposit->id}/reverse");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Transação estornada com sucesso.']);
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 0.00,
        ]);
    }
}