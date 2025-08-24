<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Services\TransactionService;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\TransactionReversalException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index()
    {
        return Transaction::latest()->paginate();
    }

    public function show(Transaction $transaction)
    {
        return $transaction;
    }

    public function deposit(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:0.01']);

        try {
            $wallet = $request->user()->wallet;
            $this->transactionService->handleDeposit($wallet, $request->amount);

            return response()->json(['message' => 'Depósito realizado com sucesso!']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Ocorreu um erro durante o depósito.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'payee_wallet_id' => 'required|integer|exists:wallets,id',
            'amount'          => 'required|numeric|min:0.01',
        ]);

        try {
            $payerWallet = $request->user()->wallet;

            if ($payerWallet->id == $request->payee_wallet_id) {
                return response()->json(['message' => 'Você não pode transferir para si mesmo.'], 422);
            }

            $payeeWallet = Wallet::findOrFail($request->payee_wallet_id);

            $this->transactionService->handleTransfer($payerWallet, $payeeWallet, $request->amount);

            return response()->json(['message' => 'Transferência realizada com sucesso!'], 201);
        } catch (InsufficientFundsException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Carteira de destino não encontrada.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Ocorreu um erro durante a transferência.'], 500);
        }
    }

    public function reverse(Transaction $transaction)
    {
        try {
            $this->transactionService->handleReversal($transaction);

            return response()->json(['message' => 'Transação estornada com sucesso.'], 200);
        } catch (TransactionReversalException | InsufficientFundsException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Não foi possível encontrar as carteiras para o estorno.'], 404);
        } catch (\Throwable $e) {
            Log::error('Falha no estorno da transação ' . $transaction->id, ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Ocorreu um erro inesperado durante o estorno.'], 500);
        }
    }

    public function walletTransactions(Request $request)
    {
        $walletId = $request->user()->wallet->id;

        return Transaction::with(['payerWallet.user', 'payeeWallet.user'])
            ->where(function ($query) use ($walletId) {
                $query->where('payer_wallet_id', $walletId)
                    ->orWhere('payee_wallet_id', $walletId);
            })
            ->latest()
            ->paginate(5);
    }


    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return response()->json(null, 204);
    }
}
