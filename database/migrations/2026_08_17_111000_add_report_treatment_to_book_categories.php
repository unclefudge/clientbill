<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('book_categories', 'report_treatment')) {
            Schema::table('book_categories', function (Blueprint $table) {
                $table->string('report_treatment', 40)
                    ->nullable()
                    ->after('type');
            });
        }

        DB::table('book_categories')
            ->select(['id', 'type', 'name', 'report_treatment'])
            ->orderBy('id')
            ->chunkById(200, function ($categories): void {
                foreach ($categories as $category) {
                    if (filled($category->report_treatment)) {
                        continue;
                    }

                    DB::table('book_categories')
                        ->where('id', $category->id)
                        ->update([
                            'report_treatment' => $this->inferTreatment(
                                (string) $category->type,
                                (string) $category->name,
                            ),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('book_categories', 'report_treatment')) {
            Schema::table('book_categories', function (Blueprint $table) {
                $table->dropColumn('report_treatment');
            });
        }
    }

    protected function inferTreatment(string $type, string $name): string
    {
        $name = strtoupper(trim($name));

        if (preg_match('/\bDISTRIBUT(ION|IONS)?\b/', $name)) {
            return 'distribution_equity';
        }

        if (preg_match('/\bLOAN\b/', $name)) {
            return 'loan_finance';
        }

        if (
            preg_match('/\bSHARE\s+(BUY|SALE)\b/', $name)
            || preg_match('/\bINVESTMENT(S)?\b/', $name)
        ) {
            return 'capital_asset';
        }

        if (
            str_contains($name, 'TAX OFFICE')
            || preg_match('/\bATO\b/', $name)
        ) {
            return 'tax_ato';
        }

        return match ($type) {
            'income' => 'revenue',
            'expense' => 'operating_expense',
            default => 'excluded',
        };
    }
};
