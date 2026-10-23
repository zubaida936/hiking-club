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
 * Tells the shop team about a new order.
 */
class NewOrderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order)
    {
        $order->loadMissing('items');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "New shop order {$this->order->reference} · {$this->order->name}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.shop.new-order', with: ['adminUrl' => route('admin.orders.show', $this->order->id)]);
    }
}
