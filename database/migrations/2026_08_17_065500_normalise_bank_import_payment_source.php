<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('book_transactions')
            ->where('source', 'bank_import')
            ->update([
                'payment_source' => 'Business bank account',
            ]);
    }

    public function down(): void
    {
        // Restore the original bank-account label where the imported transaction
        // still has a linked bank account. Rows without one keep the generic label.
        $accounts = DB::table('book_bank_accounts')
            ->pluck('name', 'id');

        foreach ($accounts as $accountId => $accountName) {
            DB::table('book_transactions')
                ->where('source', 'bank_import')
                ->where('book_bank_account_id', $accountId)
                ->update([
                    'payment_source' => $accountName,
                ]);
        }
    }
};
