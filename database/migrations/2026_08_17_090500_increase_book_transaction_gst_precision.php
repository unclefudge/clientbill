<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_transactions', function (Blueprint $table) {
            // QuickBAS percentage business-use calculations can leave fractions
            // of a cent in GST before the annual/quarterly total is rounded.
            $table->decimal('gst_amount', 14, 4)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('book_transactions', function (Blueprint $table) {
            $table->decimal('gst_amount', 14, 2)->default(0)->change();
        });
    }
};
