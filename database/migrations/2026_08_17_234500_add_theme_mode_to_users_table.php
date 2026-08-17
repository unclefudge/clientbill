<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme_mode', 10)
                ->default('system')
                ->after('default_book_business_id');
        });

        // Preserve the intended ClientBill experience for users that already
        // existed when per-user theme persistence was introduced.
        DB::table('users')
            ->where('default_area', 'billing')
            ->update(['theme_mode' => 'dark']);

        DB::table('users')
            ->where('default_area', 'books')
            ->update(['theme_mode' => 'light']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('theme_mode');
        });
    }
};
