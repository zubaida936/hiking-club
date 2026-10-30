<?php

namespace App\Domain\Rewards;

/**
 * The code in a member's perk QR code: "PERK-{hiker id}-{reward key}-{signature}". The signature (HMAC with the
 * app key) means nobody can make a working code for another member's perk; organizers still check the name
 * they see against the person in front of them. The code never expires: the perk itself can only be used once.
 */
final readonly class PerkCode
{
    private const PREFIX = 'PERK';

    private const SIGNATURE_LENGTH = 16;

    public function __construct(private string $key) {}

    public static function fromConfig(): self
    {
        return new self((string) config('app.key'));
    }

    public function for(int $hikerId, string $rewardKey): string
    {
        $payload = self::PREFIX."-{$hikerId}-{$rewardKey}";

        return "{$payload}-{$this->sign($payload)}";
    }

    /**
     * The hiker id and reward key of a genuine code, or null for anything else (wrong format, edited, a booking QR).
     *
     * @return array{hiker_id: int, reward: string}|null
     */
    public function parse(string $code): ?array
    {
        if (preg_match('/^'.self::PREFIX.'-(\d+)-([a-z0-9_]+)-([a-f0-9]{'.self::SIGNATURE_LENGTH.'})$/', trim($code), $parts) !== 1) {
            return null;
        }

        if (! hash_equals($this->sign(self::PREFIX."-{$parts[1]}-{$parts[2]}"), $parts[3])) {
            return null;
        }

        return ['hiker_id' => (int) $parts[1], 'reward' => $parts[2]];
    }

    private function sign(string $payload): string
    {
        return substr(hash_hmac('sha256', $payload, $this->key), 0, self::SIGNATURE_LENGTH);
    }
}
