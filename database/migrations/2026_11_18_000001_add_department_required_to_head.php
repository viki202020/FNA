<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A head can require a department on every voucher line that uses it. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('head', 'department_required')) {
            Schema::table('head', function (Blueprint $t) {
                $t->tinyInteger('department_required')->default(0);
            });
        }
    }

    public function down()
    {
        // Nothing is ever deleted: the column is left in place.
    }
};
