<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Exceptions\PaymentException;
use App\Models\EventRegistration;

/**
 * The hiker tells us the transaction number of their Whish Money / OMT transfer,
 * so organizers can match it before marking the booking paid.
 */
final class ReportTransfer
{
    /**
     * @throws PaymentException
     */
    public function handle(EventRegistration $registration, string $transactionNumber): void
    {
        if ($registration->paid_at !== null) {
            throw PaymentException::alreadyPaid();
        }
        if (! $registration->payment_method?->isWalletTransfer()) {
            throw PaymentException::notAWalletTransfer();
        }

        $registration->update(['payment_reference' => $transactionNumber]);
    }
}
