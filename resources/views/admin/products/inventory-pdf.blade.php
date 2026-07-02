<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de saldos de inventario</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10px; margin: 16px; }
        .header { border-bottom: 2px solid #8DC543; margin-bottom: 10px; padding-bottom: 8px; }
        .title { margin: 0; font-size: 16px; font-weight: 700; color: #BC2983; }
        .meta { margin-top: 4px; color: #4b5563; font-size: 9.5px; }
        .meta p { margin: 1px 0; }
        .filters { margin-top: 8px; padding: 6px 8px; border: 1px solid #d1d5db; background: #f8fafc; }
        .filters-title { margin: 0 0 4px 0; font-size: 10px; font-weight: 700; color: #334155; text-transform: uppercase; }
        .filters ul { margin: 0; padding-left: 16px; }
        .filters li { margin: 1px 0; font-size: 9.5px; color: #334155; }
        .kpis { margin-top: 8px; width: 100%; border-collapse: collapse; }
        .kpis td { border: 1px solid #e5e7eb; padding: 5px; width: 25%; vertical-align: top; }
        .kpi-label { font-size: 9px; color: #64748b; text-transform: uppercase; }
        .kpi-value { margin-top: 2px; font-size: 12px; font-weight: 700; color: #111827; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 4px 5px; text-align: left; font-size: 9px; }
        .table th { background: #f5f7f9; font-size: 9.2px; }
        .num { text-align: right; white-space: nowrap; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    @php
        $formatMoney = static fn ($value): string => is_numeric($value)
            ? '$'.number_format((float) $value, 0, ',', '.')
            : 'Sin definir';

        $formatStock = static function ($value): string {
            if (!is_numeric($value)) return 'Sin definir';
            $n = (float) $value;
            $decimals = abs($n - round($n)) < 0.00001 ? 0 : 2;
            return number_format($n, $decimals, ',', '.');
        };
    @endphp

    <div class="header">
        <p class="title">Reporte de saldos de inventario</p>
        <div class="meta">
            <p>Generado: {{ $generatedAt->format('Y-m-d H:i') }} ({{ config('app.timezone') }})</p>
            <p>Usuario: {{ $generatedBy ?: 'Sistema' }}</p>
        </div>

        <div class="filters">
            <p class="filters-title">Filtros aplicados</p>
            @if($appliedFilters !== [])
                <ul>
                    @foreach($appliedFilters as $filterLabel)
                        <li>{{ $filterLabel }}</li>
                    @endforeach
                </ul>
            @else
                <p class="muted">Sin filtros activos.</p>
            @endif
        </div>
    </div>

    <table class="kpis">
        <tr>
            <td>
                <div class="kpi-label">Productos</div>
                <div class="kpi-value">{{ number_format((int) $totals['products_count'], 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="kpi-label">Filas exportadas</div>
                <div class="kpi-value">{{ number_format((int) $totals['rows_count'], 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="kpi-label">Stock conocido</div>
                <div class="kpi-value">{{ number_format((int) $totals['known_stock_rows'], 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="kpi-label">Stock total</div>
                @php $ts = (float) $totals['total_stock']; @endphp
                <div class="kpi-value">{{ number_format($ts, abs($ts - round($ts)) < 0.00001 ? 0 : 2, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Producto</th>
                <th>Marca</th>
                <th>Categoria</th>
                <th>Variante</th>
                <th class="num">Precio</th>
                <th class="num">Stock</th>
                <th>Estado</th>
                <th style="width:20px;text-align:center;" title="Ficha técnica">FT</th>
                <th style="width:20px;text-align:center;" title="Manual de usuario">MN</th>
                <th style="width:20px;text-align:center;" title="INVIMA">IN</th>
                <th style="width:20px;text-align:center;" title="Guía rápida">GP</th>
                <th style="width:20px;text-align:center;" title="Calibración">DC</th>
                <th style="width:20px;text-align:center;" title="Video">VD</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['sku'] }}</td>
                    <td>{{ $row['product_name'] }}</td>
                    <td>{{ $row['brand'] ?: '-' }}</td>
                    <td>{{ $row['category'] ?: '-' }}</td>
                    <td>
                        @if($row['variant_value'])
                            {{ $row['variant_attribute'] ?: 'Variante' }}: {{ $row['variant_value'] }}
                        @else
                            <span class="muted">No aplica</span>
                        @endif
                    </td>
                    <td class="num">{{ $formatMoney($row['price']) }}</td>
                    <td class="num">{{ $formatStock($row['stock']) }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td style="text-align:center;">{{ $row['documents']['tech_sheet'] ? '✓' : '—' }}</td>
                    <td style="text-align:center;">{{ $row['documents']['manual'] ? '✓' : '—' }}</td>
                    <td style="text-align:center;">{{ $row['documents']['invima'] ? '✓' : '—' }}</td>
                    <td style="text-align:center;">{{ $row['documents']['quick_guide'] ? '✓' : '—' }}</td>
                    <td style="text-align:center;">{{ $row['documents']['calibration_document'] ? '✓' : '—' }}</td>
                    <td style="text-align:center;">{{ $row['documents']['video'] ? '✓' : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="muted">No hay productos para los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

