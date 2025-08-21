<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'payer_wallet_id',
        'payee_wallet_id',
        'amount',
        'type',
        'status',
        'original_transaction_id',
    ];
}