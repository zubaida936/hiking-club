<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Site\CheckoutRequest as SiteCheckoutRequest;

/**
 * App checkout: the website's checkout fields and rules, plus the cart the app keeps on the phone.
 */
class CheckoutRequest extends SiteCheckoutRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + self::itemRules();
    }

    /**
     * @return array<string, mixed>
     */
    public static function itemRules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.variant_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.config('shop.max_quantity')],
        ];
    }

    /**
     * The cart as variant id => quantity.
     *
     * @return array<int, int>
     */
    public function quantities(): array
    {
        return self::quantitiesFrom((array) $this->input('items'));
    }

    /**
     * @param  array<mixed>  $items
     * @return array<int, int>
     */
    public static function quantitiesFrom(array $items): array
    {
        $quantities = [];
        foreach ($items as $item) {
            $quantities[(int) $item['variant_id']] = (int) $item['quantity'];
        }

        return $quantities;
    }
}
