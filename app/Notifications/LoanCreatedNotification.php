<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class LoanCreatedNotification extends Notification
{
    public function __construct(
        private readonly int $loanId,
        private readonly string $loanNumber,
        private readonly string $clientName,
        private readonly string $amountLabel,
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
            'type' => 'loan_created',
            'title' => 'Nuevo préstamo',
            'message' => "Préstamo {$this->loanNumber} de {$this->clientName} por {$this->amountLabel}.",
            'icon' => 'fa-file-invoice-dollar',
            'url' => route('loans.show', $this->loanId),
        ];
    }
}
