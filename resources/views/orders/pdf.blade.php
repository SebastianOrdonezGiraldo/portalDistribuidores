<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotizacion al cliente {{ $order->oc_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10.5px; margin: 18px; }
        .header { border-bottom: 2px solid #8DC543; margin-bottom: 8px; padding-bottom: 6px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .logo { width: 250px; height: auto; }
        .title-box { text-align: right; }
        .title-label { margin: 0; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; }
        .title-doc { margin: 1px 0 0 0; font-size: 16px; font-weight: 700; color: #BC2983; }
        .title-date { margin: 2px 0 0 0; font-size: 10px; color: #4b5563; }
        .client { width: 100%; border: 1px solid #d1d5db; border-collapse: collapse; margin: 6px 0; }
        .client td { border: 1px solid #e5e7eb; padding: 4px 6px; font-size: 9.8px; }
        .label { width: 18%; font-weight: 700; background: #f8fafc; }
        .items { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .items th, .items td { border: 1px solid #d1d5db; padding: 5px; text-align: left; }
        .items th { background: #f5f7f9; font-size: 10px; }
        .num { text-align: right; white-space: nowrap; }
        .notes { margin-top: 10px; font-size: 10px; line-height: 1.35; }
        .notes p { margin: 2px 0; }
        .vigencia { margin-top: 8px; font-size: 10.5px; font-weight: 700; }
    </style>
</head>
<body>

    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 68%;">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Import Corporal Medical SAS" class="logo">
                    @else
                        <strong>Import Corporal Medical SAS</strong>
                    @endif
                </td>
                <td class="title-box" style="width: 32%;">
                    <p class="title-label">Cotizacion al cliente</p>
                    <p class="title-doc">{{ $order->oc_number }}</p>
                    <p class="title-date">Fecha: {{ $order->created_at?->setTimezone(config('app.timezone'))->format('Y-m-d H:i') }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table class="client">
        <tr>
            <td class="label">Razón Social</td>
            <td>{{ $order->company_name ?? '-' }}</td>
            <td class="label">NIT/Cédula</td>
            <td>{{ $order->company_nit ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Correo</td>
            <td>{{ $order->contact_email ?? '-' }}</td>
            <td class="label">Contacto</td>
            <td>{{ $order->contact_name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Dirección</td>
            <td>{{ $order->company_address ?? '-' }}</td>
            <td class="label">Ciudad / Depto</td>
            <td>{{ $order->city ?? '-' }} / {{ $order->department ?? '-' }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Producto</th>
                <th class="num">Cant</th>
                <th>Valor unit (sin IVA)</th>
                <th>IVA</th>
                <th>Valor total unitario</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lineItems as $line)
                <tr>
                    <td>{{ $line['item']->sku_snapshot }}</td>
                    <td>
                        {{ $line['item']->product_name_snapshot }}
                        @if($line['item']->variant_value_snapshot)
                            ({{ $line['item']->variant_attribute_snapshot ?? 'Variante' }}: {{ $line['item']->variant_value_snapshot }})
                        @endif
                    </td>
                    <td class="num">{{ (int) $line['item']->qty }}</td>
                    <td class="num">${{ number_format($line['valorUnit'], 0, ',', '.') }}</td>
                    <td class="num">${{ number_format($line['valorIva'], 0, ',', '.') }}</td>
                    <td class="num">${{ number_format($line['valorTotal'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="num">Total CTC</th>
                <th class="num">${{ number_format($totalFinal, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="notes">
        <p>* La cotizacion tiene validez de (1) un dia a partir de su emision, pasado el tiempo se debe verificar disponibilidad de los productos.</p>
        <p>* Somos beneficiarios de la Ley ZESE en el 6 ano, por favor aplicar retencion al 50% (1.25).</p>
        <p>* Envio gratuito a nivel nacional por compras superiores a $2.500.000 (aplica terminos y condiciones para algunos productos).</p>
        <p class="vigencia">-----VIGENCIA DE LA COTIZACION------</p>
        <p><strong>Validez de la oferta:</strong> 1 dia</p>
    </div>
</body>
</html>
