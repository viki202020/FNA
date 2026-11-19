<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An uncategorised bank line must not be part of the accounts at all: neither of its two entries
 * (the bank side and the unfinished side) reaches the trial balance, ledgers, balance sheet or profit and loss
 * until the line is categorised. From now on both entries of a bank line carry the same status.
 * Also records HOW each line was categorised (a rule or a person).
 *
 * Existing data: the bank side of every line that is still uncategorised is switched to status 0.
 * Nothing is deleted and no amount changes; the bank statement screen still shows every bank line.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('statement', function (Blueprint $t) {
            if (!Schema::hasColumn('statement', 'cat_source')) { $t->string('cat_source', 10)->nullable(); }   // rule | person
            if (!Schema::hasColumn('statement', 'cat_rule_id')) { $t->integer('cat_rule_id')->nullable(); }
            if (!Schema::hasColumn('statement', 'cat_by')) { $t->integer('cat_by')->nullable(); }
        });
        try { DB::statement('CREATE INDEX statement_voucher_no_idx ON statement (voucher_no)'); } catch (\Throwable $e) { /* already there */ }
        DB::statement("UPDATE statement b JOIN statement c ON c.voucher_no = b.voucher_no AND c.merchant_id = b.merchant_id AND c.id <> b.id AND c.status = 0
                       SET b.status = 0
                       WHERE b.voucher_no IS NOT NULL AND b.bank_id IS NOT NULL AND b.head_id = b.bank_id AND b.status = 1");
    }

    public function down() {}
};
