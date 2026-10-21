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
        CommercePricingRulesProvider $provider,
        CommercePricingRulesService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();

        $current = $provider->current();

        $result = $service->publish(
            // These fields remain persisted for compatibility with historical rules and
            // integrations, but they are no longer used by the active price calculator.
            silverMarkupBasisPoints: $request->silverMarkupBasisPoints($current->silverMarkupBasisPoints),
            silverRoundingMultiple: $request->silverRoundingMultiple($current->silverRoundingMultiple),
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
