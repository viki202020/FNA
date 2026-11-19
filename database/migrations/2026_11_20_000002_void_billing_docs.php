<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Voiding an invoice or bill keeps it visible, with who voided it, why and when. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('billing_docs', function (Blueprint $t) {
            if (!Schema::hasColumn('billing_docs', 'void_code')) { $t->string('void_code', 30)->nullable(); }
            if (!Schema::hasColumn('billing_docs', 'void_reason')) { $t->string('void_reason', 500)->nullable(); }
            if (!Schema::hasColumn('billing_docs', 'voided_by')) { $t->integer('voided_by')->nullable(); }
            if (!Schema::hasColumn('billing_docs', 'voided_at')) { $t->timestamp('voided_at')->nullable(); }
            if (!Schema::hasColumn('billing_docs', 'void_voucher_id')) { $t->unsignedBigInteger('void_voucher_id')->nullable(); }
        });
    }

    public function down() {}
};
