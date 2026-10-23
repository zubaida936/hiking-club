<?php

namespace App\Http\Requests\Site;

use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Shop\Data\CheckoutData;
use App\Domain\Shop\Enums\Fulfilment;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $delivery = $this->input('fulfilment') === Fulfilment::Delivery->value;

        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:'.Phone::PATTERN],
            'email' => ['nullable', 'string', 'email:rfc', 'max:190'],
            'fulfilment' => ['required', Rule::enum(Fulfilment::class)],
            'delivery_city' => [Rule::requiredIf($delivery), 'nullable', 'string', 'max:80'],
            'delivery_address' => [Rule::requiredIf($delivery), 'nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => PaymentMethod::available() !== []
                ? ['required', Rule::in(array_map(fn (PaymentMethod $m) => $m->value, PaymentMethod::available()))]
                : ['nullable'],
            'terms' => ['accepted'],
            // Honeypot: hidden from people, bots fill it in.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid phone number, e.g. +961 70 123 456.',
            'delivery_city.required' => 'Enter the city for the delivery.',
            'delivery_address.required' => 'Enter the delivery address.',
            'payment_method.required' => 'Choose how you will pay.',
            'terms.accepted' => 'Please accept the terms to place your order.',
        ];
    }

    public function toData(): CheckoutData
    {
        $text = fn (string $key) => $this->filled($key) ? $this->string($key)->squish()->toString() : null;

        return new CheckoutData(
            name: $this->string('name')->squish()->toString(),
            phone: (string) Phone::normalize($this->input('phone')),
            email: $this->filled('email') ? strtolower($this->string('email')->trim()->toString()) : null,
            fulfilment: Fulfilment::from($this->string('fulfilment')->toString()),
            deliveryCity: $text('delivery_city'),
            deliveryAddress: $text('delivery_address'),
            notes: $this->filled('notes') ? $this->string('notes')->trim()->toString() : null,
            paymentMethod: PaymentMethod::tryFrom((string) $this->input('payment_method')),
        );
    }
}
