<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** TDS on a payment or receipt, cash payments, and the link from a settlement to its TDS voucher. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('payment_notes', function (Blueprint $t) {
            if (!Schema::hasColumn('payment_notes', 'tds_amount')) { $t->decimal('tds_amount', 16, 2)->default(0); }
        });
        Schema::table('billing_settlements', function (Blueprint $t) {
            if (!Schema::hasColumn('billing_settlements', 'tds_voucher_id')) { $t->unsignedBigInteger('tds_voucher_id')->nullable(); }
        });
    }

    public function down() {}
};
