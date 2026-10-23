<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Exceptions\ShopException;
use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * What organizers (and customers, for the transfer number) do with an order after it's placed.
 */
final class ManageOrder
{
    /**
     * Moves the order forward (preparing, ready, completed); the customer is emailed when it's ready.
     *
     * @throws ShopException
     */
    public function setStatus(Order $order, OrderStatus $status, User $actor): void
    {
        if (! in_array($status, $order->status->next(), true)) {
            throw ShopException::notAllowed();
        }

        $order->update(['status' => $status, 'updated_by' => $actor->id]);

        if ($status === OrderStatus::Ready && $order->email !== null) {
            Mail::to($order->email)->queue(new OrderStatusMail($order));
        }
    }

    /**
     * Cancels the order and puts the items back in stock. A paid order then needs a refund (by hand).
     *
     * @throws ShopException
     */
    public function cancel(Order $order, ?string $reason, User $actor, bool $notify = true): void
    {
        if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Cancelled], true)) {
            throw ShopException::notAllowed();
        }

        DB::transaction(function () use ($order, $reason, $actor) {
            foreach ($order->items as $item) {
                if ($item->product_variant_id !== null) {
                    ProductVariant::whereKey($item->product_variant_id)->increment('stock', $item->quantity);
                }
            }

            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'updated_by' => $actor->id,
            ]);
        });

        if ($notify && $order->email !== null) {
            Mail::to($order->email)->queue(new OrderStatusMail($order));
        }
    }

    /**
     * @throws ShopException
     */
    public function setPaid(Order $order, bool $paid, User $actor): void
    {
        if ($order->isCancelled()) {
            throw ShopException::notAllowed();
        }

        $order->update(['paid_at' => $paid ? ($order->paid_at ?? now()) : null, 'updated_by' => $actor->id]);
    }

    /**
     * The customer reports the transaction number of their Whish / OMT transfer.
     *
     * @throws PaymentException
     */
    public function reportTransfer(Order $order, string $transactionNumber): void
    {
        if ($order->paid_at !== null) {
            throw PaymentException::alreadyPaid();
        }
        if (! $order->payment_method?->isWalletTransfer()) {
            throw PaymentException::notAWalletTransfer();
        }

        $order->update(['payment_reference' => $transactionNumber]);
    }
}
