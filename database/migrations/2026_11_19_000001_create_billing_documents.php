<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 5 - sales invoices (SIV) and purchase bills (PBV).
 * One table holds both (kind = sales | purchase). Nothing existing is changed except that
 * statement.voucher_no is made long enough for numbers like SIV-2425-010.
 */
return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE statement MODIFY voucher_no VARCHAR(30) NULL');

        if (!Schema::hasTable('billing_docs')) {
            Schema::create('billing_docs', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('merchant_id')->index();
                $t->string('kind', 10);                       // sales | purchase
                $t->string('number', 30)->nullable();          // SIV-2425-001 / PBV-2425-001 (given when posted)
                $t->string('fyear', 20)->nullable();
                $t->date('doc_date');
                $t->date('due_date')->nullable();
                $t->integer('party_id')->nullable();           // customer.id (customer or supplier)
                $t->string('party_name', 256)->nullable();
                $t->string('supply', 6)->default('intra');     // intra (CGST+SGST) | inter (IGST)
                $t->decimal('subtotal', 16, 2)->default(0);
                $t->decimal('cgst', 16, 2)->default(0);
                $t->decimal('sgst', 16, 2)->default(0);
                $t->decimal('igst', 16, 2)->default(0);
                $t->decimal('total', 16, 2)->default(0);
                $t->decimal('settled', 16, 2)->default(0);
                $t->string('status', 10)->default('draft');   // draft | open | part | paid | void
                $t->string('reference', 100)->nullable();      // supplier's own bill number for purchases
                $t->text('notes')->nullable();
                $t->unsignedBigInteger('voucher_id')->nullable();
                $t->integer('created_by')->nullable();
                $t->timestamps();
                $t->unique(['merchant_id', 'number']);
            });
        }
        if (!Schema::hasTable('billing_doc_lines')) {
            Schema::create('billing_doc_lines', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('doc_id')->index();
                $t->integer('product_id')->nullable();
                $t->integer('head_id')->nullable();            // expense head for a purchase line
                $t->string('description', 500)->nullable();
                $t->string('hsn', 20)->nullable();
                $t->decimal('qty', 14, 3)->default(1);
                $t->decimal('rate', 16, 2)->default(0);
                $t->decimal('tax_rate', 6, 2)->default(0);
                $t->decimal('amount', 16, 2)->default(0);      // qty x rate, before tax
                $t->timestamps();
            });
        }
        if (!Schema::hasTable('billing_settlements')) {
            Schema::create('billing_settlements', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('doc_id')->index();
                $t->integer('statement_id')->index();          // the bank line that settled it
                $t->decimal('amount', 16, 2);
                $t->string('method', 10);                      // manual | auto
                $t->integer('chosen_by')->nullable();
                $t->timestamps();
            });
        }
        if (!Schema::hasTable('fna_settings')) {
            Schema::create('fna_settings', function (Blueprint $t) {
                $t->integer('merchant_id');
                $t->string('skey', 40);
                $t->string('svalue', 255)->nullable();
                $t->primary(['merchant_id', 'skey']);
            });
        }
    }

    public function down()
    {
        // nothing is ever dropped
    }
};
