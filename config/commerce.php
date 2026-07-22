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
];
