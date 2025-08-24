<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="Transaction",
 * type="object",
 * title="Transaction Model",
 * description="Representa uma transação financeira no sistema.",
 * @OA\Property(property="id", type="integer", description="ID da transação", example=1),
 * @OA\Property(property="payer_wallet_id", type="integer", nullable=true, description="ID da carteira do pagador", example=1),
 * @OA\Property(property="payee_wallet_id", type="integer", nullable=true, description="ID da carteira do recebedor", example=2),
 * @OA\Property(property="amount", type="number", format="float", description="Valor da transação", example=100.50),
 * @OA\Property(property="type", type="string", description="Tipo da transação", enum={"deposit", "transfer", "reversal"}, example="transfer"),
 * @OA\Property(property="status", type="string", description="Status da transação", enum={"completed", "reversed"}, example="completed"),
 * @OA\Property(property="original_transaction_id", type="integer", nullable=true, description="ID da transação original (para estornos)", example=null),
 * @OA\Property(property="created_at", type="string", format="date-time", description="Data de criação"),
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Data da última atualização"),
 * @OA\Property(property="payer_wallet", type="object", ref="#/components/schemas/Wallet"),
 * @OA\Property(property="payee_wallet", type="object", ref="#/components/schemas/Wallet")
 * )
 *
 * @OA\Schema(
 * schema="TransactionPaginated",
 * type="object",
 * title="Transaction Paginated Response",
 * @OA\Property(property="current_page", type="integer", example=1),
 * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Transaction")),
 * @OA\Property(property="first_page_url", type="string", example="http://localhost/api/transactions?page=1"),
 * @OA\Property(property="from", type="integer", example=1),
 * @OA\Property(property="last_page", type="integer", example=5),
 * @OA\Property(property="last_page_url", type="string", example="http://localhost/api/transactions?page=5"),
 * @OA\Property(property="links", type="array", @OA\Items(type="object")),
 * @OA\Property(property="next_page_url", type="string", nullable=true, example="http://localhost/api/transactions?page=2"),
 * @OA\Property(property="path", type="string", example="http://localhost/api/transactions"),
 * @OA\Property(property="per_page", type="integer", example=15),
 * @OA\Property(property="prev_page_url", type="string", nullable=true, example=null),
 * @OA\Property(property="to", type="integer", example=15),
 * @OA\Property(property="total", type="integer", example=75)
 * )
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payer_wallet_id',
        'payee_wallet_id',
        'amount',
        'type',
        'status',
        'original_transaction_id',
    ];

    public function payerWallet()
    {
        return $this->belongsTo(Wallet::class, 'payer_wallet_id');
    }
    
    public function payeeWallet()
    {
        return $this->belongsTo(Wallet::class, 'payee_wallet_id');
    }
}