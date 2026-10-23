<?php

namespace App\Domain\Payments\Enums;

enum PaymentMethod: string
{
    case Whish = 'whish';
    case Omt = 'omt';
    case Cash = 'cash';
    case Online = 'online';

    /**
     * Methods hikers can pick right now: the manual ones listed in config/payments.php,
     * plus online payment when a gateway is configured.
     *
     * @return list<self>
     */
    public static function available(): array
    {
        return array_values(array_filter(
            [self::Online, self::Whish, self::Omt, self::Cash],
            fn (self $method) => $method->isAvailable(),
        ));
    }

    public function isAvailable(): bool
    {
        if ($this !== self::Online) {
            return config("payments.methods.{$this->value}") !== null;
        }

        $gateway = config('payments.online_gateway');

        // The simulator must never take "payments" on the live site.
        return filled($gateway) && ! ($gateway === 'test' && app()->isProduction());
    }

    public function label(): string
    {
        return $this === self::Online ? 'Online payment' : (string) config("payments.methods.{$this->value}.label", ucfirst($this->value));
    }

    /**
     * Money sent to the club's wallet: the hiker gets the number and a transfer reference to fill in.
     */
    public function isWalletTransfer(): bool
    {
        return in_array($this, [self::Whish, self::Omt], true);
    }

    public function accountName(): ?string
    {
        return config("payments.methods.{$this->value}.account_name");
    }

    public function accountNumber(): ?string
    {
        return config("payments.methods.{$this->value}.account_number");
    }
}
