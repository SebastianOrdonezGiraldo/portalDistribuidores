<?php

namespace App\Console\Commands;

use App\Modules\Orders\Services\Payment\OrderPaymentService;
use Illuminate\Console\Command;

class ExpirePendingPaymentReservationsCommand extends Command
{
    protected $signature = 'payments:expire-pending';

    protected $description = 'Expira reservas de pago manual vencidas y restaura stock cancelando el pedido';

    public function handle(OrderPaymentService $paymentService): int
    {
        $result = $paymentService->expireOverdueReservations();

        $this->info("Expirados: {$result['expired']}. Fallidos: {$result['failed']}.");

        return self::SUCCESS;
    }
}
