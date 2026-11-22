<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link with Inventorise: what it sends (purchase orders, sales orders, bills, invoices) is kept as received,
 * order records that post nothing, and the supplier/customer matches you confirmed. Nothing is dropped.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('link_inbox')) {
            Schema::create('link_inbox', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('merchant_id')->index();
                $t->string('type', 20);                          // purchase_order | sales_order | purchase_bill | sales_invoice
                $t->string('ref', 60);                           // Inventorise's own number
                $t->longText('payload');
                $t->string('party_key', 80)->nullable();
                $t->string('status', 10)->default('held');       // applied | held | error
                $t->string('note', 500)->nullable();
                $t->unsignedBigInteger('order_id')->nullable();
                $t->unsignedBigInteger('doc_id')->nullable();
                $t->integer('hits')->default(1);
                $t->timestamps();
                $t->unique(['merchant_id', 'type', 'ref']);
            });
        }
        if (!Schema::hasTable('link_history')) {
            Schema::create('link_history', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('inbox_id')->index();
                $t->longText('payload');
                $t->timestamp('received_at')->nullable();
            });
        }
        if (!Schema::hasTable('party_links')) {
            Schema::create('party_links', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('merchant_id');
                $t->string('ext_key', 80);                       // e.g. supplier:12 or customer:40 in Inventorise
                $t->integer('customer_id');                      // FNA party
                $t->integer('confirmed_by')->nullable();
                $t->timestamps();
                $t->unique(['merchant_id', 'ext_key']);
            });
        }
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('merchant_id')->index();
                $t->string('kind', 10);                          // purchase | sales
                $t->string('ref', 60);
                $t->string('status', 20)->nullable();
                $t->integer('party_id')->nullable();
                $t->string('party_name', 256)->nullable();
                $t->date('order_date')->nullable();
                $t->date('expected_date')->nullable();
                $t->text('notes')->nullable();
                $t->decimal('subtotal', 16, 2)->default(0);
                $t->decimal('tax', 16, 2)->default(0);
                $t->decimal('total', 16, 2)->default(0);
                $t->timestamps();
                $t->unique(['merchant_id', 'kind', 'ref']);
            });
        }
        if (!Schema::hasTable('order_lines')) {
            Schema::create('order_lines', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('order_id')->index();
                $t->string('description', 500)->nullable();
                $t->string('sku', 60)->nullable();
                $t->string('hsn', 20)->nullable();
                $t->decimal('qty', 14, 3)->default(0);
                $t->decimal('rate', 16, 2)->default(0);
                $t->decimal('tax_rate', 6, 2)->default(0);
                $t->decimal('amount', 16, 2)->default(0);
                $t->timestamps();
            });
        }
        Schema::table('billing_docs', function (Blueprint $t) {
            if (!Schema::hasColumn('billing_docs', 'order_id')) { $t->unsignedBigInteger('order_id')->nullable(); }
            if (!Schema::hasColumn('billing_docs', 'source')) { $t->string('source', 12)->nullable(); }
            if (!Schema::hasColumn('billing_docs', 'ext_ref')) { $t->string('ext_ref', 60)->nullable(); }
        });
    }

    public function down()
    {
        // nothing is ever dropped
    }
};
