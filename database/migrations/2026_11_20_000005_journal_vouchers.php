<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 3: the manual journal voucher.
 * Drafts keep their lines in `voucher_draft_lines`, so a draft never touches the ledger. Posting copies
 * the lines into `statement` (with department and party) in one step and the draft lines stay as history.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('statement', function (Blueprint $t) {
            if (!Schema::hasColumn('statement', 'department_id')) { $t->integer('department_id')->nullable(); }
            if (!Schema::hasColumn('statement', 'party_id')) { $t->integer('party_id')->nullable(); }
        });
        if (!Schema::hasTable('voucher_draft_lines')) {
            Schema::create('voucher_draft_lines', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('voucher_id')->index();
                $t->integer('line_no');
                $t->integer('head_id')->nullable();
                $t->string('narration', 500)->nullable();
                $t->integer('department_id')->nullable();
                $t->integer('party_id')->nullable();
                $t->decimal('debit', 16, 2)->default(0);
                $t->decimal('credit', 16, 2)->default(0);
                $t->timestamps();
            });
        }
    }

    public function down() {}
};
