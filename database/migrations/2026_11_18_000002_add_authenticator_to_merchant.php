<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Authenticator-app (TOTP) sign-in: one secret per user, plus one-time recovery codes. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('merchant', function (Blueprint $t) {
            if (!Schema::hasColumn('merchant', 'totp_secret')) { $t->string('totp_secret', 64)->nullable(); }
            if (!Schema::hasColumn('merchant', 'totp_enabled')) { $t->tinyInteger('totp_enabled')->default(0); }
            if (!Schema::hasColumn('merchant', 'totp_last_step')) { $t->bigInteger('totp_last_step')->default(0); }
            if (!Schema::hasColumn('merchant', 'recovery_codes')) { $t->text('recovery_codes')->nullable(); } // JSON of sha256 hashes
        });
    }

    public function down()
    {
        // Nothing is ever deleted: the columns are left in place.
    }
};
