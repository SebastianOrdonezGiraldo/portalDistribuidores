<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Http\Requests\UpdateCommerceSettingsRequest;
use App\Modules\Admin\Http\Requests\UpdateCommerceTierAdvisorsRequest;
use App\Modules\Orders\Models\CommercePricingRule;
use App\Modules\Orders\Pricing\CommercePricingRulesProvider;
use App\Modules\Orders\Pricing\CommercePricingRulesService;
use App\Modules\Orders\Services\CommerceTierAdvisorProvider;
use App\Modules\Orders\Services\CommerceTierAdvisorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CommerceSettingsAdminController extends Controller
{
    public function edit(
        CommercePricingRulesProvider $provider,
        CommerceTierAdvisorProvider $advisorProvider,
    ): View {
        $current = $provider->current();
        $history = CommercePricingRule::query()
            ->with('createdBy:id,name,email')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('admin.settings.commerce', [
            'current' => $current,
            'history' => $history,
            'latestRule' => $history->first(),
            'advisors' => $advisorProvider->all(),
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
            silverMinOrderEnabled: $request->silverMinOrderEnabled(),
            silverMinOrderAmount: $request->silverMinOrderAmount(),
            goldMinOrderEnabled: $request->goldMinOrderEnabled(),
            goldMinOrderAmount: $request->goldMinOrderAmount(),
            goldPricingThresholdEnabled: $request->goldPricingThresholdEnabled(),
            goldPricingThresholdAmount: $request->goldPricingThresholdAmount(),
            goldPricingThresholdBasis: $request->goldPricingThresholdBasis(),
        );

        $message = $result->changed
            ? 'Reglas comerciales actualizadas correctamente.'
            : 'No hubo cambios: la configuración enviada coincide con la regla vigente.';

        return redirect()
            ->route('admin.settings.commerce.edit')
            ->with('status', $message);
    }

    public function updateAdvisors(
        UpdateCommerceTierAdvisorsRequest $request,
        CommerceTierAdvisorService $service,
    ): RedirectResponse {
        /** @var array<string, array{advisor_name:string,advisor_email:string,advisor_whatsapp:string}> $advisors */
        $advisors = $request->validated('advisors');
        $service->update($advisors);

        return redirect()
            ->route('admin.settings.commerce.edit')
            ->with('status', 'Asesores comerciales actualizados correctamente.');
    }
}
