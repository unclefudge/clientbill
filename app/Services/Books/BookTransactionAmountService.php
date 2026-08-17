<?php

namespace App\Services\Books;

use App\Models\BookBusiness;

class BookTransactionAmountService
{
    public function calculate(
        BookBusiness $business,
        float $signedAmount,
        float $businessUsePercentage,
        string $gstTreatment,
    ): array {
        $businessAmount = round($signedAmount * ($businessUsePercentage / 100), 4);

        if (! $business->gst_registered || $gstTreatment !== 'gst_applicable' || (float) $business->gst_rate <= 0) {
            return [
                'business_amount' => $businessAmount,
                'net_amount' => $businessAmount,
                'gst_amount' => 0.0,
            ];
        }

        $absoluteBusinessAmount = abs($businessAmount);
        $gstRate = (float) $business->gst_rate;
        $gst = round($absoluteBusinessAmount * ($gstRate / (100 + $gstRate)), 2);
        $netAbsolute = round($absoluteBusinessAmount - $gst, 2);
        $net = $businessAmount < 0 ? -$netAbsolute : $netAbsolute;

        return [
            'business_amount' => $businessAmount,
            'net_amount' => $net,
            'gst_amount' => $gst,
        ];
    }
}
