<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Planned amount per account per month, and a log of every change to a planned amount. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('budgets')) {
            Schema::create('budgets', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('merchant_id')->index();
                $t->string('fyear', 20);
                $t->integer('head_id');
                $t->string('ym', 7);                           // month, YYYY-MM
                $t->decimal('amount', 16, 2)->default(0);
                $t->integer('updated_by')->nullable();
                $t->timestamps();
                $t->unique(['merchant_id', 'fyear', 'head_id', 'ym']);
            });
        }
        if (!Schema::hasTable('budget_log')) {
            Schema::create('budget_log', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('merchant_id')->index();
                $t->string('fyear', 20);
                $t->integer('head_id');
                $t->string('ym', 7);
                $t->decimal('old_amount', 16, 2)->nullable();
                $t->decimal('new_amount', 16, 2);
                $t->string('source', 10);                      // screen | upload
                $t->integer('changed_by')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down()
    {
        // nothing is ever dropped
    }
};
