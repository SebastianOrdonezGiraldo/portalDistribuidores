<?php

namespace App\View\Composers;

use App\Modules\Orders\Support\AdvisorWhatsappNormalizer;

/**
 * General support remains the safe contact for guests and incomplete advisor configuration.
 */
final class SupportContactData
{
    /**
     * @return array{supportWhatsappNumber:string|null,supportWhatsappUrl:string|null,supportPhone:string|null}
     */
    public static function forView(): array
    {
        $number = AdvisorWhatsappNormalizer::normalize((string) config('commerce.support.whatsapp_number'));

        return [
            'supportWhatsappNumber' => $number,
            'supportWhatsappUrl' => $number === null
                ? null
                : 'https://wa.me/'.$number.'?text='.rawurlencode('Hola, vengo desde el portal.'),
            'supportPhone' => $number === null ? null : '+'.$number,
        ];
    }
}
