<?php

namespace App\Modules\Orders\Support;

class PaymentReceiptUploadLimits
{
    public static function maxSizeKb(): int
    {
        return max(1, (int) config('commerce.payment.receipt_max_size_kb', 10240));
    }

    public static function maxSizeLabel(): string
    {
        $megabytes = self::maxSizeKb() / 1024;

        if (abs($megabytes - floor($megabytes)) < 0.00001) {
            return number_format($megabytes, 0, ',', '.').' MB';
        }

        return number_format($megabytes, 1, ',', '.').' MB';
    }
}
