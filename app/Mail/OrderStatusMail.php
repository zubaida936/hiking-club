<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Order ready (for pickup / out for delivery) or cancelled.
 */
class OrderStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order)
    {
        $order->loadMissing('items');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your order {$this->order->reference}: {$this->order->statusLabel()}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.shop.status', with: ['orderUrl' => route('orders.show', $this->order)]);
    }
}
