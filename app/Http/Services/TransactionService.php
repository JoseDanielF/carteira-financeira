<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\TransactionReversalException;

class TransactionService
{
    /**
     */
    public function handleDeposit(Wallet $wallet, float $amount): Transaction
    {
        return DB::transaction(function () use ($wallet, $amount) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            $wallet->balance += $amount;
            $wallet->save();

            return Transaction::create([
                'payee_wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => 'deposit',
            ]);
        });
    }

    /**
     */
    public function handleTransfer(Wallet $payerWallet, Wallet $payeeWallet, float $amount): Transaction
    {
        return DB::transaction(function () use ($payerWallet, $payeeWallet, $amount) {
            $wallets = Wallet::lockForUpdate()->findMany([$payerWallet->id, $payeeWallet->id])->keyBy('id');
            $payerWallet = $wallets[$payerWallet->id];
            $payeeWallet = $wallets[$payeeWallet->id];

            if ($payerWallet->balance < $amount) {
                throw new InsufficientFundsException('Saldo insuficiente para realizar a transferência.');
            }

            $payerWallet->balance -= $amount;
            $payerWallet->save();

            $payeeWallet->balance += $amount;
            $payeeWallet->save();

            return Transaction::create([
                'payer_wallet_id' => $payerWallet->id,
                'payee_wallet_id' => $payeeWallet->id,
                'amount' => $amount,
                'type' => 'transfer',
            ]);
        });
    }

    /**
     */
    public function handleReversal(Transaction $originalTransaction): Transaction
    {
        return DB::transaction(function () use ($originalTransaction) {
            if ($originalTransaction->status === 'reversed') {
                throw new TransactionReversalException('Esta transação já foi estornada.');
            }

            $newPayerWallet = Wallet::lockForUpdate()->findOrFail($originalTransaction->payee_wallet_id);

            if ($originalTransaction->payer_wallet_id) {
                $newPayeeWallet = Wallet::lockForUpdate()->findOrFail($originalTransaction->payer_wallet_id);

                if ($newPayerWallet->balance < $originalTransaction->amount) {
                    throw new InsufficientFundsException('O destinatário original não possui saldo para o estorno.');
                }
                
                $newPayerWallet->balance -= $originalTransaction->amount;
                $newPayeeWallet->balance += $originalTransaction->amount;

                $newPayerWallet->save();
                $newPayeeWallet->save();

            } else {
                $newPayerWallet->balance -= $originalTransaction->amount;
                $newPayerWallet->save();
            }

            $originalTransaction->status = 'reversed';
            $originalTransaction->save();

            return Transaction::create([
                'payer_wallet_id' => $originalTransaction->payee_wallet_id,
                'payee_wallet_id' => $originalTransaction->payer_wallet_id,
                'amount' => $originalTransaction->amount,
                'type' => 'reversal',
                'original_transaction_id' => $originalTransaction->id,
            ]);
        });
    }
}
