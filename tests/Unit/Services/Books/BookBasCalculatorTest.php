<?php

namespace Tests\Unit\Services\Books;

use App\Models\BookTransaction;
use App\Services\Books\BookBasCalculator;
use PHPUnit\Framework\TestCase;

class BookBasCalculatorTest extends TestCase
{
    public function test_it_calculates_full_bas_labels_from_stored_transaction_values(): void
    {
        $transactions = collect([
            new BookTransaction([
                'amount' => 110,
                'business_amount' => 110,
                'gst_amount' => 10,
                'gst_treatment' => 'gst_applicable',
                'sale_type' => 'gst',
            ]),
            new BookTransaction([
                'amount' => 50,
                'business_amount' => 50,
                'gst_amount' => 0,
                'gst_treatment' => 'gst_free',
                'sale_type' => 'gst_free',
            ]),
            new BookTransaction([
                'amount' => 30,
                'business_amount' => 30,
                'gst_amount' => 0,
                'gst_treatment' => 'gst_free',
                'sale_type' => 'export',
            ]),
            new BookTransaction([
                'amount' => -220,
                'business_amount' => -220,
                'gst_amount' => 20,
                'gst_treatment' => 'gst_applicable',
                'purchase_type' => 'non_capital',
            ]),
            new BookTransaction([
                'amount' => -1100,
                'business_amount' => -1100,
                'gst_amount' => 100,
                'gst_treatment' => 'gst_applicable',
                'purchase_type' => 'capital',
            ]),
            new BookTransaction([
                'amount' => -1000,
                'business_use_percentage' => 25,
                'business_amount' => -250,
                'gst_amount' => 22.73,
                'gst_treatment' => 'gst_applicable',
                'purchase_type' => 'non_capital',
            ]),
            new BookTransaction([
                'amount' => 999,
                'business_amount' => 999,
                'gst_amount' => 90,
                'gst_treatment' => 'excluded',
                'sale_type' => 'excluded',
            ]),
        ]);

        $bas = (new BookBasCalculator())->calculateTransactions($transactions);

        $this->assertSame(190.0, $bas['g1_total_sales']);
        $this->assertSame(30.0, $bas['g2_export_sales']);
        $this->assertSame(50.0, $bas['g3_gst_free_sales']);
        $this->assertSame(1100.0, $bas['g10_capital_purchases']);
        $this->assertSame(470.0, $bas['g11_non_capital_purchases']);
        $this->assertSame(10.0, $bas['gst_1a']);
        $this->assertSame(142.73, $bas['gst_1b']);
        $this->assertSame(-132.73, $bas['net_amount']);

        $this->assertSame(6, $bas['transaction_count']);
        $this->assertSame(1, $bas['excluded_count']);
        $this->assertSame(-132, $bas['whole']['net_amount']);
    }
}
