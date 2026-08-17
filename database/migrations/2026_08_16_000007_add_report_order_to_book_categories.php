<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_categories', function (Blueprint $table) {
            $table->unsignedInteger('report_order')->nullable()->after('name');
        });

        $businessIds = DB::table('book_categories')
            ->select('book_business_id')
            ->distinct()
            ->pluck('book_business_id');

        foreach ($businessIds as $businessId) {
            $categoryIds = DB::table('book_categories')
                ->where('book_business_id', $businessId)
                ->orderBy('type')
                ->orderBy('name')
                ->orderBy('id')
                ->pluck('id');

            foreach ($categoryIds as $index => $categoryId) {
                DB::table('book_categories')
                    ->where('id', $categoryId)
                    ->update(['report_order' => ($index + 1) * 10]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('book_categories', function (Blueprint $table) {
            $table->dropColumn('report_order');
        });
    }
};
