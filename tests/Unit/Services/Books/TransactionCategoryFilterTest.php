<?php

namespace Tests\Feature\Books;

use App\Filament\Pages\Books\BooksTransactions;
use App\Models\BookBusiness;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionCategoryFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Use a separate in-memory connection; never touch application data.
        config(['database.default' => 'category_filter_test']);
        config(['database.connections.category_filter_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);

        Schema::create('book_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('book_business_id');
            $table->string('name');
            $table->string('type');
            $table->boolean('active');
        });
        Schema::create('book_financial_years', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('book_business_id');
            $table->date('start_date');
            $table->date('end_date');
        });
        Schema::create('book_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('book_business_id');
            $table->unsignedBigInteger('book_category_id')->nullable();
            $table->date('transaction_date');
        });

        DB::table('book_financial_years')->insert([
            ['id' => 1, 'book_business_id' => 1, 'start_date' => '2026-07-01', 'end_date' => '2027-06-30'],
            ['id' => 2, 'book_business_id' => 1, 'start_date' => '2025-07-01', 'end_date' => '2026-06-30'],
        ]);
        foreach ([
            [1, 1, 'Active expense', 'expense', true],
            [2, 1, 'Active income', 'income', true],
            [3, 1, 'Shared', 'other', true],
            [4, 1, 'Old expense', 'expense', false],
            [5, 1, 'Unused expense', 'expense', false],
            [6, 2, 'Other business', 'expense', true],
            [7, 1, 'Old income', 'income', false],
            [8, 1, 'Previous year only', 'expense', false],
        ] as [$id, $business, $name, $type, $active]) {
            DB::table('book_categories')->insert([
                'id' => $id, 'book_business_id' => $business,
                'name' => $name, 'type' => $type, 'active' => $active,
            ]);
        }
        DB::table('book_transactions')->insert([
            ['book_business_id' => 1, 'book_category_id' => 4, 'transaction_date' => '2027-06-30'],
            ['book_business_id' => 1, 'book_category_id' => 7, 'transaction_date' => '2026-07-01'],
            ['book_business_id' => 1, 'book_category_id' => 8, 'transaction_date' => '2026-06-30'],
            ['book_business_id' => 2, 'book_category_id' => 5, 'transaction_date' => '2026-08-01'],
        ]);
    }

    private function page(): BooksTransactions
    {
        $page = new class extends BooksTransactions {
            public function getBookBusiness(): BookBusiness
            {
                return (new BookBusiness())->forceFill(['id' => 1]);
            }
        };
        $page->financialYearFilter = '1';

        return $page;
    }

    private function ids(BooksTransactions $page): array
    {
        return $page->getFilterCategoriesProperty()->pluck('id')->sort()->values()->all();
    }

    public function test_inactive_categories_require_use_in_the_whole_selected_year(): void
    {
        $page = $this->page();
        $page->periodFilter = 'q1';
        $page->search = 'unrelated search';
        $page->categoryFilter = 1;

        $this->assertSame([1, 2, 3, 4, 7], $this->ids($page));
        $page->financialYearFilter = '2';
        $this->assertSame([1, 2, 3, 8], $this->ids($page));
    }

    public function test_direction_filters_keep_only_matching_and_shared_categories(): void
    {
        $page = $this->page();
        $page->directionFilter = 'expense';
        $this->assertSame([1, 3, 4], $this->ids($page));
        $page->directionFilter = 'income';
        $this->assertSame([2, 3, 7], $this->ids($page));
    }

    public function test_all_years_includes_used_historical_categories_but_not_unused_ones(): void
    {
        $page = $this->page();
        $page->financialYearFilter = 'all';
        $this->assertSame([1, 2, 3, 4, 7, 8], $this->ids($page));
    }

    public function test_direction_change_clears_an_incompatible_selection(): void
    {
        $page = $this->page();
        $page->categoryFilter = 4;
        $page->directionFilter = 'income';
        $page->updatedDirectionFilter();
        $this->assertNull($page->categoryFilter);

        $page->categoryFilter = 3;
        $page->updatedDirectionFilter();
        $this->assertSame(3, $page->categoryFilter);
    }

    public function test_year_change_clears_an_unavailable_selection_and_keeps_a_valid_one(): void
    {
        $page = $this->page();
        $page->categoryFilter = 4;
        $page->financialYearFilter = '2';
        $page->updatedFinancialYearFilter();
        $this->assertNull($page->categoryFilter);

        $page->categoryFilter = 1;
        $page->updatedFinancialYearFilter();
        $this->assertSame(1, $page->categoryFilter);
        $page->financialYearFilter = 'all';
        $page->periodFilter = 'q1';
        $page->updatedFinancialYearFilter();
        $this->assertSame('all', $page->periodFilter);
    }
}
