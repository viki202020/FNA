<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Customers and suppliers are real party records: PAN, and the TDS section, kept alongside GSTIN, TDS applicability and rate. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('customer', function (Blueprint $t) {
            if (!Schema::hasColumn('customer', 'pan')) { $t->string('pan', 20)->nullable(); }
            if (!Schema::hasColumn('customer', 'tds_section')) { $t->string('tds_section', 12)->nullable(); }
        });
    }

    public function down() {}
};
