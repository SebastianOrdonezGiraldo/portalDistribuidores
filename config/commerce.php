<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reglas comerciales de niveles de distribuidor (fallback)
    |--------------------------------------------------------------------------
    |
    | Valores de arranque seguro usados por CommercePricingRulesProvider cuando:
    | - la tabla commerce_pricing_rules aún no existe (pre-migración);
    | - no hay registros en la tabla.
    |
    | La fuente vigente en runtime es la última fila de commerce_pricing_rules.
    | No eliminar estas claves: permiten migrate/config:cache antes de migrar.
    |
    | Fórmula: Precio Plata = techo(Precio Oro × (1 + incremento)) + redondeo.
    |
    */
    'tiers' => [
        // Incremento porcentual del precio Plata sobre el precio Oro (fallback).
        'silver_markup_percent' => 5,

        // Múltiplo de redondeo del precio Plata en pesos (fallback).
        'silver_rounding_multiple' => 1000,

        // Pedido mínimo por nivel (fallback cuando aún no hay filas en DB).
        'silver_min_order_enabled' => true,
        'silver_min_order_amount' => 800_000,
        'gold_min_order_enabled' => false,
        'gold_min_order_amount' => 1_000_000,

        // Umbral para activar precios Oro (fallback).
        'gold_pricing_threshold_enabled' => true,
        'gold_pricing_threshold_amount' => 1_000_000,
        'gold_pricing_threshold_basis' => 'gold_candidate',
    ],

    /*
    |--------------------------------------------------------------------------
    | Presentación por nivel (UI)
    |--------------------------------------------------------------------------
    |
    | Copy, framing de KPIs, modo de pricing de ProductCard y CTA de upgrade.
    | Agregar un tercer nivel = nueva entrada aquí + case en DistributorTier.
    | Ninguna vista Blade debe ramificar por valor de tier; leer vía el Enum.
    |
    | pricing_mode:
    |   - active-discount: precio dual con descuento aplicado
    |   - locked-discount: precio dual con descuento potencial bloqueado
    |   - single: solo el precio efectivo del nivel actual
    |
    | benefits:
    |   PLACEHOLDER hasta que exista un módulo real de promociones. El KPI debe
    |   etiquetarse como "Beneficios de tu nivel" (u otra frase del config), nunca
    |   como "promociones activas".
    |
    */
    'presentation' => [
        'oro' => [
            'badge_label' => 'Cliente Oro',
            'topbar_label' => 'Nivel Oro',
            'accent' => 'gold',
            'pricing_mode' => 'active-discount',
            'price_toggle' => [
                'interactive' => false,
                'label_on' => null,
                'label_off' => null,
                'locked_label' => null,
            ],
            'banner' => [
                'greeting' => '¡Bienvenido, :name!',
                'headline' => 'Tu Nivel Oro ya está activo en todo el catálogo',
                'subtext' => 'Mantienes el precio de 2025, atención prioritaria y beneficios exclusivos en cada pedido.',
                'show_upgrade_cta' => false,
                'cta_label' => null,
                'missed_savings_template' => null,
                'background_image' => 'images/tiers/banner-oro.jpg',
            ],
            'kpis' => [
                'savings' => [
                    'label' => 'Ahorro acumulado',
                    'value_suffix' => 'vs. precio estándar',
                    'empty_hint' => 'Tus pedidos de este mes aún no registran ahorro',
                ],
                'discount' => [
                    'label' => 'Tu precio Oro',
                    'value_template' => 'Precio 2025',
                    'hint' => 'Se mantiene en todo el catálogo',
                    'locked' => false,
                ],
                'orders' => [
                    'label' => 'Pedidos realizados',
                    'value_suffix' => 'pedidos este mes',
                    'value_suffix_one' => 'pedido este mes',
                    'empty_hint' => 'Aún no tienes pedidos este mes',
                ],
                'benefits' => [
                    'label' => 'Beneficios de tu nivel',
                    'value_suffix' => 'beneficios incluidos',
                    'hint' => 'Incluidos en tu nivel',
                    'cta_label' => 'Ver mis beneficios',
                ],
            ],
            'product_card' => [
                'price_badge' => 'Precio Oro',
                'savings_template' => 'Ahorras :amount',
                'standard_label' => 'Precio estándar',
                'tier_price_label' => 'Precio Oro',
            ],
            // PLACEHOLDER: reemplazar cuando exista módulo de promociones.
            'benefits' => [
                'Mantienes el precio de 2025 en todo el catálogo',
                'Atención prioritaria en pedidos',
                'Acceso anticipado a lanzamientos',
                'Condiciones comerciales preferentes',
            ],
            'upgrade' => [
                'modal_title' => 'Como eres Cliente Oro',
                'modal_body' => 'Ya tienes activo el Nivel Oro: mantienes el precio de 2025, atención prioritaria y beneficios exclusivos.',
                'whatsapp_message' => null,
            ],
        ],

        'plata' => [
            'badge_label' => 'Cliente Plata',
            'topbar_label' => 'Nivel Plata',
            'accent' => 'silver',
            'pricing_mode' => 'single',
            'price_toggle' => [
                'interactive' => false,
                'label_on' => null,
                'label_off' => null,
                'locked_label' => null,
            ],
            'banner' => [
                'greeting' => 'Hola, :name',
                'headline' => 'Tu nivel Plata está activo',
                'subtext' => 'Compra con tu precio comercial Plata y las condiciones vigentes para tu operación.',
                'show_upgrade_cta' => false,
                'cta_label' => null,
                'missed_savings_template' => null,
                'background_image' => 'images/tiers/banner-plata.jpg',
            ],
            'kpis' => [
                'savings' => [
                    'label' => 'Ahorro',
                    'value_suffix' => null,
                    'empty_hint' => null,
                ],
                'discount' => [
                    'label' => 'Tu precio Plata',
                    'value_template' => 'Precio Plata',
                    'hint' => 'Tu precio comercial vigente',
                    'locked' => false,
                ],
                'orders' => [
                    'label' => 'Pedidos realizados',
                    'value_suffix' => 'pedidos este mes',
                    'value_suffix_one' => 'pedido este mes',
                    'empty_hint' => 'Aún no tienes pedidos este mes',
                ],
                'benefits' => [
                    'label' => 'Nivel comercial',
                    'value_suffix' => 'nivel activo',
                    'hint' => null,
                    'cta_label' => null,
                ],
            ],
            'product_card' => [
                'price_badge' => null,
                'savings_template' => null,
                'standard_label' => 'Tu precio',
                'tier_price_label' => 'Precio Plata',
            ],
            'benefits' => [],
            'upgrade' => [],
        ],
    ],

    'support' => [
        'whatsapp_number' => '573117479607',
    ],

    /*
    |--------------------------------------------------------------------------
    | Manual payment (Bancolombia / Nequi / Llave / QR)
    |--------------------------------------------------------------------------
    |
    | Bank details and optional public image paths (under /public) for QR cards.
    |
    */
    'payment' => [
        'manual_reservation_ttl_minutes' => (int) env('PAYMENT_MANUAL_RESERVATION_TTL_MINUTES', 45),
        'upload_token_ttl_minutes' => (int) env('PAYMENT_UPLOAD_TOKEN_TTL_MINUTES', 20),
        'receipt_max_size_kb' => (int) env('PAYMENT_RECEIPT_MAX_SIZE_KB', 10240),
        'methods' => [
            'bancolombia' => [
                'label' => 'Bancolombia',
                'lines' => [
                    'Cuenta de ahorros Bancolombia',
                    'Número: 75690965348',
                    'Titular: IMPORT CORPORAL MEDICAL SAS',
                ],
                'image' => null,
            ],
            'nequi' => [
                'label' => 'Nequi',
                'lines' => [
                    'Llave Nequi / Bre-B: 0092741326',
                    'Titular: IMPORT CORPORAL MEDICAL SAS',
                    'Escanea el QR desde la app de tu banco o Nequi.',
                ],
                'image' => 'images/payments/llave-qr.png',
            ],
            'llave' => [
                'label' => 'Llave',
                'lines' => [
                    'Llave: 0092741326',
                    'Titular: IMPORT CORPORAL MEDICAL SAS',
                    'En tu app bancaria: envío con llaves → escribe 0092741326.',
                ],
                'image' => 'images/payments/llave-qr.png',
            ],
            'qr' => [
                'label' => 'QR',
                'lines' => [
                    'Escanea el QR Negocios Nequi / Bre-B',
                    'Titular: IMPORT CORPORAL MEDICAL SAS',
                    'Llave asociada: 0092741326',
                ],
                'image' => 'images/payments/llave-qr.png',
            ],
        ],
    ],
];
