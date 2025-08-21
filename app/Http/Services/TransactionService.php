<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\TransactionReversalException;

class TransactionService
{
    public function handleDeposit(Wallet $wallet, float $amount): Transaction
    {
        return DB::transaction(function () use ($wallet, $amount) {
            $wallet->balance += $amount;
            $wallet->save();

            return Transaction::create([
                'payee_wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => 'deposit',
            ]);
        });
    }

    public function handleTransfer(Wallet $payerWallet, Wallet $payeeWallet, float $amount): Transaction
    {
        return DB::transaction(function () use ($payerWallet, $payeeWallet, $amount) {
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

    public function handleReversal(Transaction $originalTransaction): Transaction
    {
        return DB::transaction(function () use ($originalTransaction) {
            if ($originalTransaction->status === 'reversed') {
                throw new TransactionReversalException('Esta transação já foi estornada.');
            }

            $payerWallet = Wallet::find($originalTransaction->payee_wallet_id);
            $payeeWallet = Wallet::find($originalTransaction->payer_wallet_id);

            if ($payerWallet && $payeeWallet) { // Caso de Transferência
                if ($payerWallet->balance < $originalTransaction->amount) {
                    throw new InsufficientFundsException('O destinatário não possui saldo para o estorno.');
                }
                $payerWallet->balance -= $originalTransaction->amount;
                $payerWallet->save();

                $payeeWallet->balance += $originalTransaction->amount;
                $payeeWallet->save();
            } else { 
                $payerWallet->balance -= $originalTransaction->amount;
                $payerWallet->save();
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