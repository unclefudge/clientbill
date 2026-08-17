<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_imports', function (Blueprint $table) {
            $table->json('format_snapshot')->nullable()->after('file_path');
        });

        Schema::table('book_import_rows', function (Blueprint $table) {
            $table->foreignId('book_category_id')
                ->nullable()
                ->after('suggested_book_category_id')
                ->constrained('book_categories')
                ->nullOnDelete();
            $table->decimal('balance', 14, 2)->nullable()->after('amount');
            $table->timestamp('confirmed_at')->nullable()->after('status');
            $table->string('gst_treatment', 30)->nullable()->after('confirmed_at');
            $table->decimal('business_use_percentage', 5, 2)->nullable()->after('gst_treatment');
            $table->string('purchase_type', 20)->nullable()->after('business_use_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('book_import_rows', function (Blueprint $table) {
            $table->dropForeign(['book_category_id']);
            $table->dropColumn([
                'book_category_id',
                'balance',
                'confirmed_at',
                'gst_treatment',
                'business_use_percentage',
                'purchase_type',
            ]);
        });

        Schema::table('book_imports', function (Blueprint $table) {
            $table->dropColumn('format_snapshot');
        });
    }
};
