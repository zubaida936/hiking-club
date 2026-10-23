@php
    use App\Domain\Payments\Enums\PaymentMethod;

    $fee = (int) config('shop.delivery_fee_cents');
    $legend = 'font-display text-xl font-bold uppercase tracking-wide text-brand-900';
    $radio = 'flex cursor-pointer items-start gap-3 rounded-xl border border-slate-300 px-4 py-3 has-checked:border-brand-500 has-checked:bg-brand-50';
@endphp

<x-site.layout title="Checkout" :noindex="true">
    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6" x-data="{ fulfilment: @js(old('fulfilment', 'pickup')) }">
        <h1 class="font-display text-3xl font-bold uppercase tracking-wide text-brand-900">Checkout</h1>

        <div class="mt-8 grid gap-10 lg:grid-cols-3">
            <form method="POST" action="{{ route('checkout.store') }}" class="space-y-10 lg:col-span-2" novalidate>
                @csrf

                <fieldset class="space-y-5">
                    <legend class="{{ $legend }}">Your details</legend>
                    <x-site.field name="name" label="Full name" :value="$member?->name" required maxlength="120" autocomplete="name" />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-site.field name="phone" label="Phone (WhatsApp)" type="tel" :value="$member?->phone" required autocomplete="tel" placeholder="+961 70 123 456" />
                        <x-site.field name="email" label="Email" type="email" :optional="true" :value="$member?->email" autocomplete="email" hint="For your order confirmation." />
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="{{ $legend }}">How do you get it?</legend>
                    <div class="mt-4 grid gap-2">
                        <label class="{{ $radio }}">
                            <input type="radio" name="fulfilment" value="pickup" x-model="fulfilment" class="mt-1 text-brand-600 focus:ring-brand-500">
                            <span><span class="block font-semibold">Pickup at a hike · free</span><span class="block text-sm text-slate-500">{{ config('shop.pickup_note') }}</span></span>
                        </label>
                        <label class="{{ $radio }}">
                            <input type="radio" name="fulfilment" value="delivery" x-model="fulfilment" class="mt-1 text-brand-600 focus:ring-brand-500">
                            <span><span class="block font-semibold">Delivery · {{ \App\Models\Product::money($fee) }}</span><span class="block text-sm text-slate-500">{{ config('shop.delivery_note') }}</span></span>
                        </label>
                    </div>
                    @error('fulfilment') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    <div x-show="fulfilment === 'delivery'" class="mt-4 grid gap-5 sm:grid-cols-3">
                        <x-site.field name="delivery_city" label="City" maxlength="80" autocomplete="address-level2" />
                        <div class="sm:col-span-2"><x-site.field name="delivery_address" label="Address" maxlength="255" autocomplete="street-address" /></div>
                    </div>
                </fieldset>

                @if (PaymentMethod::available() !== [])
                    <fieldset>
                        <legend class="{{ $legend }}">How will you pay?</legend>
                        <div class="mt-4 grid gap-2">
                            @foreach (PaymentMethod::available() as $method)
                                <label class="{{ $radio }}">
                                    <input type="radio" name="payment_method" value="{{ $method->value }}" class="mt-1 text-brand-600 focus:ring-brand-500" @checked(old('payment_method') === $method->value)>
                                    <span class="font-semibold">{{ $method === PaymentMethod::Cash ? 'Cash on delivery / at pickup' : $method->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('payment_method') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </fieldset>
                @endif

                <div>
                    <label for="notes" class="block text-sm font-semibold text-slate-700">Notes <span class="font-normal text-slate-400">(optional)</span></label>
                    <textarea id="notes" name="notes" rows="2" maxlength="500" placeholder="e.g. which hike you'll pick it up at"
                              class="mt-1 block w-full rounded-xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500">{{ old('notes') }}</textarea>
                </div>

                <label class="flex items-start gap-3 text-sm text-slate-700">
                    <input type="checkbox" name="terms" value="1" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('terms'))>
                    <span>I agree that the club contacts me about this order by phone, WhatsApp or email. See our <a href="{{ route('legal.privacy') }}" target="_blank" class="text-brand-700 underline">privacy policy</a>.</span>
                </label>
                @error('terms') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <div class="hidden" aria-hidden="true">
                    <label for="website">Website</label>
                    <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>

                <button class="w-full rounded-full bg-accent-500 px-8 py-4 font-display text-lg font-bold uppercase tracking-wide text-white hover:bg-accent-600 sm:w-auto">Place order</button>
            </form>

            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-2xl bg-white p-6 shadow-lg ring-1 ring-slate-200">
                    <h2 class="font-display text-lg font-bold uppercase tracking-wide text-brand-900">Your order</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ($lines as $line)
                            <li class="flex justify-between gap-3">
                                <span>{{ $line['quantity'] }} × {{ $line['variant']->product->name }}{{ $line['variant']->name !== 'One size' ? ' ('.$line['variant']->name.')' : '' }}</span>
                                <span class="shrink-0 font-semibold">{{ \App\Models\Product::money($line['line_cents']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <dl class="mt-4 space-y-1 border-t border-slate-100 pt-4 text-sm">
                        <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ \App\Models\Product::money($subtotal) }}</dd></div>
                        <div class="flex justify-between"><dt>Delivery</dt><dd x-text="fulfilment === 'delivery' ? @js(\App\Models\Product::money($fee)) : 'Free'">Free</dd></div>
                        <div class="flex justify-between font-display text-lg font-bold text-brand-900"><dt>Total</dt>
                            <dd x-text="fulfilment === 'delivery' ? @js(\App\Models\Product::money($subtotal + $fee)) : @js(\App\Models\Product::money($subtotal))">{{ \App\Models\Product::money($subtotal) }}</dd></div>
                    </dl>
                    <a href="{{ route('cart') }}" class="mt-4 inline-block text-sm font-semibold text-brand-700 hover:underline">Edit cart</a>
                </div>
            </aside>
        </div>
    </div>
</x-site.layout>
