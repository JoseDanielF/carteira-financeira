<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payer_wallet_id')->nullable()->constrained('wallets');
            $table->foreignId('payee_wallet_id')->constrained('wallets');
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['deposit', 'transfer', 'reversal']);
            $table->enum('status', ['completed', 'reversed'])->default('completed');
            $table->foreignId('original_transaction_id')->nullable()->constrained('transactions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
