<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_documents', function (Blueprint $table): void {
            $table->foreignId('book_transaction_id')
                ->nullable()
                ->after('book_import_id')
                ->constrained('book_transactions')
                ->cascadeOnDelete();

            $table->index(
                ['book_business_id', 'book_transaction_id', 'document_type'],
                'book_documents_transaction_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::table('book_documents', function (Blueprint $table): void {
            $table->dropIndex('book_documents_transaction_lookup');
            $table->dropConstrainedForeignId('book_transaction_id');
        });
    }
};
