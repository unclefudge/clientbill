<?php

namespace Tests\Unit\Services\Books;

use App\Models\BookCategory;
use App\Models\BookTransaction;
use App\Services\Books\BookProfitLossReport;
use PHPUnit\Framework\TestCase;

class BookProfitLossReportTest extends TestCase
{
    public function test_it_includes_only_revenue_and_operating_expenses_in_profit(): void
    {
        $transactions = collect([
            $this->transaction(
                amount: 110,
                businessAmount: 110,
                gstAmount: 10,
                category: new BookCategory([
                    'id' => 1,
                    'type' => 'income',
                    'name' => 'CAPE COD',
                    'report_treatment' => 'revenue',
                ]),
            ),
            $this->transaction(
                amount: -55,
                businessAmount: -55,
                gstAmount: 5,
                category: new BookCategory([
                    'id' => 2,
                    'type' => 'expense',
                    'name' => 'SOFTWARE',
                    'report_treatment' => 'operating_expense',
                ]),
            ),
            $this->transaction(
                amount: -1100,
                businessAmount: -1100,
                gstAmount: 100,
                purchaseType: 'capital',
                category: new BookCategory([
                    'id' => 3,
                    'type' => 'expense',
                    'name' => 'IT EQUIPMENT',
                    'report_treatment' => 'operating_expense',
                ]),
            ),
            $this->transaction(
                amount: -5000,
                businessAmount: -5000,
                gstAmount: 0,
                category: new BookCategory([
                    'id' => 4,
                    'type' => 'expense',
                    'name' => 'DISTRIBUTIONS',
                    'report_treatment' => 'distribution_equity',
                ]),
            ),
        ]);

        $report = (new BookProfitLossReport())->calculateTransactions($transactions);

        $this->assertSame(100.0, $report['revenue']['total']);
        $this->assertSame(50.0, $report['operating_expenses']['total']);
        $this->assertSame(50.0, $report['net_profit']);

        $this->assertArrayHasKey('capital_asset', $report['outside_pnl']);
        $this->assertArrayHasKey('distribution_equity', $report['outside_pnl']);
        $this->assertSame(2, $report['pnl_transaction_count']);
        $this->assertSame(2, $report['outside_transaction_count']);
    }

    public function test_category_defaults_identify_historical_non_pnl_categories(): void
    {
        $this->assertSame('revenue', BookCategory::inferReportTreatment('income', 'CAPE COD'));
        $this->assertSame('loan_finance', BookCategory::inferReportTreatment('income', 'LOAN'));
        $this->assertSame('capital_asset', BookCategory::inferReportTreatment('expense', 'SHARE BUY'));
        $this->assertSame('capital_asset', BookCategory::inferReportTreatment('income', 'SHARE SALE'));
        $this->assertSame('distribution_equity', BookCategory::inferReportTreatment('expense', 'DISTRIBUTIONS'));
        $this->assertSame('tax_ato', BookCategory::inferReportTreatment('expense', 'TAX OFFICE'));
        $this->assertSame('operating_expense', BookCategory::inferReportTreatment('expense', 'WEB HOSTING'));
    }

    protected function transaction(
        float $amount,
        float $businessAmount,
        float $gstAmount,
        BookCategory $category,
        ?string $purchaseType = null,
    ): BookTransaction {
        $transaction = new BookTransaction([
            'amount' => $amount,
            'business_amount' => $businessAmount,
            'gst_amount' => $gstAmount,
            'purchase_type' => $purchaseType,
        ]);

        $transaction->book_category_id = (int) $category->id;
        $transaction->setRelation('category', $category);

        return $transaction;
    }
}
