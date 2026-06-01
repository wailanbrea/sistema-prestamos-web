<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\LoanApprovedNotification;
use App\Notifications\LoanCreatedNotification;
use App\Notifications\PaymentRegisteredNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Genera notificaciones persistentes (bandeja) para eventos de negocio.
 * Destinatarios: usuarios activos de la empresa, excluyendo a quien ejecutó la
 * acción. Se envían por el canal `database` (sin cola, inmediato).
 */
class EventNotifier
{
    public function loanCreated(Loan $loan, ?int $actorId): void
    {
        $loan->loadMissing('client');

        $this->dispatch((int) $loan->company_id, $actorId, new LoanCreatedNotification(
            loanId: (int) $loan->id,
            loanNumber: (string) $loan->loan_number,
            clientName: (string) ($loan->client?->full_name ?? 'Cliente'),
            amountLabel: $this->money((float) $loan->principal_amount),
        ));
    }

    public function loanApproved(Loan $loan, ?int $actorId): void
    {
        $loan->loadMissing('client');

        $this->dispatch((int) $loan->company_id, $actorId, new LoanApprovedNotification(
            loanId: (int) $loan->id,
            loanNumber: (string) $loan->loan_number,
            clientName: (string) ($loan->client?->full_name ?? 'Cliente'),
        ));
    }

    public function paymentRegistered(Payment $payment, ?int $actorId): void
    {
        $payment->loadMissing('client');

        $this->dispatch((int) $payment->company_id, $actorId, new PaymentRegisteredNotification(
            paymentId: (int) $payment->id,
            receiptNumber: (string) $payment->receipt_number,
            clientName: (string) ($payment->client?->full_name ?? 'Cliente'),
            amountLabel: $this->money((float) $payment->amount),
        ));
    }

    private function dispatch(int $companyId, ?int $actorId, Notification $notification): void
    {
        $recipients = User::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->when($actorId !== null, fn ($query) => $query->whereKeyNot($actorId))
            ->get();

        if ($recipients->isNotEmpty()) {
            NotificationFacade::send($recipients, $notification);
        }
    }

    private function money(float $amount): string
    {
        return currency().' '.number_format($amount, 2);
    }
}
