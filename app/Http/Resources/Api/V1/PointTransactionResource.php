<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PointTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One line of the member's points ledger. Load registration.event first.
 *
 * @mixin PointTransaction
 */
class PointTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->registration?->event;

        return [
            'id' => $this->id,
            'type' => ['key' => $this->type->value, 'label' => $this->type->label()],
            'points' => $this->points,
            'description' => $this->description,
            'event' => $event === null ? null : ['slug' => $event->slug, 'title' => $event->title],
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
