<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('type')->nullable();  //  ['time', 'hosting', 'domain', 'product', 'custom']
            $table->string('description');
            $table->longText('summary')->nullable();
            $table->decimal('quantity', 8, 2)->default(1); // hours OR item qty OR 1 for fixed
            $table->decimal('rate', 8, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->integer('order')->default(0);
            $table->foreignId('time_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('hosting_id')->nullable()->constrained('hosting')->nullOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
