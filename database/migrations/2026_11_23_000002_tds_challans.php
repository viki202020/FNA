<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Challan details (BSR code, serial number) for each deposit of TDS, attached to the bank line that paid it. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tds_challans')) {
            Schema::create('tds_challans', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('merchant_id')->index();
                $t->integer('statement_id');                    // the ledger debit to TDS Payable (the deposit)
                $t->string('for_month', 7);                     // the deduction month this deposit is for, YYYY-MM
                $t->string('bsr_code', 10)->nullable();
                $t->string('challan_serial', 12)->nullable();
                $t->decimal('interest', 14, 2)->default(0);
                $t->decimal('fee', 14, 2)->default(0);
                $t->string('note', 300)->nullable();
                $t->integer('updated_by')->nullable();
                $t->timestamps();
                $t->unique(['merchant_id', 'statement_id']);
            });
        }
    }

    public function down()
    {
        // nothing is ever dropped
    }
};
