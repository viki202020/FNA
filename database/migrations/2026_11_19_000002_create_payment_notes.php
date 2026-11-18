<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Payment sent" / "Payment received" notes: a person's statement that money has moved,
 * kept against an invoice or bill. They post nothing to the books; the bank line does that
 * once it arrives and a person confirms the match.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('payment_notes')) {
            Schema::create('payment_notes', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('merchant_id')->index();
                $t->unsignedBigInteger('doc_id')->index();
                $t->date('pay_date');
                $t->decimal('amount', 16, 2);
                $t->string('bank_name', 100)->nullable();
                $t->string('reference', 100)->nullable();     // UTR or cheque number
                $t->string('status', 10)->default('sent');   // sent | matched | withdrawn
                $t->string('reason', 500)->nullable();         // required when withdrawn
                $t->integer('created_by')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down() {}
};
