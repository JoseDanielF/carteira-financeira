<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientFundsException;
use App\Exceptions\TransactionReversalException;
use App\Http\Services\TransactionService;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * @OA\Post(
     * path="/api/deposit",
     * operationId="depositToWallet",
     * tags={"Ações da Carteira"},
     * summary="Realiza um depósito na carteira do usuário autenticado",
     * security={{"sanctum":{}}},
     * @OA\RequestBody(
     * required=true,
     * description="Valor a ser depositado",
     * @OA\JsonContent(
     * required={"amount"},
     * @OA\Property(property="amount", type="number", format="float", example=150.75)
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Depósito bem-sucedido",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Depósito realizado com sucesso!")
     * )
     * ),
     * @OA\Response(response=401, description="Não autenticado"),
     * @OA\Response(response=422, description="Erro de validação (ex: valor inválido)")
     * )
     */
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

    /**
     * @OA\Post(
     * path="/api/transfer",
     * operationId="transferBetweenWallets",
     * tags={"Ações da Carteira"},
     * summary="Transfere um valor da carteira do usuário para outra carteira",
     * security={{"sanctum":{}}},
     * @OA\RequestBody(
     * required=true,
     * description="Dados da transferência",
     * @OA\JsonContent(
     * required={"payee_wallet_id", "amount"},
     * @OA\Property(property="payee_wallet_id", type="integer", description="ID da carteira de destino", example=2),
     * @OA\Property(property="amount", type="number", format="float", description="Valor a ser transferido", example=50.25)
     * )
     * ),
     * @OA\Response(response=201, description="Transferência bem-sucedida"),
     * @OA\Response(response=401, description="Não autenticado"),
     * @OA\Response(response=404, description="Carteira de destino não encontrada"),
     * @OA\Response(response=422, description="Erro de validação (ex: saldo insuficiente)")
     * )
     */
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

    /**
     * @OA\Get(
     * path="/api/wallet/transactions",
     * operationId="getUserWalletTransactions",
     * tags={"Ações da Carteira"},
     * summary="Lista as transações da carteira do usuário autenticado",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="page",
     * in="query",
     * description="Número da página para paginação",
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Response(
     * response=200,
     * description="Lista paginada de transações",
     * @OA\JsonContent(type="object", ref="#/components/schemas/TransactionPaginated")
     * ),
     * @OA\Response(response=401, description="Não autenticado")
     * )
     */
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

    /**
     * @OA\Post(
     * path="/api/transactions/{transaction}/reverse",
     * operationId="reverseTransaction",
     * tags={"Transações"},
     * summary="Estorna (reverte) uma transação existente",
     * description="Apenas o originador da transação (quem depositou ou enviou) pode estornar.",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="transaction",
     * in="path",
     * required=true,
     * description="ID da transação a ser estornada",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(response=200, description="Estorno bem-sucedido"),
     * @OA\Response(response=401, description="Não autenticado"),
     * @OA\Response(response=404, description="Transação não encontrada"),
     * @OA\Response(response=422, description="Erro ao processar estorno (ex: saldo insuficiente para estornar)")
     * )
     */
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

    /**
     * @OA\Get(
     * path="/api/transactions",
     * operationId="listAllTransactions",
     * tags={"Transações"},
     * summary="Lista todas as transações (Admin)",
     * security={{"sanctum":{}}},
     * @OA\Response(
     * response=200,
     * description="Lista paginada de todas as transações",
     * @OA\JsonContent(type="object", ref="#/components/schemas/TransactionPaginated")
     * )
     * )
     */
    public function index()
    {
        return Transaction::latest()->paginate();
    }

    /**
     * @OA\Get(
     * path="/api/transactions/{transaction}",
     * operationId="showTransaction",
     * tags={"Transações"},
     * summary="Exibe uma transação específica",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="transaction",
     * in="path",
     * required=true,
     * description="ID da transação",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Detalhes da transação",
     * @OA\JsonContent(ref="#/components/schemas/Transaction")
     * ),
     * @OA\Response(response=404, description="Transação não encontrada")
     * )
     */
    public function show(Transaction $transaction)
    {
        return $transaction;
    }


    /**
     * @OA\Delete(
     * path="/api/transactions/{transaction}",
     * operationId="deleteTransaction",
     * tags={"Transações"},
     * summary="Deleta uma transação (Admin)",
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="transaction",
     * in="path",
     * required=true,
     * description="ID da transação a ser deletada",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(response=204, description="Transação deletada com sucesso"),
     * @OA\Response(response=404, description="Transação não encontrada")
     * )
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return response()->json(null, 204);
    }
}