<?php

namespace App\Services\Books;

use App\Models\BookBusiness;
use App\Models\BookCategory;
use App\Models\BookCategoryRule;
use App\Models\BookTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class BankCsvImportService
{
    public const DATE_FORMATS = [
        'd/m/Y' => 'DD/MM/YYYY',
        'Y-m-d' => 'YYYY-MM-DD',
        'd-m-Y' => 'DD-MM-YYYY',
        'm/d/Y' => 'MM/DD/YYYY',
    ];

    public function guessFormat(string $path): array
    {
        $rows = $this->readRawRows($path, 3);
        $first = $rows[0] ?? [];
        $second = $rows[1] ?? [];

        $hasHeader = false;
        $dateFormat = 'd/m/Y';

        $firstDate = $this->detectDateFormat($first[0] ?? null);
        $secondDate = $this->detectDateFormat($second[0] ?? null);

        if ($firstDate) {
            $dateFormat = $firstDate;
        } elseif ($secondDate) {
            $hasHeader = true;
            $dateFormat = $secondDate;
        }

        $columnCount = max(count($first), count($second), 4);

        return [
            'has_header' => $hasHeader,
            'date_column' => 0,
            'amount_column' => min(1, $columnCount - 1),
            'description_column' => min(2, $columnCount - 1),
            'balance_column' => $columnCount >= 4 ? 3 : null,
            'date_format' => $dateFormat,
        ];
    }

    public function preview(string $path, array $format, int $limit = 8): array
    {
        $parsed = [];

        foreach ($this->parse($path, $format) as $row) {
            $parsed[] = $row;

            if (count($parsed) >= $limit) {
                break;
            }
        }

        return $parsed;
    }

    public function rawPreview(string $path, int $limit = 5): array
    {
        return $this->readRawRows($path, $limit);
    }

    public function parse(string $path, array $format): \Generator
    {
        $handle = fopen($path, 'rb');

        if (! $handle) {
            throw new RuntimeException('Unable to read the CSV file.');
        }

        $physicalRow = 0;
        $dataRow = 0;

        try {
            while (($columns = fgetcsv($handle)) !== false) {
                $physicalRow++;

                if ($physicalRow === 1 && isset($columns[0])) {
                    $columns[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $columns[0]);
                }

                if ($this->rowIsEmpty($columns)) {
                    continue;
                }

                if (($format['has_header'] ?? false) && $physicalRow === 1) {
                    continue;
                }

                $dataRow++;

                $dateRaw = $this->column($columns, $format['date_column'] ?? null);
                $amountRaw = $this->column($columns, $format['amount_column'] ?? null);
                $description = trim((string) $this->column($columns, $format['description_column'] ?? null));
                $balanceRaw = $this->column($columns, $format['balance_column'] ?? null);

                $errors = [];
                $date = $this->parseDate($dateRaw, $format['date_format'] ?? 'd/m/Y');
                $amount = $this->parseMoney($amountRaw);
                $balance = $this->parseMoney($balanceRaw);

                if (! $date) {
                    $errors[] = 'Invalid date';
                }

                if ($amount === null) {
                    $errors[] = 'Invalid amount';
                }

                if ($description === '') {
                    $description = 'Bank transaction';
                }

                yield [
                    'row_number' => $dataRow,
                    'physical_row_number' => $physicalRow,
                    'transaction_date' => $date?->toDateString(),
                    'amount' => $amount,
                    'description' => $description,
                    'balance' => $balance,
                    'errors' => $errors,
                    'raw_data' => array_values($columns),
                ];
            }
        } finally {
            fclose($handle);
        }
    }

    public function fingerprint(
        int $businessId,
        int $bankAccountId,
        string $date,
        float $amount,
        string $description,
        ?float $balance = null,
    ): string {
        return hash('sha256', implode('|', [
            $businessId,
            $bankAccountId,
            $date,
            number_format($amount, 2, '.', ''),
            $this->normaliseDescription($description),
            $balance === null ? '' : number_format($balance, 2, '.', ''),
        ]));
    }

    public function rulesFor(BookBusiness $business): Collection
    {
        return BookCategoryRule::query()
            ->with('category')
            ->where('book_business_id', $business->id)
            ->where('active', true)
            ->get()
            ->filter(fn (BookCategoryRule $rule) => $rule->category?->active)
            ->values();
    }

    public function historicalHintsFor(BookBusiness $business): array
    {
        $transactions = BookTransaction::query()
            ->where('book_business_id', $business->id)
            ->whereNotNull('book_category_id')
            ->whereNotNull('description')
            ->whereHas('category', fn ($query) => $query->where('active', true))
            ->orderByDesc('transaction_date')
            ->limit(5000)
            ->get(['book_category_id', 'amount', 'description']);

        $groups = [];

        foreach ($transactions as $transaction) {
            if ($this->isInternationalTransactionFee((string) $transaction->description)) {
                continue;
            }

            $key = $this->merchantKey((string) $transaction->description);

            if ($key === '') {
                continue;
            }

            $direction = (float) $transaction->amount >= 0 ? 'income' : 'expense';
            $groupKey = $direction . '|' . $key;
            $categoryId = (int) $transaction->book_category_id;

            $groups[$groupKey]['total'] = ($groups[$groupKey]['total'] ?? 0) + 1;
            $groups[$groupKey]['categories'][$categoryId] = ($groups[$groupKey]['categories'][$categoryId] ?? 0) + 1;
        }

        $hints = [];

        foreach ($groups as $key => $group) {
            arsort($group['categories']);
            $categoryId = (int) array_key_first($group['categories']);
            $topCount = (int) $group['categories'][$categoryId];
            $total = (int) $group['total'];

            // Only learn from history when the same merchant has been categorised consistently.
            if ($total > 0 && ($topCount / $total) >= 0.9) {
                $hints[$key] = [
                    'category_id' => $categoryId,
                    'confidence' => $total >= 3 ? 90 : 82,
                    'count' => $total,
                ];
            }
        }

        return $hints;
    }

    public function suggestCategory(
        float $amount,
        string $description,
        Collection $rules,
        array $historicalHints,
    ): ?array {
        // International Transaction Fee is linked to the related overseas purchase.
        // It must not get one permanent category through generic rules/history.
        if ($this->isInternationalTransactionFee($description)) {
            return null;
        }

        $direction = $amount >= 0 ? 'income' : 'expense';
        $normalised = $this->normaliseDescription($description);
        $best = null;

        foreach ($rules as $rule) {
            $category = $rule->category;

            if (! $category || ! in_array($category->type, [$direction, 'other'], true)) {
                continue;
            }

            $needle = $this->normaliseDescription((string) $rule->match_value);

            if ($needle === '') {
                continue;
            }

            $matched = match ($rule->match_type) {
                'exact' => $normalised === $needle,
                'starts_with' => str_starts_with($normalised, $needle),
                default => str_contains($normalised, $needle),
            };

            if (! $matched) {
                continue;
            }

            $confidence = match ($rule->match_type) {
                'exact' => 100,
                'starts_with' => 96,
                default => min(95, 86 + min(9, (int) floor(strlen($needle) / 8))),
            };

            $candidate = [
                'category_id' => $category->id,
                'confidence' => $confidence,
                'source' => 'rule',
                'rule_id' => $rule->id,
            ];

            if (! $best || $candidate['confidence'] > $best['confidence']) {
                $best = $candidate;
            }
        }

        if ($best) {
            return $best;
        }

        $key = $direction . '|' . $this->merchantKey($description);
        $hint = $historicalHints[$key] ?? null;

        if ($hint) {
            return [
                'category_id' => $hint['category_id'],
                'confidence' => $hint['confidence'],
                'source' => 'history',
                'history_count' => $hint['count'],
            ];
        }

        return null;
    }

    public function isInternationalTransactionFee(string $description): bool
    {
        return str_contains(
            $this->normaliseDescription($description),
            'international transaction fee',
        );
    }

    public function merchantKey(string $description): string
    {
        $value = strtoupper($description);
        $value = preg_replace('/\bVALUE DATE:?\s*\d{1,2}[\/\- ]\d{1,2}[\/\- ]\d{2,4}\b.*$/i', '', $value);
        $value = preg_replace('/\bCARD\s+XX\d+\b/i', '', $value);
        $value = preg_replace('/\b(?:AUD|USD|GBP|EUR|NZD)\s+[-+]?\d+(?:\.\d+)?\b/i', '', $value);
        $value = preg_replace('/\b\d{8,}\b/', '', $value);
        $value = preg_replace('/\s+/', ' ', trim((string) $value));

        return mb_substr($value, 0, 100);
    }

    public function normaliseDescription(string $description): string
    {
        $value = mb_strtolower(trim($description));
        $value = preg_replace('/\s+/', ' ', (string) $value);

        return $value;
    }

    protected function readRawRows(string $path, int $limit): array
    {
        $rows = [];
        $handle = fopen($path, 'rb');

        if (! $handle) {
            throw new RuntimeException('Unable to read the CSV file.');
        }

        try {
            while (count($rows) < $limit && ($row = fgetcsv($handle)) !== false) {
                if (isset($row[0])) {
                    $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
                }

                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $rows[] = array_values($row);
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function column(array $columns, mixed $index): mixed
    {
        if ($index === null || $index === '' || ! is_numeric($index)) {
            return null;
        }

        return $columns[(int) $index] ?? null;
    }

    protected function parseDate(mixed $value, string $format): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!' . $format, $value);
            $errors = Carbon::getLastErrors();

            if (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
                return null;
            }

            return $date;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function detectDateFormat(mixed $value): ?string
    {
        foreach (array_keys(self::DATE_FORMATS) as $format) {
            if ($this->parseDate($value, $format)) {
                return $format;
            }
        }

        return null;
    }

    protected function parseMoney(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '-') {
            return null;
        }

        $negative = str_starts_with($value, '(') && str_ends_with($value, ')');
        $clean = str_replace([',', '$', ' ', '+', '(', ')'], '', $value);

        if (! is_numeric($clean)) {
            return null;
        }

        $number = (float) $clean;

        return $negative ? -abs($number) : $number;
    }
}
