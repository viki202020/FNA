<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lines of an uploaded GSTR-2B file, kept as uploaded, with what they were matched to. Nothing is deleted. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('gstr2b_lines')) {
            Schema::create('gstr2b_lines', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('merchant_id')->index();
                $t->string('batch', 30)->index();
                $t->string('period', 10)->nullable();            // MMYYYY as on the portal
                $t->string('supplier_gstin', 20)->index();
                $t->string('supplier_name', 256)->nullable();
                $t->string('inv_no', 60);
                $t->date('inv_date')->nullable();
                $t->decimal('inv_value', 16, 2)->default(0);
                $t->decimal('taxable', 16, 2)->default(0);
                $t->decimal('igst', 16, 2)->default(0);
                $t->decimal('cgst', 16, 2)->default(0);
                $t->decimal('sgst', 16, 2)->default(0);
                $t->decimal('cess', 16, 2)->default(0);
                $t->string('itc_available', 5)->nullable();      // Y or N, as the portal says
                $t->string('status', 12)->default('missing');    // matched | mismatch | missing | drafted
                $t->string('note', 500)->nullable();
                $t->unsignedBigInteger('doc_id')->nullable();     // the purchase bill it matched, or the draft created from it
                $t->integer('uploaded_by')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down() {}
};
