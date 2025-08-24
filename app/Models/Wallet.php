<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="Wallet",
 * type="object",
 * title="Wallet Model",
 * description="Representa a carteira de um usuário, contendo seu saldo.",
 * @OA\Property(property="id", type="integer", description="ID da carteira", example=1),
 * @OA\Property(property="user_id", type="integer", description="ID do usuário dono da carteira", example=1),
 * @OA\Property(property="balance", type="number", format="float", description="Saldo atual da carteira", example=1234.56),
 * @OA\Property(property="created_at", type="string", format="date-time", description="Data de criação"),
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Data da última atualização"),
 * @OA\Property(property="user", type="object", ref="#/components/schemas/User")
 * )
 */
class Wallet extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'balance'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}