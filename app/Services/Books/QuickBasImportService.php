<?php

namespace App\Services\Books;

use App\Models\BookBasPeriod;
use App\Models\BookBusiness;
use App\Models\BookCategory;
use App\Models\BookFinancialYear;
use App\Models\BookImport;
use App\Models\BookImportRow;
use App\Models\BookTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class QuickBasImportService
{
    public function analyse(string $incomePath, string $expensePath): array
    {
        $income = $this->parseIncomeReport($incomePath);
        $expenses = $this->parseExpenseReport($expensePath);

        $rows = [...$income['rows'], ...$expenses['rows']];

        if ($rows === []) {
            throw new RuntimeException('No QuickBAS transaction rows were found.');
        }

        $financialYear = $this->detectFinancialYear($rows);
        $this->assertRowsFitFinancialYear($rows, $financialYear);

        $categories = $this->categorySummary($rows);
        $bas = $this->calculateBas($rows);

        return [
            'income' => $income,
            'expenses' => $expenses,
            'rows' => $rows,
            'financial_year' => $financialYear,
            'categories' => $categories,
            'bas' => $bas,
            'checks' => [
                'income_count_matches' => $income['totals']['items'] === count($income['rows']),
                'expense_count_matches' => $expenses['totals']['items'] === count($expenses['rows']),
                'income_total_matches' => $this->moneyEqual($income['totals']['income_total'], $bas['g1_total_sales']),
                // Validate the source Business Cost against the combined unrounded
                // expense business amounts. Do not add the already-rounded G10 and
                // G11 BAS buckets: rounding those two buckets separately can introduce
                // a one-cent difference even when the source transaction data is exact.
                'expense_business_total_matches' => $this->moneyEqual(
                    $expenses['totals']['business_cost'],
                    $this->expenseBusinessTotal($expenses['rows']),
                ),
                'gst_received_matches' => $this->moneyEqual($income['totals']['gst_received'], $bas['gst_1a']),
                'gst_paid_matches' => $this->moneyEqual($expenses['totals']['gst_paid'], $bas['gst_1b']),
            ],
        ];
    }

    public function preview(array $analysis, BookBusiness $business): array
    {
        $categoryPreview = [];

        foreach ($analysis['categories'] as $category) {
            $existing = BookCategory::query()
                ->where('book_business_id', $business->id)
                ->where('type', $category['type'])
                ->where('name', $category['name'])
                ->first();

            $categoryPreview[] = [
                ...$category,
                'existing_id' => $existing?->id,
                'action' => $existing ? 'match' : 'create',
            ];
        }

        return [
            'financial_year' => $analysis['financial_year'],
            'income_count' => count($analysis['income']['rows']),
            'expense_count' => count($analysis['expenses']['rows']),
            'total_count' => count($analysis['rows']),
            'categories' => $categoryPreview,
            'bas' => $analysis['bas'],
            'source_totals' => [
                'income' => $analysis['income']['totals'],
                'expenses' => $analysis['expenses']['totals'],
            ],
            'checks' => $analysis['checks'],
        ];
    }

    public function migrate(
        BookBusiness $business,
        array $analysis,
        string $incomeFilename,
        string $expenseFilename,
    ): array {
        $fy = $analysis['financial_year'];

        $existingImport = BookImport::query()
            ->where('book_business_id', $business->id)
            ->where('type', 'quickbas')
            ->where('status', 'complete')
            ->whereDate('period_start', $fy['start_date'])
            ->whereDate('period_end', $fy['end_date'])
            ->first();

        if ($existingImport) {
            throw ValidationException::withMessages([
                'quickbas' => 'QuickBAS history has already been imported for ' . $fy['label'] . '. Remove that migration first if you need to run it again.',
            ]);
        }

        return DB::transaction(function () use ($business, $analysis, $incomeFilename, $expenseFilename, $fy): array {
            $financialYear = BookFinancialYear::query()->firstOrCreate(
                [
                    'book_business_id' => $business->id,
                    'start_date' => $fy['start_date'],
                    'end_date' => $fy['end_date'],
                ],
                [
                    'label' => $fy['label'],
                    'status' => Carbon::parse($fy['end_date'])->isPast() ? 'closed' : 'open',
                ],
            );

            $categoryMap = [];
            $createdCategories = 0;

            foreach ($analysis['categories'] as $categoryData) {
                $category = BookCategory::query()->firstOrCreate(
                    [
                        'book_business_id' => $business->id,
                        'type' => $categoryData['type'],
                        'name' => $categoryData['name'],
                    ],
                    [
                        'default_gst_treatment' => $categoryData['default_gst_treatment'],
                        'default_business_use_percentage' => $categoryData['default_business_use_percentage'],
                        'default_purchase_type' => $categoryData['default_purchase_type'],
                        'active' => true,
                    ],
                );

                if ($category->wasRecentlyCreated) {
                    $createdCategories++;
                }

                $categoryMap[$this->categoryKey($categoryData['type'], $categoryData['name'])] = $category;
            }

            $bas = $analysis['bas'];

            $import = BookImport::create([
                'book_business_id' => $business->id,
                'book_bank_account_id' => null,
                'type' => 'quickbas',
                'status' => 'complete',
                'original_filename' => 'QuickBAS ' . $fy['label'],
                'format_snapshot' => [
                    'income_filename' => $incomeFilename,
                    'expense_filename' => $expenseFilename,
                    'source_income_totals' => $analysis['income']['totals'],
                    'source_expense_totals' => $analysis['expenses']['totals'],
                    'bas_totals' => $bas,
                    'checks' => $analysis['checks'],
                ],
                'period_start' => $fy['start_date'],
                'period_end' => $fy['end_date'],
                'row_count' => count($analysis['rows']),
                'included_count' => count($analysis['rows']),
                'excluded_count' => 0,
                'duplicate_count' => 0,
                'imported_at' => now(),
            ]);

            $rowNumber = 0;

            foreach ($analysis['rows'] as $row) {
                $rowNumber++;
                $category = $categoryMap[$this->categoryKey($row['category_type'], $row['category'])] ?? null;

                if (! $category) {
                    throw new RuntimeException('Unable to resolve QuickBAS category: ' . $row['category']);
                }

                $transaction = BookTransaction::create([
                    'book_business_id' => $business->id,
                    'book_bank_account_id' => null,
                    'book_category_id' => $category->id,
                    'book_import_id' => $import->id,
                    'transaction_date' => $row['transaction_date'],
                    'amount' => $row['amount'],
                    'business_use_percentage' => $row['business_use_percentage'],
                    'business_amount' => $row['business_amount'],
                    'net_amount' => $row['net_amount'],
                    'gst_amount' => $row['gst_amount'],
                    'gst_treatment' => $row['gst_treatment'],
                    'sale_type' => $row['sale_type'],
                    'purchase_type' => $row['purchase_type'],
                    'source' => 'quickbas_migration',
                    'payment_source' => null,
                    'description' => $row['description'] ?: $row['category'],
                    'notes' => null,
                ]);

                BookImportRow::create([
                    'book_import_id' => $import->id,
                    'book_transaction_id' => $transaction->id,
                    'book_category_id' => $category->id,
                    'row_number' => $rowNumber,
                    'transaction_date' => $row['transaction_date'],
                    'amount' => $row['amount'],
                    'description' => $row['description'],
                    'status' => 'included',
                    'confirmed_at' => now(),
                    'gst_treatment' => $row['gst_treatment'],
                    'business_use_percentage' => $row['business_use_percentage'],
                    'purchase_type' => $row['purchase_type'],
                    'raw_data' => [
                        'source' => 'quickbas',
                        'report_type' => $row['report_type'],
                        'source_row_number' => $row['source_row_number'],
                        'columns' => $row['raw_columns'],
                    ],
                ]);
            }

            $basPeriod = BookBasPeriod::query()->updateOrCreate(
                [
                    'book_business_id' => $business->id,
                    'start_date' => $fy['start_date'],
                    'end_date' => $fy['end_date'],
                    'period_type' => 'annual',
                ],
                [
                    'book_financial_year_id' => $financialYear->id,
                    'period_label' => $fy['label'] . ' Annual',
                    'status' => 'historical',
                    'g1_total_sales' => $bas['g1_total_sales'],
                    'g2_export_sales' => $bas['g2_export_sales'],
                    'g3_gst_free_sales' => $bas['g3_gst_free_sales'],
                    'g10_capital_purchases' => $bas['g10_capital_purchases'],
                    'g11_non_capital_purchases' => $bas['g11_non_capital_purchases'],
                    'gst_1a' => $bas['gst_1a'],
                    'gst_1b' => $bas['gst_1b'],
                    'net_amount' => $bas['net_amount'],
                    'prepared_at' => now(),
                ],
            );

            return [
                'import' => $import,
                'financial_year' => $financialYear,
                'bas_period' => $basPeriod,
                'created_categories' => $createdCategories,
                'matched_categories' => count($analysis['categories']) - $createdCategories,
            ];
        });
    }

    protected function parseIncomeReport(string $path): array
    {
        $report = $this->readReport($path, 'income');
        $rows = [];

        foreach ($report['data_rows'] as $row) {
            $columns = $this->normaliseColumns($row['columns'], 9);
            $date = $this->parseDate($columns[0], $row['line']);
            $category = trim($columns[1]);
            $incomeWithGst = $this->parseMoney($columns[2]);
            $gstFreeIncome = $this->parseMoney($columns[3]);
            $exportIncome = $this->parseMoney($columns[4]);
            $incomeTotal = $this->parseMoney($columns[5]);
            $incomeNet = $this->parseMoney($columns[6]);
            $gstReceived = $this->parseMoney($columns[7]);
            $notes = trim($columns[8]);

            if ($category === '' || $incomeTotal <= 0) {
                throw new RuntimeException('Invalid QuickBAS income row at line ' . $row['line'] . '.');
            }

            $saleType = $exportIncome > 0
                ? 'export'
                : ($gstFreeIncome > 0 ? 'gst_free' : 'gst');

            $rows[] = [
                'report_type' => 'income',
                'source_row_number' => $row['line'],
                'raw_columns' => $columns,
                'transaction_date' => $date->toDateString(),
                'category_type' => 'income',
                'category' => $category,
                'amount' => round($incomeTotal, 2),
                'business_use_percentage' => 100.0,
                'business_amount' => round($incomeTotal, 4),
                'net_amount' => round($incomeNet, 2),
                'gst_amount' => round($gstReceived, 2),
                'gst_treatment' => $gstReceived > 0 ? 'gst_applicable' : 'gst_free',
                'sale_type' => $saleType,
                'purchase_type' => null,
                'description' => $notes,
                'income_with_gst' => $incomeWithGst,
                'gst_free_income' => $gstFreeIncome,
                'export_income' => $exportIncome,
            ];
        }

        $totalColumns = $this->normaliseColumns($report['total_columns'], 9);

        return [
            'title' => $report['title'],
            'rows' => $rows,
            'totals' => [
                'items' => $this->parseItemCount($totalColumns[1]),
                'income_with_gst' => $this->parseMoney($totalColumns[2]),
                'gst_free_income' => $this->parseMoney($totalColumns[3]),
                'export_income' => $this->parseMoney($totalColumns[4]),
                'income_total' => $this->parseMoney($totalColumns[5]),
                'income_net_gst' => $this->parseMoney($totalColumns[6]),
                'gst_received' => $this->parseMoney($totalColumns[7]),
            ],
        ];
    }

    protected function parseExpenseReport(string $path): array
    {
        $report = $this->readReport($path, 'expense');
        $rows = [];

        foreach ($report['data_rows'] as $row) {
            $columns = $this->normaliseColumns($row['columns'], 9);
            $date = $this->parseDate($columns[0], $row['line']);
            $category = trim($columns[1]);
            $nonCapital = $this->parseMoney($columns[2]);
            $capital = $this->parseMoney($columns[3]);
            $businessUse = $this->parsePercentage($columns[4]);
            $displayBusinessCost = $this->parseMoney($columns[5]);
            $net = $this->parseMoney($columns[6]);
            $gstPaid = $this->parseMoney($columns[7]);
            $notes = trim($columns[8]);

            $gross = $capital > 0 ? $capital : $nonCapital;
            $purchaseType = $capital > 0 ? 'capital' : 'non_capital';

            if ($category === '') {
                throw new RuntimeException('Invalid QuickBAS expense row at line ' . $row['line'] . '.');
            }

            // Older QuickBAS files can contain a genuine zero-value expense row
            // (for example a subscription/category note that was entered but never
            // given an amount). QuickBAS still includes that row in its ITEMS count,
            // so preserve it as a $0 historical transaction rather than rejecting
            // or skipping it. Only reject a row if it contains inconsistent money
            // values with no Non-Capital/Capital amount.
            if (
                $gross <= 0
                && (
                    $displayBusinessCost != 0.0
                    || $net != 0.0
                    || $gstPaid != 0.0
                )
            ) {
                throw new RuntimeException('Invalid QuickBAS expense row at line ' . $row['line'] . '.');
            }

            // QuickBAS totals business-use amounts before rounding each displayed row.
            // Keep four decimal places so percentage-use rows reproduce the source totals.
            $exactBusinessAmount = round($gross * ($businessUse / 100), 4);

            // QuickBAS does the same thing for GST on percentage business-use rows:
            // the row displays GST rounded to cents, but the TOTAL row can include the
            // underlying fraction of a cent. Reconstruct that extra precision only when
            // the normal 1/11 GST calculation rounds back to the GST shown in the export.
            // This avoids changing rows where GST was manually overridden or was not the
            // standard GST fraction.
            $exactGstPaid = round($gstPaid, 4);

            if ($businessUse < 100 && $gstPaid > 0) {
                $calculatedExactGst = $exactBusinessAmount / 11;

                if ($this->moneyEqual(round($calculatedExactGst, 2), $gstPaid)) {
                    $exactGstPaid = round($calculatedExactGst, 4);
                }
            }

            $rows[] = [
                'report_type' => 'expense',
                'source_row_number' => $row['line'],
                'raw_columns' => $columns,
                'transaction_date' => $date->toDateString(),
                'category_type' => 'expense',
                'category' => $category,
                'amount' => -round($gross, 2),
                'business_use_percentage' => $businessUse,
                'business_amount' => -$exactBusinessAmount,
                'display_business_cost' => $displayBusinessCost,
                'net_amount' => -round($net, 2),
                'gst_amount' => $exactGstPaid,
                'gst_treatment' => $gstPaid > 0 ? 'gst_applicable' : 'gst_free',
                'sale_type' => null,
                'purchase_type' => $purchaseType,
                'description' => $notes,
            ];
        }

        $totalColumns = $this->normaliseColumns($report['total_columns'], 9);

        $totals = [
            'items' => $this->parseItemCount($totalColumns[1]),
            'non_capital' => $this->parseMoney($totalColumns[2]),
            'capital' => $this->parseMoney($totalColumns[3]),
            'business_cost' => $this->parseMoney($totalColumns[5]),
            'net_gst' => $this->parseMoney($totalColumns[6]),
            'gst_paid' => $this->parseMoney($totalColumns[7]),
        ];

        // Some older QuickBAS exports only expose GST to 2 decimals on each row,
        // while the TOTAL row was calculated from hidden sub-cent precision.
        //
        // Preserve the visible row values for display, but distribute a very small
        // hidden adjustment (max 0.0049 per GST row) so the migrated 4-decimal
        // gst_amount values reconcile exactly to QuickBAS' authoritative GST TOTAL.
        //
        // Example 2015-16:
        //   displayed row GST sum = $706.96
        //   QuickBAS GST TOTAL    = $706.98
        //
        // Every adjusted value still rounds back to the same 2-decimal GST shown
        // in the export, so the historical transaction screen remains unchanged.
        $rows = $this->reconcileExpenseGstTotal($rows, (float) $totals['gst_paid']);

        return [
            'title' => $report['title'],
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    protected function readReport(string $path, string $type): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('Unable to read the QuickBAS ' . $type . ' report.');
        }

        // QuickBAS exports can contain mixed line endings. In particular, some
        // exports use LF for most rows but a bare CR immediately before the
        // final TOTAL row. Reading with fgets() only splits on LF, which joins
        // that TOTAL row onto the preceding transaction and makes it appear
        // to be missing. Normalise all CRLF / CR / LF separators here.
        $rawLines = preg_split('/\r\n|\r|\n/', $contents) ?: [];
        $lines = [];

        foreach ($rawLines as $index => $line) {
            $lineNumber = $index + 1;
            $line = preg_replace('/^\xEF\xBB\xBF/', '', (string) $line);

            if (trim((string) $line) === '') {
                continue;
            }

            $lines[] = ['line' => $lineNumber, 'text' => (string) $line];
        }

        if (count($lines) < 4) {
            throw new RuntimeException('The QuickBAS ' . $type . ' report does not contain enough rows.');
        }

        $title = $lines[0]['text'];
        $header = str_getcsv($lines[1]['text']);
        $this->validateHeader($header, $type);

        $dataRows = [];
        $totalColumns = null;

        foreach (array_slice($lines, 2) as $line) {
            $columns = str_getcsv($line['text']);

            if (strtoupper(trim((string) ($columns[0] ?? ''))) === 'TOTAL') {
                $totalColumns = $columns;
                break;
            }

            $dataRows[] = [
                'line' => $line['line'],
                'columns' => $columns,
            ];
        }

        if ($totalColumns === null) {
            throw new RuntimeException('The QuickBAS ' . $type . ' report is missing its TOTAL row.');
        }

        return [
            'title' => $title,
            'header' => $header,
            'data_rows' => $dataRows,
            'total_columns' => $totalColumns,
        ];
    }

    protected function validateHeader(array $header, string $type): void
    {
        $normalised = array_map(fn ($value) => strtolower(trim((string) $value)), $header);

        $required = $type === 'income'
            ? ['date', 'category', 'income with gst', 'gst-free income', 'export income', 'income total', 'income nett gst', 'gst received']
            : ['date', 'category', 'non-capital', 'capital', '% business use', 'business cost', 'nett gst', 'gst paid'];

        foreach ($required as $index => $label) {
            if (($normalised[$index] ?? null) !== $label) {
                throw new RuntimeException('This does not look like a QuickBAS ' . $type . ' export. Expected column ' . ($index + 1) . ' to be "' . $label . '".');
            }
        }
    }

    protected function normaliseColumns(array $columns, int $expected): array
    {
        $columns = array_values($columns);

        if (count($columns) > $expected) {
            $columns = [
                ...array_slice($columns, 0, $expected - 1),
                implode(',', array_slice($columns, $expected - 1)),
            ];
        }

        return array_pad($columns, $expected, '');
    }

    protected function parseDate(string $value, int $line): Carbon
    {
        try {
            $date = Carbon::createFromFormat('!d M Y', trim($value));
            $errors = Carbon::getLastErrors();

            if (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
                throw new RuntimeException();
            }

            return $date;
        } catch (\Throwable) {
            throw new RuntimeException('Invalid QuickBAS date "' . $value . '" at line ' . $line . '.');
        }
    }

    protected function parseMoney(mixed $value): float
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '-') {
            return 0.0;
        }

        $negative = str_starts_with($value, '(') && str_ends_with($value, ')');
        $clean = str_replace([',', '$', ' ', '+', '(', ')'], '', $value);

        if (! is_numeric($clean)) {
            throw new RuntimeException('Invalid QuickBAS amount: ' . $value);
        }

        $number = (float) $clean;

        return $negative ? -abs($number) : $number;
    }

    protected function parsePercentage(mixed $value): float
    {
        $clean = str_replace(['%', ' '], '', trim((string) $value));

        if ($clean === '' || ! is_numeric($clean)) {
            throw new RuntimeException('Invalid QuickBAS business-use percentage: ' . $value);
        }

        return max(0, min(100, (float) $clean));
    }

    protected function parseItemCount(string $value): int
    {
        if (preg_match('/ITEMS:\s*(\d+)/i', $value, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    protected function detectFinancialYear(array $rows): array
    {
        $dates = collect($rows)
            ->pluck('transaction_date')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date))
            ->sort()
            ->values();

        $first = $dates->first();

        if (! $first) {
            throw new RuntimeException('Unable to determine the QuickBAS financial year.');
        }

        $startYear = $first->month >= 7 ? $first->year : $first->year - 1;
        $endYear = $startYear + 1;

        return [
            'label' => $startYear . '–' . substr((string) $endYear, -2),
            'start_date' => Carbon::create($startYear, 7, 1)->toDateString(),
            'end_date' => Carbon::create($endYear, 6, 30)->toDateString(),
            'start_year' => $startYear,
            'end_year' => $endYear,
        ];
    }

    protected function assertRowsFitFinancialYear(array $rows, array $financialYear): void
    {
        $start = Carbon::parse($financialYear['start_date']);
        $end = Carbon::parse($financialYear['end_date']);

        foreach ($rows as $row) {
            $date = Carbon::parse($row['transaction_date']);

            if ($date->lt($start) || $date->gt($end)) {
                throw new RuntimeException('The QuickBAS files contain transactions from more than one financial year. Import one worksheet/year at a time.');
            }
        }
    }

    protected function categorySummary(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $key = $this->categoryKey($row['category_type'], $row['category']);
            $groups[$key]['type'] = $row['category_type'];
            $groups[$key]['name'] = $row['category'];
            $groups[$key]['count'] = ($groups[$key]['count'] ?? 0) + 1;
            $groups[$key]['gst_yes'] = ($groups[$key]['gst_yes'] ?? 0) + ($row['gst_amount'] > 0 ? 1 : 0);
            $groups[$key]['gst_no'] = ($groups[$key]['gst_no'] ?? 0) + ($row['gst_amount'] > 0 ? 0 : 1);
            $businessUseKey = number_format((float) $row['business_use_percentage'], 2, '.', '');
            $groups[$key]['business_use'][$businessUseKey] = ($groups[$key]['business_use'][$businessUseKey] ?? 0) + 1;

            if ($row['category_type'] === 'expense') {
                $purchase = $row['purchase_type'] ?: 'non_capital';
                $groups[$key]['purchase_type'][$purchase] = ($groups[$key]['purchase_type'][$purchase] ?? 0) + 1;
            }
        }

        $summary = [];

        foreach ($groups as $group) {
            arsort($group['business_use']);
            $defaultBusinessUse = (float) array_key_first($group['business_use']);
            $defaultGst = $group['gst_yes'] > $group['gst_no'] ? 'gst_applicable' : 'gst_free';
            $defaultPurchase = 'non_capital';

            if (($group['purchase_type'] ?? []) !== []) {
                arsort($group['purchase_type']);
                $defaultPurchase = (string) array_key_first($group['purchase_type']);
            }

            $summary[] = [
                'type' => $group['type'],
                'name' => $group['name'],
                'count' => $group['count'],
                'default_gst_treatment' => $defaultGst,
                'default_business_use_percentage' => $defaultBusinessUse,
                'default_purchase_type' => $defaultPurchase,
            ];
        }

        usort($summary, fn (array $a, array $b) => [$a['type'], $a['name']] <=> [$b['type'], $b['name']]);

        return $summary;
    }

    protected function calculateBas(array $rows): array
    {
        $g1 = 0.0;
        $g2 = 0.0;
        $g3 = 0.0;
        $g10 = 0.0;
        $g11 = 0.0;
        $gst1a = 0.0;
        $gst1b = 0.0;

        foreach ($rows as $row) {
            if ($row['category_type'] === 'income') {
                $g1 += (float) $row['business_amount'];
                $gst1a += (float) $row['gst_amount'];

                if ($row['sale_type'] === 'export') {
                    $g2 += (float) $row['business_amount'];
                } elseif ($row['sale_type'] === 'gst_free') {
                    $g3 += (float) $row['business_amount'];
                }

                continue;
            }

            $businessAmount = abs((float) $row['business_amount']);
            $gst1b += (float) $row['gst_amount'];

            if ($row['purchase_type'] === 'capital') {
                $g10 += $businessAmount;
            } else {
                $g11 += $businessAmount;
            }
        }

        return [
            'g1_total_sales' => round($g1, 2),
            'g2_export_sales' => round($g2, 2),
            'g3_gst_free_sales' => round($g3, 2),
            'g10_capital_purchases' => round($g10, 2),
            'g11_non_capital_purchases' => round($g11, 2),
            'gst_1a' => round($gst1a, 2),
            'gst_1b' => round($gst1b, 2),
            'net_amount' => round($gst1a - $gst1b, 2),
        ];
    }

    protected function reconcileExpenseGstTotal(array $rows, float $targetGstTotal): array
    {
        $currentTotal = 0.0;
        $eligibleIndexes = [];

        foreach ($rows as $index => $row) {
            $gst = (float) ($row['gst_amount'] ?? 0);
            $currentTotal += $gst;

            if ($gst > 0) {
                $eligibleIndexes[] = $index;
            }
        }

        $difference = round($targetGstTotal - $currentTotal, 4);

        if (abs($difference) < 0.00005) {
            return $rows;
        }

        // This is only a sub-cent precision reconciliation. If the source differs
        // materially, leave it untouched so the normal validation still flags it.
        if ($eligibleIndexes === [] || abs($difference) > 0.10) {
            return $rows;
        }

        $units = (int) round(abs($difference) * 10000);
        $direction = $difference > 0 ? 1 : -1;
        $remaining = $units;
        $cursor = 0;
        $adjustedUnits = array_fill_keys($eligibleIndexes, 0);

        // A 0.0049 adjustment is the largest safe movement that still rounds back
        // to the original 2-decimal exported GST amount.
        $maxUnitsPerRow = 49;

        while ($remaining > 0) {
            $index = $eligibleIndexes[$cursor % count($eligibleIndexes)];

            if ($adjustedUnits[$index] < $maxUnitsPerRow) {
                $candidate = round(
                    (float) $rows[$index]['gst_amount'] + ($direction * 0.0001),
                    4,
                );

                if ($candidate >= 0) {
                    $rows[$index]['gst_amount'] = $candidate;
                    $adjustedUnits[$index]++;
                    $remaining--;
                }
            }

            $cursor++;

            if ($cursor > count($eligibleIndexes) * ($maxUnitsPerRow + 1)) {
                break;
            }
        }

        // If there was not enough safe capacity, do not partially reconcile.
        if ($remaining > 0) {
            foreach ($adjustedUnits as $index => $usedUnits) {
                if ($usedUnits === 0) {
                    continue;
                }

                $rows[$index]['gst_amount'] = round(
                    (float) $rows[$index]['gst_amount'] - ($direction * $usedUnits * 0.0001),
                    4,
                );
            }

            return $rows;
        }

        return $rows;
    }

    protected function expenseBusinessTotal(array $rows): float
    {
        $total = 0.0;

        foreach ($rows as $row) {
            $total += abs((float) ($row['business_amount'] ?? 0));
        }

        // QuickBAS' historical Business Cost TOTAL behaves differently from its
        // GST total: it truncates the combined business-use total to cents.
        //
        // Example 2017-18:
        //   combined exact business cost = 86194.1150
        //   QuickBAS TOTAL              = 86194.11
        //
        // Do not use round($total, 2) here because that produces 86194.12.
        // Keep the BAS G10/G11 calculations unchanged; this helper is only for
        // validating the source report's Business Cost TOTAL.
        $total = round($total, 4);

        return floor(($total + 0.0000001) * 100) / 100;
    }

    protected function categoryKey(string $type, string $name): string
    {
        return strtolower($type . '|' . trim($name));
    }

    protected function moneyEqual(float $a, float $b): bool
    {
        return abs(round($a, 2) - round($b, 2)) < 0.005;
    }
}
