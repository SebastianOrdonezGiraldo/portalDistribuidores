<?php

namespace App\Modules\Shared\Enums;

enum DistributorTier: string
{
    case Silver = 'plata';
    case Gold = 'oro';

    public function label(): string
    {
        return match ($this) {
            DistributorTier::Silver => 'ICM Plata',
            DistributorTier::Gold => 'ICM Oro',
        };
    }

    public function description(): string
    {
        return match ($this) {
            DistributorTier::Silver => 'Precio comercial Plata con redondeo al alza. Los carritos abiertos y pedidos nuevos utilizan este nivel hasta un cambio manual.',
            DistributorTier::Gold => 'Precio base (Oro) con descuento respecto al precio Plata de lista. Los carritos abiertos y pedidos nuevos utilizan este nivel hasta un cambio manual.',
        };
    }

    public function shortDescription(): string
    {
        return match ($this) {
            DistributorTier::Silver => 'Precio comercial Plata.',
            DistributorTier::Gold => 'Precio base Oro con descuento respecto a Plata.',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function presentation(): array
    {
        /** @var array<string, mixed> $presentation */
        $presentation = config('commerce.presentation.'.$this->value, []);

        return $presentation;
    }

    public function badgeLabel(): string
    {
        return (string) ($this->presentation()['badge_label'] ?? $this->label());
    }

    public function topbarLabel(): string
    {
        return (string) ($this->presentation()['topbar_label'] ?? $this->label());
    }

    public function accent(): string
    {
        return (string) ($this->presentation()['accent'] ?? 'neutral');
    }

    /**
     * ProductCard pricing mode: active-discount | locked-discount.
     */
    public function pricingMode(): string
    {
        return (string) ($this->presentation()['pricing_mode'] ?? 'locked-discount');
    }

    public function isActiveDiscount(): bool
    {
        return $this->pricingMode() === 'active-discount';
    }

    public function isLockedDiscount(): bool
    {
        return $this->pricingMode() === 'locked-discount';
    }

    /**
     * @return array<string, mixed>
     */
    public function priceToggle(): array
    {
        /** @var array<string, mixed> $toggle */
        $toggle = $this->presentation()['price_toggle'] ?? [];

        return $toggle;
    }

    /**
     * @return array<string, mixed>
     */
    public function banner(): array
    {
        /** @var array<string, mixed> $banner */
        $banner = $this->presentation()['banner'] ?? [];

        return $banner;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function kpis(): array
    {
        /** @var array<string, array<string, mixed>> $kpis */
        $kpis = $this->presentation()['kpis'] ?? [];

        return $kpis;
    }

    /**
     * @return array<string, mixed>
     */
    public function productCardCopy(): array
    {
        /** @var array<string, mixed> $copy */
        $copy = $this->presentation()['product_card'] ?? [];

        return $copy;
    }

    /**
     * PLACEHOLDER until a real promotions module exists.
     *
     * @return list<string>
     */
    public function benefits(): array
    {
        /** @var list<string> $benefits */
        $benefits = $this->presentation()['benefits'] ?? [];

        return array_values($benefits);
    }

    public function benefitsCount(): int
    {
        return count($this->benefits());
    }

    /**
     * @return array<string, mixed>
     */
    public function upgrade(): array
    {
        /** @var array<string, mixed> $upgrade */
        $upgrade = $this->presentation()['upgrade'] ?? [];

        return $upgrade;
    }

    public function showUpgradeCta(): bool
    {
        return (bool) ($this->banner()['show_upgrade_cta'] ?? false);
    }
}
