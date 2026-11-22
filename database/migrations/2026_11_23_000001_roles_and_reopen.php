<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two kinds of login for a company: admin (can close books, re-open an earlier year and change setup)
 * and accountant (day-to-day work). Existing logins stay admins. A second login points at the company's
 * main login through company_id. Also the log of years re-opened by an admin. Nothing is dropped.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('merchant', function (Blueprint $t) {
            if (!Schema::hasColumn('merchant', 'role')) { $t->string('role', 12)->default('admin'); }
            if (!Schema::hasColumn('merchant', 'company_id')) { $t->integer('company_id')->nullable(); }
        });
        if (!Schema::hasTable('financial_year_reopen')) {
            Schema::create('financial_year_reopen', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('merchant_id')->index();
                $t->string('financial_year', 20);
                $t->string('reason', 500);
                $t->integer('reopened_by');
                $t->timestamp('reopened_at')->nullable();
                $t->timestamp('closed_again_at')->nullable();
                $t->integer('closed_again_by')->nullable();
            });
        }
    }

    public function down()
    {
        // nothing is ever dropped
    }
};
