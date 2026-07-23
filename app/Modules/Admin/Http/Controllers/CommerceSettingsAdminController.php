<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Http\Requests\UpdateCommerceSettingsRequest;
use App\Modules\Orders\Models\CommercePricingRule;
use App\Modules\Orders\Pricing\CommercePricingRulesProvider;
use App\Modules\Orders\Pricing\CommercePricingRulesService;
use App\Modules\Orders\Pricing\DistributorPriceCalculator;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CommerceSettingsAdminController extends Controller
{
    public function edit(CommercePricingRulesProvider $provider): View
    {
        $current = $provider->current();
        $calculator = new DistributorPriceCalculator($current);
        $exampleGoldCents = 10_000_000; // $100.000
        $exampleSilver = $calculator->calculate($exampleGoldCents, DistributorTier::Silver);

        $history = CommercePricingRule::query()
            ->with('createdBy:id,name,email')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('admin.settings.commerce', [
            'current' => $current,
            'history' => $history,
            'latestRule' => $history->first(),
            'exampleGoldDecimal' => '100000.00',
            'exampleSilverBeforeRounding' => $this->unroundedSilverDisplay($exampleGoldCents, $current->silverMarkupBasisPoints),
            'exampleSilverFinal' => $exampleSilver->silverPriceDecimal(),
            'exampleDifference' => $exampleSilver->unitSavingsDecimal(),
        ]);
    }

    public function update(
        UpdateCommerceSettingsRequest $request,
        CommercePricingRulesService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();

        $result = $service->publish(
            silverMarkupBasisPoints: $request->silverMarkupBasisPoints(),
            silverRoundingMultiple: $request->silverRoundingMultiple(),
            actor: $actor,
        );

        $message = $result->changed
            ? 'Reglas comerciales actualizadas. Los precios visibles y los carritos abiertos se recalcularán con la nueva configuración.'
            : 'No hubo cambios: la configuración enviada coincide con la regla vigente.';

        return redirect()
            ->route('admin.settings.commerce.edit')
            ->with('status', $message);
    }

    /**
     * Informative unrounded Plata amount for the admin preview (integer math only).
     */
    private function unroundedSilverDisplay(int $baseCents, int $markupBasisPoints): string
    {
        $percentageBase = 10_000;
        $factor = $percentageBase + $markupBasisPoints;
        $unrounded = intdiv(($baseCents * $factor) + $percentageBase - 1, $percentageBase);
        $pesos = intdiv($unrounded, 100);
        $cents = $unrounded % 100;

        return number_format($pesos, 0, ',', '.').($cents > 0 ? ','.str_pad((string) $cents, 2, '0', STR_PAD_LEFT) : '');
    }
}
