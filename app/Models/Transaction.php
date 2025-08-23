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

    public function payerWallet()
    {
        return $this->belongsTo(Wallet::class, 'payer_wallet_id');
    }
    
    public function payeeWallet()
    {
        return $this->belongsTo(Wallet::class, 'payee_wallet_id');
    }
}