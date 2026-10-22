<?php

namespace App\Models;

use Database\Factories\SubscriberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ?string $email
 * @property ?string $phone
 * @property string $token
 * @property ?Carbon $confirmed_at
 * @property ?Carbon $unsubscribed_at
 */
class Subscriber extends Model
{
    /** @use HasFactory<SubscriberFactory> */
    use HasFactory;

    /**
     * Written only by the Newsletter domain actions with explicit attributes.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    /**
     * Subscribers who should get announcement emails.
     *
     * @param  Builder<self>  $query
     */
    public function scopeReceivingEmails(Builder $query): void
    {
        $query->whereNotNull('email')->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    public function isActive(): bool
    {
        return $this->unsubscribed_at === null;
    }

    public function awaitsConfirmation(): bool
    {
        return $this->email !== null && $this->confirmed_at === null && $this->isActive();
    }
}
