<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\AuthAccess\Mail\DistributorAccountActivatedMail;
use App\Modules\Orders\Mail\OrderCreatedCustomerQuotationMail;
use App\Modules\Orders\Mail\OrderCreatedNotificationMail;
use App\Modules\Orders\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMail extends Command
{
    protected $signature = 'app:test-mail
        {to? : Correo destino para pruebas}
        {--type=all : all|activation|quotation|internal}';

    protected $description = 'Envia correos de prueba con las plantillas actuales';

    public function handle(): int
    {
        $to = (string) ($this->argument('to') ?: config('mail.from.address'));
        $type = strtolower((string) $this->option('type'));

        if ($to === '') {
            $this->error('Debes indicar un correo destino o configurar MAIL_FROM_ADDRESS.');

            return self::FAILURE;
        }

        if (! in_array($type, ['all', 'activation', 'quotation', 'internal'], true)) {
            $this->error("Tipo invalido: {$type}. Usa all|activation|quotation|internal.");

            return self::FAILURE;
        }

        $sent = [];

        if ($type === 'all' || $type === 'activation') {
            $user = User::query()
                ->with('distributor')
                ->whereNotNull('distributor_id')
                ->latest('id')
                ->first();

            if (! $user || ! $user->distributor) {
                $this->warn('No hay usuario distribuidor disponible para probar activation.');
            } else {
                Mail::to($to)->send(new DistributorAccountActivatedMail($user, $user->distributor));
                $sent[] = 'activation';
            }
        }

        if ($type === 'all' || $type === 'quotation' || $type === 'internal') {
            $order = Order::query()
                ->with('distributor')
                ->latest('id')
                ->first();

            if (! $order) {
                $this->warn('No hay pedidos disponibles para probar quotation/internal.');
            } else {
                $pdf = $this->samplePdf((string) $order->oc_number);

                if ($type === 'all' || $type === 'quotation') {
                    Mail::to($to)->send(new OrderCreatedCustomerQuotationMail($order, $pdf));
                    $sent[] = 'quotation';
                }

                if ($type === 'all' || $type === 'internal') {
                    Mail::to($to)->send(new OrderCreatedNotificationMail($order, $pdf));
                    $sent[] = 'internal';
                }
            }
        }

        if ($sent === []) {
            $this->error('No se envio ningun correo de prueba.');

            return self::FAILURE;
        }

        $this->info('Correos enviados a '.$to.': '.implode(', ', $sent));

        return self::SUCCESS;
    }

    private function samplePdf(string $reference): string
    {
        $reference = preg_replace('/[^A-Za-z0-9\\-]/', '', $reference) ?: 'CTC-DEMO';

        return <<<PDF
%PDF-1.1
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 200] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>
endobj
4 0 obj
<< /Length 56 >>
stream
BT
/F1 16 Tf
20 120 Td
(Prueba de correo {$reference}) Tj
ET
endstream
endobj
5 0 obj
<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
endobj
xref
0 6
0000000000 65535 f 
0000000010 00000 n 
0000000063 00000 n 
0000000122 00000 n 
0000000248 00000 n 
0000000354 00000 n 
trailer
<< /Size 6 /Root 1 0 R >>
startxref
424
%%EOF
PDF;
    }
}
