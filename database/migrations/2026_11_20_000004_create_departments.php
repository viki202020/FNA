<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The list of departments a voucher line can be tagged with. Nothing is deleted: a department is switched off. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('merchant_id')->index();
                $t->string('name', 100);
                $t->boolean('active')->default(true);
                $t->integer('created_by')->nullable();
                $t->timestamps();
                $t->unique(['merchant_id', 'name']);
            });
        }
    }

    public function down() {}
};
