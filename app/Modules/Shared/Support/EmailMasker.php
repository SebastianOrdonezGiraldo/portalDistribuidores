<?php

namespace App\Modules\Shared\Support;

/**
 * Masks emails for infrastructure logs (Ley 1581 / habeas data minimisation).
 */
final class EmailMasker
{
    public static function mask(string $email): string
    {
        if (! str_contains($email, '@')) {
            return '***';
        }

        [$local, $domain] = explode('@', $email, 2);

        $maskedLocal = substr($local, 0, min(2, strlen($local))).'***';

        $domainParts = explode('.', $domain, 2);
        $maskedDomain = substr($domainParts[0], 0, min(3, strlen($domainParts[0]))).'***'
            .(isset($domainParts[1]) ? '.'.$domainParts[1] : '');

        return $maskedLocal.'@'.$maskedDomain;
    }
}
