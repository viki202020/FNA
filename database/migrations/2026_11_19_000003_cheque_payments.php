<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment mode and cheque details on the payment entry. The cheque register is a view of these rows.
 * A cheque posts on the day it is handed over; the bank line later only clears it.
 */
return new class extends Migration
{
    public function up()
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE payment_notes MODIFY status VARCHAR(15) NOT NULL DEFAULT 'sent'");
        Schema::table('payment_notes', function (Blueprint $t) {
            if (!Schema::hasColumn('payment_notes', 'mode'))       { $t->string('mode', 10)->nullable(); }       // Cheque, NEFT, RTGS, IMPS, UPI, Cash, Other
            if (!Schema::hasColumn('payment_notes', 'cheque_no'))  { $t->string('cheque_no', 30)->nullable()->index(); }
            if (!Schema::hasColumn('payment_notes', 'bank_head_id')) { $t->integer('bank_head_id')->nullable(); }
            if (!Schema::hasColumn('payment_notes', 'voucher_id')) { $t->unsignedBigInteger('voucher_id')->nullable(); }
            if (!Schema::hasColumn('payment_notes', 'cleared_on')) { $t->date('cleared_on')->nullable(); }
            if (!Schema::hasColumn('payment_notes', 'statement_id')) { $t->integer('statement_id')->nullable(); }
        });
    }

    public function down() {}
};
