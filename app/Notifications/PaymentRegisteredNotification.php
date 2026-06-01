<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PaymentRegisteredNotification extends Notification
{
    public function __construct(
        private readonly int $paymentId,
        private readonly string $receiptNumber,
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
            'type' => 'payment_registered',
            'title' => 'Cobro registrado',
            'message' => "Recibo {$this->receiptNumber} de {$this->clientName} por {$this->amountLabel}.",
            'icon' => 'fa-cash-register',
            'url' => route('payments.show', $this->paymentId),
        ];
    }
}
