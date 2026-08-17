<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_financial_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_business_id')->constrained('book_businesses')->cascadeOnDelete();
            $table->string('label', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('open');
            $table->timestamps();

            $table->unique(['book_business_id', 'start_date', 'end_date'], 'book_fy_business_dates_unique');
        });

        Schema::create('book_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_business_id')->constrained('book_businesses')->cascadeOnDelete();
            $table->string('name');
            $table->string('institution')->nullable();
            $table->string('account_name')->nullable();
            $table->string('last_four', 4)->nullable();
            $table->json('import_format')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('book_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_business_id')->constrained('book_businesses')->cascadeOnDelete();
            $table->string('type', 20); // income | expense | other
            $table->string('name');
            $table->string('default_gst_treatment', 30)->default('gst_applicable');
            $table->decimal('default_business_use_percentage', 5, 2)->default(100.00);
            $table->string('default_purchase_type', 20)->default('non_capital');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['book_business_id', 'type', 'name']);
        });

        Schema::create('book_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_business_id')->constrained('book_businesses')->cascadeOnDelete();
            $table->foreignId('book_bank_account_id')->nullable()->constrained('book_bank_accounts')->nullOnDelete();
            $table->string('type', 30)->default('bank');
            $table->string('status', 20)->default('draft');
            $table->string('original_filename')->nullable();
            $table->string('disk')->nullable();
            $table->string('file_path')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('included_count')->default(0);
            $table->unsignedInteger('excluded_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('book_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_business_id')->constrained('book_businesses')->cascadeOnDelete();
            $table->foreignId('book_bank_account_id')->nullable()->constrained('book_bank_accounts')->nullOnDelete();
            $table->foreignId('book_category_id')->nullable()->constrained('book_categories')->nullOnDelete();
            $table->foreignId('book_import_id')->nullable()->constrained('book_imports')->nullOnDelete();
            $table->date('transaction_date');
            $table->decimal('amount', 14, 2);
            $table->decimal('business_use_percentage', 5, 2)->default(100.00);
            $table->decimal('business_amount', 14, 2)->nullable();
            $table->decimal('net_amount', 14, 2)->nullable();
            $table->decimal('gst_amount', 14, 2)->default(0);
            $table->string('gst_treatment', 30)->default('gst_applicable');
            $table->string('purchase_type', 20)->nullable();
            $table->string('source', 30)->default('manual');
            $table->string('payment_source', 50)->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['book_business_id', 'transaction_date']);
            $table->index(['book_business_id', 'book_category_id']);
        });

        Schema::create('book_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_import_id')->constrained('book_imports')->cascadeOnDelete();
            $table->foreignId('book_transaction_id')->nullable()->constrained('book_transactions')->nullOnDelete();
            $table->foreignId('suggested_book_category_id')->nullable()->constrained('book_categories')->nullOnDelete();
            $table->unsignedInteger('row_number');
            $table->date('transaction_date')->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('fingerprint', 64)->nullable()->index();
            $table->string('status', 30)->default('pending');
            $table->unsignedTinyInteger('suggestion_confidence')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['book_import_id', 'row_number']);
        });

        Schema::create('book_category_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_business_id')->constrained('book_businesses')->cascadeOnDelete();
            $table->foreignId('book_category_id')->constrained('book_categories')->cascadeOnDelete();
            $table->string('match_type', 20)->default('contains');
            $table->string('match_value');
            $table->string('gst_treatment', 30)->nullable();
            $table->decimal('business_use_percentage', 5, 2)->nullable();
            $table->unsignedInteger('times_confirmed')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('book_bas_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_business_id')->constrained('book_businesses')->cascadeOnDelete();
            $table->foreignId('book_financial_year_id')->nullable()->constrained('book_financial_years')->nullOnDelete();
            $table->string('period_type', 20)->default('quarterly');
            $table->string('period_label', 40);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('g1_total_sales', 14, 2)->default(0);
            $table->decimal('g2_export_sales', 14, 2)->default(0);
            $table->decimal('g3_gst_free_sales', 14, 2)->default(0);
            $table->decimal('g10_capital_purchases', 14, 2)->default(0);
            $table->decimal('g11_non_capital_purchases', 14, 2)->default(0);
            $table->decimal('gst_1a', 14, 2)->default(0);
            $table->decimal('gst_1b', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2)->default(0);
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('lodged_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['book_business_id', 'start_date', 'end_date', 'period_type'], 'book_bas_business_period_unique');
        });

        Schema::create('book_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_business_id')->constrained('book_businesses')->cascadeOnDelete();
            $table->foreignId('book_financial_year_id')->nullable()->constrained('book_financial_years')->nullOnDelete();
            $table->foreignId('book_bas_period_id')->nullable()->constrained('book_bas_periods')->nullOnDelete();
            $table->foreignId('book_import_id')->nullable()->constrained('book_imports')->nullOnDelete();
            $table->string('document_type', 40);
            $table->string('period')->nullable();
            $table->string('original_filename');
            $table->string('disk')->default('books');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_documents');
        Schema::dropIfExists('book_bas_periods');
        Schema::dropIfExists('book_category_rules');
        Schema::dropIfExists('book_import_rows');
        Schema::dropIfExists('book_transactions');
        Schema::dropIfExists('book_imports');
        Schema::dropIfExists('book_categories');
        Schema::dropIfExists('book_bank_accounts');
        Schema::dropIfExists('book_financial_years');
    }
};
