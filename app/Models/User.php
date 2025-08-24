<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="User",
 * type="object",
 * title="User Model",
 * description="Representa um usuário no sistema.",
 * @OA\Property(property="id", type="integer", description="ID do usuário", example=1),
 * @OA\Property(property="name", type="string", description="Nome do usuário", example="José da Silva"),
 * @OA\Property(property="email", type="string", format="email", description="Email do usuário", example="jose.silva@example.com"),
 * @OA\Property(property="email_verified_at", type="string", format="date-time", description="Data de verificação do email", example=null),
 * @OA\Property(property="created_at", type="string", format="date-time", description="Data de criação"),
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Data da última atualização")
 * )
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }
}