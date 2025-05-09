<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_invoice_delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('do_id');
            $table->timestamps();

            $table->foreign('invoice_id')->references('invoice_id')->on('SalesInvoice')->onDelete('cascade');
            $table->foreign('do_id')->references('do_id')->on('DeliveryOrder')->onDelete('cascade');

            $table->unique(['invoice_id', 'do_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_delivery_orders');
    }
};
