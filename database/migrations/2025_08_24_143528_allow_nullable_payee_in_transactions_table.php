<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AllowNullablePayeeInTransactionsTable extends Migration
{
      /**
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('payee_wallet_id')->nullable()->change();
        });
    }

    /**
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('payee_wallet_id')->nullable(false)->change();
        });
    }
}
