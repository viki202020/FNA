<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reversing a payment or receipt. Nothing is deleted: a reversal keeps the original and records
 * who reversed it, why (a code from a fixed list, plus typed words for "Others"), and when.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('payment_reversals')) {
            Schema::create('payment_reversals', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('merchant_id')->index();
                $t->unsignedBigInteger('doc_id')->index();
                $t->unsignedBigInteger('note_id')->nullable();         // cheque / payment note reversed
                $t->integer('statement_id')->nullable();               // bank line returned to Uncategorised
                $t->decimal('amount', 16, 2);
                $t->string('reason_code', 30);
                $t->string('reason_text', 500)->nullable();
                $t->unsignedBigInteger('reversal_voucher_id')->nullable();
                $t->integer('reversed_by')->nullable();
                $t->timestamps();
            });
        }
        Schema::table('billing_settlements', function (Blueprint $t) {
            if (!Schema::hasColumn('billing_settlements', 'reversed_at')) { $t->timestamp('reversed_at')->nullable(); }
            if (!Schema::hasColumn('billing_settlements', 'reversed_by')) { $t->integer('reversed_by')->nullable(); }
        });
    }

    public function down() {}
};
