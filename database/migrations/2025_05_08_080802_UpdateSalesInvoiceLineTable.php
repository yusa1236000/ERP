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
        Schema::table('SalesInvoiceLine', function (Blueprint $table) {
            // Add do_line_id column after the so_line_id column if it exists
            // If so_line_id doesn't exist, just add do_line_id
            if (Schema::hasColumn('SalesInvoiceLine', 'so_line_id')) {
                $table->unsignedBigInteger('do_line_id')->nullable()->after('so_line_id');
            } else {
                $table->unsignedBigInteger('do_line_id')->nullable();
            }

            // Add foreign key constraint
            $table->foreign('do_line_id')->references('line_id')->on('DOLine')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('SalesInvoiceLine', function (Blueprint $table) {
            $table->dropForeign(['do_line_id']);
            $table->dropColumn('do_line_id');
        });
    }
};
