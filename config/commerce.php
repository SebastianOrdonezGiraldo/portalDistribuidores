<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reglas comerciales de niveles de distribuidor
    |--------------------------------------------------------------------------
    |
    | Valores usados por el motor central de precios (DistributorPriceCalculator)
    | para derivar el precio Plata a partir del precio base (Oro).
    |
    | Diseñado para ser reemplazado en el futuro por valores administrables desde
    | base de datos sin cambiar la lógica de cálculo.
    |
    */
    'tiers' => [
        // Oro tiene un descuento del 5% respecto al precio Plata.
        'gold_discount_percent' => 5,

        // El precio Plata se redondea hacia arriba al siguiente múltiplo de este
        // valor (en pesos). Un múltiplo exacto no se incrementa.
        'silver_rounding_multiple' => 1000,
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
                'interactive' => true,
                'label_on' => 'Mostrando precios Oro',
                'label_off' => 'Mostrar precios Oro',
                'locked_label' => null,
            ],
            'banner' => [
                'greeting' => '¡Bienvenido, :name!',
                'headline' => 'Tu Nivel Oro ya está activo en todo el catálogo',
                'subtext' => 'Precios preferenciales, atención prioritaria y beneficios exclusivos en cada pedido.',
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
                    'label' => 'Descuento Oro activo',
                    'value_template' => ':percent% en todos los productos',
                    'hint' => 'Aplicado automáticamente',
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
                    'cta_label' => null,
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
                'Precios preferenciales en todo el catálogo',
                'Atención prioritaria en pedidos',
                'Acceso anticipado a lanzamientos',
                'Condiciones comerciales preferentes',
                'Soporte dedicado de asesoría',
            ],
            'upgrade' => [
                'modal_title' => null,
                'modal_body' => null,
                'whatsapp_message' => null,
            ],
        ],

        'plata' => [
            'badge_label' => 'Cliente Plata',
            'topbar_label' => 'Nivel Plata',
            'accent' => 'silver',
            'pricing_mode' => 'locked-discount',
            'price_toggle' => [
                'interactive' => false,
                'label_on' => null,
                'label_off' => null,
                'locked_label' => 'Ver precios Oro',
            ],
            'banner' => [
                'greeting' => 'Hola, :name',
                'headline' => 'Sube a Nivel Oro y paga menos en cada pedido',
                'subtext' => 'Descuento en todo el catálogo, atención prioritaria y beneficios exclusivos para tu operación.',
                'show_upgrade_cta' => true,
                'cta_label' => 'Sube a Nivel Oro',
                'missed_savings_template' => 'Este mes podrías haber ahorrado :amount con Nivel Oro',
                'background_image' => 'images/tiers/banner-plata.jpg',
            ],
            'kpis' => [
                'savings' => [
                    'label' => 'Ahorro potencial este mes',
                    'value_suffix' => 'con Nivel Oro',
                    'empty_hint' => 'Haz un pedido este mes para ver cuánto podrías ahorrar',
                ],
                'discount' => [
                    'label' => 'Descuento Oro',
                    'value_template' => ':percent% disponible',
                    'hint' => 'Se activa al subir a Oro',
                    'locked' => true,
                ],
                'orders' => [
                    'label' => 'Pedidos realizados',
                    'value_suffix' => 'pedidos este mes',
                    'value_suffix_one' => 'pedido este mes',
                    'empty_hint' => 'Aún no tienes pedidos este mes',
                ],
                'benefits' => [
                    'label' => 'Con Nivel Oro obtienes',
                    'value_suffix' => 'beneficios exclusivos',
                    'hint' => 'Disponibles al subir a Oro',
                    'cta_label' => 'Quiero subir a Oro',
                ],
            ],
            'product_card' => [
                'price_badge' => 'Precio Oro',
                'savings_template' => 'Ahorrarías :amount con Oro',
                'standard_label' => 'Tu precio',
                'tier_price_label' => 'Precio Oro',
            ],
            // PLACEHOLDER: beneficios del nivel Oro que se desbloquean al subir.
            'benefits' => [
                'Precios preferenciales en todo el catálogo',
                'Atención prioritaria en pedidos',
                'Acceso anticipado a lanzamientos',
                'Condiciones comerciales preferentes',
                'Soporte dedicado de asesoría',
            ],
            'upgrade' => [
                'modal_title' => 'Sube a Nivel Oro',
                'modal_body' => 'El Nivel Oro te da descuento en todos los productos del catálogo, atención prioritaria y beneficios exclusivos de tu nivel. Escríbenos por WhatsApp para conocer los requisitos y activar tu upgrade.',
                'whatsapp_message' => 'Hola, soy Cliente Plata en el portal y quiero conocer cómo subir a Nivel Oro.',
            ],
        ],
    ],

    'support' => [
        'whatsapp_number' => '573117479607',
    ],
];
