<?php

namespace Tests\Unit;

use App\Modules\Shared\Enums\OrderStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_cases_expose_all_current_statuses_in_order(): void
    {
        $this->assertSame([
            OrderStatus::Draft,
            OrderStatus::PendingApproval,
            OrderStatus::Submitted,
            OrderStatus::Sold,
            OrderStatus::Dispatched,
            OrderStatus::Delivered,
            OrderStatus::Cancelled,
            OrderStatus::Sending,
            OrderStatus::Sent,
            OrderStatus::Failed,
            OrderStatus::Rejected,
        ], OrderStatus::cases());
    }

    #[DataProvider('statusValueProvider')]
    public function test_each_case_exposes_expected_backed_value(OrderStatus $status, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $status->value);
    }

    #[DataProvider('statusLabelProvider')]
    public function test_label_returns_expected_text(OrderStatus $status, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $status->label());
    }

    #[DataProvider('statusBadgeClassProvider')]
    public function test_badge_class_returns_expected_css_class(OrderStatus $status, string $expectedClass): void
    {
        $this->assertSame($expectedClass, $status->badgeClass());
    }

    #[DataProvider('pendingReviewProvider')]
    public function test_is_pending_review_is_only_true_for_pending_approval(OrderStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->isPendingReview());
    }

    #[DataProvider('rejectedProvider')]
    public function test_is_rejected_is_only_true_for_rejected(OrderStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->isRejected());
    }

    #[DataProvider('canBeApprovedProvider')]
    public function test_can_be_approved_is_only_true_for_pending_approval(OrderStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->canBeApproved());
    }

    #[DataProvider('canBeEditedByCompanyProvider')]
    public function test_can_be_edited_by_company_matches_expected_statuses(OrderStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->canBeEditedByCompany());
    }

    #[DataProvider('canBeEditedByAdminProvider')]
    public function test_can_be_edited_by_admin_matches_expected_statuses(OrderStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->canBeEditedByAdmin());
    }

    #[DataProvider('requiresTransitionNoteProvider')]
    public function test_requires_transition_note_is_only_true_for_sold_and_dispatched(OrderStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->requiresTransitionNote());
    }

    #[DataProvider('nextAllowedStatusesProvider')]
    public function test_next_allowed_statuses_match_expected_contract(OrderStatus $status, array $expected): void
    {
        $this->assertSame($expected, $status->nextAllowedStatuses());
    }

    #[DataProvider('allowedTransitionProvider')]
    public function test_can_transition_to_accepts_allowed_transitions(OrderStatus $from, OrderStatus $to): void
    {
        $this->assertTrue($from->canTransitionTo($to));
    }

    #[DataProvider('disallowedTransitionProvider')]
    public function test_can_transition_to_rejects_invalid_and_self_transitions(OrderStatus $from, OrderStatus $to): void
    {
        $this->assertFalse($from->canTransitionTo($to));
    }

    public static function statusValueProvider(): array
    {
        return [
            'draft' => [OrderStatus::Draft, 'draft'],
            'pending_approval' => [OrderStatus::PendingApproval, 'pending_approval'],
            'submitted' => [OrderStatus::Submitted, 'submitted'],
            'sold' => [OrderStatus::Sold, 'sold'],
            'dispatched' => [OrderStatus::Dispatched, 'dispatched'],
            'delivered' => [OrderStatus::Delivered, 'delivered'],
            'cancelled' => [OrderStatus::Cancelled, 'cancelled'],
            'sending' => [OrderStatus::Sending, 'sending'],
            'sent' => [OrderStatus::Sent, 'sent'],
            'failed' => [OrderStatus::Failed, 'failed'],
            'rejected' => [OrderStatus::Rejected, 'rejected'],
        ];
    }

    public static function statusLabelProvider(): array
    {
        return [
            'draft' => [OrderStatus::Draft, 'Borrador'],
            'pending_approval' => [OrderStatus::PendingApproval, 'En revisión'],
            'submitted' => [OrderStatus::Submitted, 'Registrado'],
            'sold' => [OrderStatus::Sold, 'Vendido'],
            'dispatched' => [OrderStatus::Dispatched, 'Despachado'],
            'delivered' => [OrderStatus::Delivered, 'Entregado'],
            'cancelled' => [OrderStatus::Cancelled, 'Cancelado'],
            'sending' => [OrderStatus::Sending, 'En proceso (legado)'],
            'sent' => [OrderStatus::Sent, 'Completado (legado)'],
            'failed' => [OrderStatus::Failed, 'Fallido (legado)'],
            'rejected' => [OrderStatus::Rejected, 'Rechazado'],
        ];
    }

    public static function statusBadgeClassProvider(): array
    {
        return [
            'draft' => [OrderStatus::Draft, 'bg-amber-400'],
            'pending_approval' => [OrderStatus::PendingApproval, 'bg-violet-400'],
            'submitted' => [OrderStatus::Submitted, 'bg-emerald-400'],
            'sold' => [OrderStatus::Sold, 'bg-cyan-500'],
            'dispatched' => [OrderStatus::Dispatched, 'bg-sky-500'],
            'delivered' => [OrderStatus::Delivered, 'bg-blue-500'],
            'cancelled' => [OrderStatus::Cancelled, 'bg-slate-500'],
            'sending' => [OrderStatus::Sending, 'bg-sky-400'],
            'sent' => [OrderStatus::Sent, 'bg-blue-400'],
            'failed' => [OrderStatus::Failed, 'bg-rose-400'],
            'rejected' => [OrderStatus::Rejected, 'bg-red-500'],
        ];
    }

    public static function pendingReviewProvider(): array
    {
        return array_map(
            fn (OrderStatus $status) => [$status, $status === OrderStatus::PendingApproval],
            OrderStatus::cases(),
        );
    }

    public static function rejectedProvider(): array
    {
        return array_map(
            fn (OrderStatus $status) => [$status, $status === OrderStatus::Rejected],
            OrderStatus::cases(),
        );
    }

    public static function canBeApprovedProvider(): array
    {
        return array_map(
            fn (OrderStatus $status) => [$status, $status === OrderStatus::PendingApproval],
            OrderStatus::cases(),
        );
    }

    public static function requiresTransitionNoteProvider(): array
    {
        return array_map(
            fn (OrderStatus $status) => [$status, in_array($status, [OrderStatus::Sold, OrderStatus::Dispatched], true)],
            OrderStatus::cases(),
        );
    }

    public static function canBeEditedByCompanyProvider(): array
    {
        return array_map(
            fn (OrderStatus $status) => [$status, in_array($status, [OrderStatus::Draft, OrderStatus::PendingApproval, OrderStatus::Rejected], true)],
            OrderStatus::cases(),
        );
    }

    public static function canBeEditedByAdminProvider(): array
    {
        return array_map(
            fn (OrderStatus $status) => [$status, in_array($status, [OrderStatus::Draft, OrderStatus::PendingApproval, OrderStatus::Rejected, OrderStatus::Submitted], true)],
            OrderStatus::cases(),
        );
    }

    public static function nextAllowedStatusesProvider(): array
    {
        return [
            'draft' => [OrderStatus::Draft, []],
            'pending_approval' => [OrderStatus::PendingApproval, [OrderStatus::Submitted, OrderStatus::Rejected, OrderStatus::Cancelled]],
            'submitted' => [OrderStatus::Submitted, [OrderStatus::Sold, OrderStatus::Cancelled]],
            'sold' => [OrderStatus::Sold, [OrderStatus::Dispatched, OrderStatus::Cancelled]],
            'dispatched' => [OrderStatus::Dispatched, [OrderStatus::Delivered, OrderStatus::Cancelled]],
            'delivered' => [OrderStatus::Delivered, []],
            'cancelled' => [OrderStatus::Cancelled, []],
            'sending' => [OrderStatus::Sending, []],
            'sent' => [OrderStatus::Sent, []],
            'failed' => [OrderStatus::Failed, []],
            'rejected' => [OrderStatus::Rejected, [OrderStatus::PendingApproval, OrderStatus::Cancelled]],
        ];
    }

    public static function allowedTransitionProvider(): array
    {
        return [
            'pending approval to submitted' => [OrderStatus::PendingApproval, OrderStatus::Submitted],
            'pending approval to rejected' => [OrderStatus::PendingApproval, OrderStatus::Rejected],
            'pending approval to cancelled' => [OrderStatus::PendingApproval, OrderStatus::Cancelled],
            'submitted to sold' => [OrderStatus::Submitted, OrderStatus::Sold],
            'submitted to cancelled' => [OrderStatus::Submitted, OrderStatus::Cancelled],
            'sold to dispatched' => [OrderStatus::Sold, OrderStatus::Dispatched],
            'sold to cancelled' => [OrderStatus::Sold, OrderStatus::Cancelled],
            'dispatched to delivered' => [OrderStatus::Dispatched, OrderStatus::Delivered],
            'dispatched to cancelled' => [OrderStatus::Dispatched, OrderStatus::Cancelled],
            'rejected to pending approval' => [OrderStatus::Rejected, OrderStatus::PendingApproval],
            'rejected to cancelled' => [OrderStatus::Rejected, OrderStatus::Cancelled],
        ];
    }

    public static function disallowedTransitionProvider(): array
    {
        return [
            'self transition pending approval' => [OrderStatus::PendingApproval, OrderStatus::PendingApproval],
            'self transition sold' => [OrderStatus::Sold, OrderStatus::Sold],
            'draft to submitted' => [OrderStatus::Draft, OrderStatus::Submitted],
            'submitted to delivered' => [OrderStatus::Submitted, OrderStatus::Delivered],
            'submitted to rejected' => [OrderStatus::Submitted, OrderStatus::Rejected],
            'sold to delivered' => [OrderStatus::Sold, OrderStatus::Delivered],
            'dispatched to sold' => [OrderStatus::Dispatched, OrderStatus::Sold],
            'delivered to cancelled' => [OrderStatus::Delivered, OrderStatus::Cancelled],
            'rejected to submitted' => [OrderStatus::Rejected, OrderStatus::Submitted],
            'legacy sending to sent' => [OrderStatus::Sending, OrderStatus::Sent],
        ];
    }
}
