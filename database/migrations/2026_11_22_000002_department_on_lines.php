<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A department (cost centre) can be chosen on every invoice and bill line; it travels to the ledger entries. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('billing_doc_lines', function (Blueprint $t) {
            if (!Schema::hasColumn('billing_doc_lines', 'department_id')) { $t->integer('department_id')->nullable(); }
        });
    }

    public function down() {}
};
