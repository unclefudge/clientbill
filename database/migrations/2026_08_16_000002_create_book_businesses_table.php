<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('abn', 20)->nullable();
            $table->boolean('gst_registered')->default(false);
            $table->decimal('gst_rate', 5, 2)->default(10.00);
            $table->string('bas_frequency', 20)->default('quarterly');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('book_business_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_business_id')->constrained('book_businesses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('user');
            $table->timestamps();

            $table->unique(['book_business_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_business_user');
        Schema::dropIfExists('book_businesses');
    }
};
