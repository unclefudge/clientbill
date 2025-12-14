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
        Schema::table('domains', function (Blueprint $table) {
            $table->date('last_renewed')->nullable()->after('date');
            $table->date('next_renewal')->nullable()->after('last_renewed');
        });

        Schema::table('hosting', function (Blueprint $table) {
            $table->date('last_renewed')->nullable()->after('date');
            $table->date('next_renewal')->nullable()->after('last_renewed');
            $table->integer('renewal')->default(1)->after('last_renewed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn(['last_renewed', 'next_renewal']);
        });

        Schema::table('hosting', function (Blueprint $table) {
            $table->dropColumn(['last_renewed', 'next_renewal', 'renewal']);
        });
    }
};
