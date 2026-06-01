<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class LoanApprovedNotification extends Notification
{
    public function __construct(
        private readonly int $loanId,
        private readonly string $loanNumber,
        private readonly string $clientName,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'loan_approved',
            'title' => 'Préstamo aprobado',
            'message' => "El préstamo {$this->loanNumber} de {$this->clientName} fue aprobado y desembolsado.",
            'icon' => 'fa-circle-check',
            'url' => route('loans.show', $this->loanId),
        ];
    }
}
