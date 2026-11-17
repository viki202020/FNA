<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 1 - voucher foundations.
 *
 * `vouchers`          one header row per voucher number (status Draft/Posted/Reversed/Void, current version).
 * `voucher_versions`  every earlier version of a voucher is kept (nothing is ever deleted).
 * `statement.voucher_id` links ledger lines to their header.
 *
 * Existing vouchers are back-filled as Posted, version 1. No amount in the ledger is changed,
 * so every report shows the same figures as before.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('vouchers')) {
            Schema::create('vouchers', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('merchant_id')->index();
                $t->string('voucher_no', 20);
                $t->string('series', 10)->nullable();
                $t->string('fyear', 100)->nullable();
                $t->string('voucher_date', 30)->nullable();
                $t->string('status', 10)->default('posted');   // draft, posted, reversed, void
                $t->unsignedInteger('version')->default(1);
                $t->text('narration')->nullable();
                $t->string('reference', 100)->nullable();
                $t->unsignedBigInteger('reverses_id')->nullable();     // set on an RJV / SRV
                $t->unsignedBigInteger('reversed_by_id')->nullable();  // set on the original
                $t->integer('created_by')->nullable();
                $t->integer('updated_by')->nullable();
                $t->timestamps();
                $t->unique(['merchant_id', 'voucher_no']);
            });
        }

        if (!Schema::hasTable('voucher_versions')) {
            Schema::create('voucher_versions', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('voucher_id')->index();
                $t->unsignedInteger('version');
                $t->string('action', 20);            // created, edited, reversed, voided, posted
                $t->text('reason')->nullable();
                $t->longText('snapshot')->nullable(); // JSON copy of the lines as they stood
                $t->integer('changed_by')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasColumn('statement', 'voucher_id')) {
            Schema::table('statement', function (Blueprint $t) {
                $t->unsignedBigInteger('voucher_id')->nullable()->index();
            });
        }

        // Back-fill: one Posted header per existing voucher number.
        $rows = DB::table('statement')
            ->whereNotNull('voucher_no')->where('voucher_no', '!=', '')
            ->select('merchant_id', 'voucher_no', DB::raw('MIN(fyear) as fyear'), DB::raw('MIN(st_date) as d'),
                     DB::raw('MIN(created_by) as cb'), DB::raw('MAX(status) as st'))
            ->groupBy('merchant_id', 'voucher_no')->get();
        foreach ($rows as $r) {
            $exists = DB::table('vouchers')->where('merchant_id', $r->merchant_id)->where('voucher_no', $r->voucher_no)->first();
            if (!$exists) {
                $series = preg_match('/^[A-Za-z]+/', $r->voucher_no, $m) ? strtoupper($m[0]) : null;
                $id = DB::table('vouchers')->insertGetId([
                    'merchant_id' => $r->merchant_id, 'voucher_no' => $r->voucher_no, 'series' => $series,
                    'fyear' => $r->fyear, 'voucher_date' => $r->d, 'status' => $r->st ? 'posted' : 'draft',
                    'version' => 1, 'created_by' => $r->cb, 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('voucher_versions')->insert([
                    'voucher_id' => $id, 'version' => 1, 'action' => 'created',
                    'reason' => 'Existing voucher carried over', 'changed_by' => $r->cb,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
        DB::statement("UPDATE statement s JOIN vouchers v ON v.merchant_id=s.merchant_id AND v.voucher_no=s.voucher_no COLLATE utf8mb4_unicode_ci SET s.voucher_id=v.id WHERE s.voucher_id IS NULL");
    }

    public function down()
    {
        // Nothing is ever deleted: the tables are left in place.
    }
};
