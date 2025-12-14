<?php

namespace App\Support;

use Carbon\Carbon;

class FinancialYear
{
    public static function fromDate(Carbon $date): string
    {
        $year = $date->year;

        return $date->month < 7
            ? ($year - 1) . '/' . $year
            : $year . '/' . ($year + 1);
    }
}
