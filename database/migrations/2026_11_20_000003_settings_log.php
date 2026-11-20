<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Every change to a posting head setting is recorded: old, new, who, when. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('fna_settings_log')) {
            Schema::create('fna_settings_log', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('merchant_id')->index();
                $t->string('skey', 40);
                $t->integer('old_head')->nullable();
                $t->integer('new_head')->nullable();
                $t->integer('changed_by')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down() {}
};
