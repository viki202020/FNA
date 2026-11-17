<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rule audit: for every uploaded bank line, which rules fitted and what happened.
 * outcome: none (no rule fitted), auto (one account, applied), conflict (rules pointed to different
 * accounts - waits for the user), resolved (user chose). Rows are never deleted.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('rule_audit')) {
            Schema::create('rule_audit', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('merchant_id')->index();
                $t->string('batch', 40)->index();           // one per upload
                $t->string('voucher_no', 20)->index();
                $t->string('fyear', 100)->nullable();
                $t->string('line_date', 30)->nullable();
                $t->string('description', 500)->nullable();
                $t->decimal('amount', 15, 2)->default(0);
                $t->string('direction', 10)->nullable();    // Debit / Credit (as the bank shows it)
                $t->text('matched')->nullable();            // JSON: every rule that fitted and its account
                $t->string('matched_key', 255)->nullable()->index(); // sorted rule ids, to group identical clashes
                $t->string('outcome', 12)->index();
                $t->unsignedInteger('applied_rule_id')->nullable();
                $t->unsignedInteger('chosen_rule_id')->nullable();
                $t->string('head_id', 11)->nullable();      // account the line went to
                $t->integer('created_by')->nullable();
                $t->integer('decided_by')->nullable();
                $t->timestamp('decided_at')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down()
    {
        // Nothing is ever deleted: the table is left in place.
    }
};
