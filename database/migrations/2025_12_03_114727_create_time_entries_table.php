<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('activity')->nullable();
            $table->date('date');
            $table->integer('duration')->default(0);
            $table->decimal('rate', 8, 2)->nullable();
            $table->enum('entry_type', ['regular', 'prebill', 'payback'])->default('regular');
            $table->boolean('billable')->default(true);
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->time('start')->nullable();
            $table->time('end')->nullable();
            $table->string('import')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
