<?php

namespace App\Services\Books;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class BookBasDueDateService
{
    public const WARNING_DAYS = 14;

    /**
     * Return the standard ATO quarterly BAS due date.
     *
     * Standard quarterly dates:
     * Q1 (Jul-Sep) -> 28 Oct
     * Q2 (Oct-Dec) -> 28 Feb
     * Q3 (Jan-Mar) -> 28 Apr
     * Q4 (Apr-Jun) -> 28 Jul
     *
     * If the date falls on a weekend, move it to the next weekday.
     *
     * If an explicit due-date override has been saved on a BAS period, use it
     * unchanged. This lets Books follow the actual due date shown by the ATO
     * where an online concession, agent program or extension applies.
     */
    public function dueDate(
        CarbonInterface|string $periodEnd,
        CarbonInterface|string|null $override = null,
    ): Carbon {
        if ($override) {
            return Carbon::parse($override, 'Australia/Hobart')->startOfDay();
        }

        $end = Carbon::parse($periodEnd, 'Australia/Hobart')->startOfDay();

        $due = match ($end->month) {
            9 => Carbon::create($end->year, 10, 28, 0, 0, 0, 'Australia/Hobart'),
            12 => Carbon::create($end->year + 1, 2, 28, 0, 0, 0, 'Australia/Hobart'),
            3 => Carbon::create($end->year, 4, 28, 0, 0, 0, 'Australia/Hobart'),
            6 => Carbon::create($end->year, 7, 28, 0, 0, 0, 'Australia/Hobart'),
            default => $end->copy()->addMonthNoOverflow()->day(28),
        };

        while ($due->isWeekend()) {
            $due->addDay();
        }

        return $due->startOfDay();
    }

    public function warningStartsAt(CarbonInterface|string $dueDate): Carbon
    {
        return Carbon::parse($dueDate, 'Australia/Hobart')
            ->startOfDay()
            ->subDays(self::WARNING_DAYS);
    }

    public function daysUntil(CarbonInterface|string $dueDate, CarbonInterface|string|null $today = null): int
    {
        $today = $today
            ? Carbon::parse($today, 'Australia/Hobart')->startOfDay()
            : now('Australia/Hobart')->startOfDay();

        $due = Carbon::parse($dueDate, 'Australia/Hobart')->startOfDay();

        return (int) $today->diffInDays($due, false);
    }
}
