<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_import_rows', function (Blueprint $table) {
            $table->decimal('gst_amount', 14, 2)->nullable()->after('gst_treatment');
        });
    }

    public function down(): void
    {
        Schema::table('book_import_rows', function (Blueprint $table) {
            $table->dropColumn('gst_amount');
        });
    }
};
