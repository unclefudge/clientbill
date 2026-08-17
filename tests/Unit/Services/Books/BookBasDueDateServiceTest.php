<?php

namespace Tests\Unit\Services\Books;

use App\Services\Books\BookBasDueDateService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class BookBasDueDateServiceTest extends TestCase
{
    public function test_it_returns_standard_quarterly_bas_due_dates(): void
    {
        $service = new BookBasDueDateService();

        $this->assertSame(
            '2026-10-28',
            $service->dueDate('2026-09-30')->toDateString(),
        );

        // 28 Feb 2027 is a Sunday, so the standard due date moves to Monday.
        $this->assertSame(
            '2027-03-01',
            $service->dueDate('2026-12-31')->toDateString(),
        );

        $this->assertSame(
            '2027-04-28',
            $service->dueDate('2027-03-31')->toDateString(),
        );

        $this->assertSame(
            '2027-07-28',
            $service->dueDate('2027-06-30')->toDateString(),
        );
    }

    public function test_warning_starts_fourteen_days_before_due_date(): void
    {
        $service = new BookBasDueDateService();

        $due = Carbon::parse('2026-10-28');

        $this->assertSame(
            '2026-10-14',
            $service->warningStartsAt($due)->toDateString(),
        );
    }

    public function test_explicit_due_date_override_wins(): void
    {
        $service = new BookBasDueDateService();

        $this->assertSame(
            '2026-11-11',
            $service->dueDate('2026-09-30', '2026-11-11')->toDateString(),
        );
    }
}
