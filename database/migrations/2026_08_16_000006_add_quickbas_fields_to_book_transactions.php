<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_transactions', function (Blueprint $table) {
            // Business-use calculations can legitimately land on fractions of a cent
            // (for example 25% of $2,017.50 = $504.375). Keep the precision so
            // historical QuickBAS totals reproduce correctly when rows are summed.
            $table->decimal('business_amount', 14, 4)->nullable()->change();
            $table->string('sale_type', 20)->nullable()->after('gst_treatment');
        });
    }

    public function down(): void
    {
        Schema::table('book_transactions', function (Blueprint $table) {
            $table->dropColumn('sale_type');
            $table->decimal('business_amount', 14, 2)->nullable()->change();
        });
    }
};
