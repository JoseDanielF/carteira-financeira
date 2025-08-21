<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TransactionService;
use App\Models\Wallet;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function transfer(Request $request)
    {
        try {
            $payerWallet = auth()->user()->wallet;
            $payeeWallet = Wallet::findOrFail($request->payee_wallet_id);
            
            $transaction = $this->transactionService->handleTransfer(
                $payerWallet,
                $payeeWallet,
                $request->amount
            );

            return response()->json($transaction, 201);
        } catch (InsufficientFundsException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocorreu um erro interno.'], 500);
        }
    }
}
