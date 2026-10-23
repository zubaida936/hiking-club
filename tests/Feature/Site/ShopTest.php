<?php

use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Shop\Enums\Fulfilment;
use App\Domain\Shop\Enums\OrderStatus;
use App\Mail\NewOrderMail;
use App\Mail\OrderPlacedMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    config([
        'shop.delivery_fee_cents' => 400,
        'shop.notify_email' => 'shop@hikingclub.test',
        'payments.methods.whish.account_name' => 'Hiking Club SAL',
        'payments.methods.whish.account_number' => '+961 71 222 333',
        'payments.online_gateway' => null,
    ]);
});

function checkoutForm(array $overrides = []): array
{
    return [
        'name' => 'Rima Haddad',
        'phone' => '+961 70 123 456',
        'email' => 'rima@example.com',
        'fulfilment' => 'pickup',
        'payment_method' => 'cash',
        'terms' => '1',
        ...$overrides,
    ];
}

function variantOf(Product $product, ?string $name = null): ProductVariant
{
    return $product->variants()->when($name, fn ($q) => $q->where('name', $name))->firstOrFail();
}

describe('listing', function () {
    it('shows active products with price, sale and stock badges', function () {
        Product::factory()->withStock(20)->create(['name' => 'Trail Cap', 'price_cents' => 1500]);
        Product::factory()->withStock(3)->create(['name' => 'Rain Jacket', 'price_cents' => 8000, 'sale_price_cents' => 6000]);
        Product::factory()->withStock(0)->create(['name' => 'Trail Socks']);
        Product::factory()->withStock(5)->create(['name' => 'Secret Prototype', 'is_active' => false]);

        $this->get(route('shop.index'))->assertOk()
            ->assertSeeInOrder(['Trail Cap', '$15', 'In stock'])
            ->assertSeeInOrder(['Sale', 'Rain Jacket', '$60', '$80', 'Only 3 left'])
            ->assertSeeInOrder(['Trail Socks', 'Out of stock'])
            ->assertDontSee('Secret Prototype');
    });

    it('filters by category and ignores unknown ones', function () {
        $footwear = ProductCategory::where('slug', 'footwear')->firstOrFail();
        Product::factory()->withStock(5)->create(['name' => 'Hiking Boots', 'product_category_id' => $footwear->id]);
        Product::factory()->withStock(5)->create(['name' => 'Trail Cap']);

        $this->get(route('shop.index', ['category' => 'footwear']))->assertSee('Hiking Boots')->assertDontSee('Trail Cap');
        $this->get(route('shop.index', ['category' => 'nope']))->assertOk()->assertSee('Hiking Boots')->assertSee('Trail Cap');
    });
});

describe('product page', function () {
    it('shows sizes, disables sold-out ones and warns on low stock', function () {
        $product = Product::factory()->withStock(['S' => 0, 'M' => 2, 'L' => 12])->create([
            'name' => 'Club T-shirt', 'description' => "Soft cotton.\n\nMachine washable.", 'tags' => ['Breathable'],
        ]);

        $this->get(route('shop.show', $product))->assertOk()
            ->assertSee('Club T-shirt')->assertSee('Breathable')->assertSee('Soft cotton.')->assertSee('Machine washable.')
            ->assertSee('(2 left)')
            ->assertSee('value="'.variantOf($product, 'S')->id.'" class="sr-only" disabled', false)
            ->assertSee('Add to cart');
    });

    it('says when everything is sold out and hides inactive products', function () {
        $soldOut = Product::factory()->withStock(0)->create();
        $hidden = Product::factory()->withStock(5)->create(['is_active' => false]);

        $this->get(route('shop.show', $soldOut))->assertSee('Out of stock')->assertDontSee('Add to cart');
        $this->get(route('shop.show', $hidden))->assertNotFound();
    });
});

describe('cart', function () {
    it('adds items, shows the header counter and totals', function () {
        $cap = Product::factory()->withStock(10)->create(['name' => 'Trail Cap', 'price_cents' => 1500]);
        $jacket = Product::factory()->withStock(['M' => 4])->create(['name' => 'Rain Jacket', 'price_cents' => 8000, 'sale_price_cents' => 6000]);

        $this->from(route('shop.show', $cap))->post(route('cart.add'), ['variant_id' => variantOf($cap)->id, 'quantity' => 2])
            ->assertRedirect(route('shop.show', $cap))->assertSessionHas('success', 'Trail Cap added to your cart.');
        $this->post(route('cart.add'), ['variant_id' => variantOf($jacket)->id, 'quantity' => 1]);

        $this->get(route('home'))->assertSee('aria-label="Cart (3 items)"', false);
        $this->get(route('cart'))->assertOk()
            ->assertSeeInOrder(['Trail Cap', '$30', 'Rain Jacket', 'M', '$60'])
            ->assertSee('$90');
    });

    it('refuses more than the stock, sold-out sizes and hidden products', function () {
        $product = Product::factory()->withStock(['S' => 0, 'M' => 3])->create();
        $hidden = Product::factory()->withStock(5)->create(['is_active' => false]);

        $this->post(route('cart.add'), ['variant_id' => variantOf($product, 'M')->id, 'quantity' => 2]);
        $this->post(route('cart.add'), ['variant_id' => variantOf($product, 'M')->id, 'quantity' => 2])
            ->assertSessionHas('error', 'Only 3 left in this size (2 already in your cart).');
        $this->post(route('cart.add'), ['variant_id' => variantOf($product, 'S')->id, 'quantity' => 1])
            ->assertSessionHas('error', 'Sorry, this size is out of stock.');
        $this->post(route('cart.add'), ['variant_id' => variantOf($hidden)->id, 'quantity' => 1])
            ->assertSessionHas('error', 'Sorry, this size is out of stock.');
        $this->post(route('cart.add'), ['quantity' => 1])->assertSessionHasErrors(['variant_id' => 'Choose a size.']);

        expect(session('shop.cart'))->toBe([variantOf($product, 'M')->id => 2]);
    });

    it('updates quantities and removes lines', function () {
        $a = Product::factory()->withStock(10)->create();
        $b = Product::factory()->withStock(10)->create();
        $this->post(route('cart.add'), ['variant_id' => variantOf($a)->id, 'quantity' => 1]);
        $this->post(route('cart.add'), ['variant_id' => variantOf($b)->id, 'quantity' => 1]);

        $this->post(route('cart.update'), ['quantities' => [variantOf($a)->id => 4, variantOf($b)->id => 0]])
            ->assertRedirect(route('cart'));
        expect(session('shop.cart'))->toBe([variantOf($a)->id => 4]);

        $this->post(route('cart.remove', variantOf($a)))->assertRedirect(route('cart'));
        expect(session('shop.cart'))->toBe([]);
        $this->get(route('checkout'))->assertRedirect(route('cart'));
    });
});

describe('checkout', function () {
    beforeEach(function () {
        $this->product = Product::factory()->withStock(['M' => 5])->create(['name' => 'Rain Jacket', 'price_cents' => 8000, 'sale_price_cents' => 6000]);
        $this->variant = variantOf($this->product);
        $this->post(route('cart.add'), ['variant_id' => $this->variant->id, 'quantity' => 2]);
    });

    it('places a pickup order, takes the stock and emails both sides', function () {
        $this->get(route('checkout'))->assertOk()->assertSee('$120');

        $response = $this->post(route('checkout.store'), checkoutForm(['notes' => 'Size check please']));
        $order = Order::sole();
        $response->assertRedirect(route('orders.show', $order));

        expect($order)
            ->status->toBe(OrderStatus::New)
            ->fulfilment->toBe(Fulfilment::Pickup)
            ->subtotal_cents->toBe(12000)->delivery_fee_cents->toBe(0)->total_cents->toBe(12000)
            ->phone->toBe('+96170123456')->hiker_id->not->toBeNull()
            ->and($order->items->sole())->product_name->toBe('Rain Jacket')->variant_name->toBe('M')->unit_price_cents->toBe(6000)->quantity->toBe(2)
            ->and($this->variant->fresh()->stock)->toBe(3)
            ->and(session('shop.cart'))->toBeNull();

        Mail::assertQueued(OrderPlacedMail::class, fn ($mail) => $mail->hasTo('rima@example.com'));
        Mail::assertQueued(NewOrderMail::class, fn ($mail) => $mail->hasTo('shop@hikingclub.test'));

        $this->get(route('orders.show', $order))->assertOk()
            ->assertSee($order->reference)->assertSee('Thanks for your order!')
            ->assertSee('2 × Rain Jacket (M)')->assertSee('Pay <strong>$120</strong> in cash when you collect your order', false);
    });

    it('adds the delivery fee and requires the address', function () {
        $this->post(route('checkout.store'), checkoutForm(['fulfilment' => 'delivery']))
            ->assertSessionHasErrors(['delivery_city' => 'Enter the city for the delivery.', 'delivery_address' => 'Enter the delivery address.']);

        $this->post(route('checkout.store'), checkoutForm(['fulfilment' => 'delivery', 'delivery_city' => 'Jounieh', 'delivery_address' => 'Main road, bldg 4']));

        expect(Order::sole())->delivery_fee_cents->toBe(400)->total_cents->toBe(12400)->delivery_city->toBe('Jounieh');
    });

    it('validates the form', function () {
        $this->post(route('checkout.store'), checkoutForm(['phone' => '123', 'terms' => null, 'payment_method' => null, 'website' => 'spam']))
            ->assertSessionHasErrors(['phone', 'terms', 'payment_method', 'website']);
        expect(Order::count())->toBe(0);
    });

    it('never oversells when stock ran out meanwhile', function () {
        $this->variant->update(['stock' => 1]);

        $this->post(route('checkout.store'), checkoutForm())
            ->assertRedirect(route('cart'))
            ->assertSessionHas('error', 'Sorry, only 1 left of Rain Jacket (M). Lower the quantity in your cart to continue.');

        expect(Order::count())->toBe(0)->and($this->variant->fresh()->stock)->toBe(1);
        $this->get(route('cart'))->assertSee('Only 1 left');
    });

    it('lets the customer send a wallet transfer number', function () {
        $this->post(route('checkout.store'), checkoutForm(['payment_method' => 'whish']));
        $order = Order::sole();

        $this->get(route('orders.show', $order))->assertSee('Whish Money')->assertSee('+961 71 222 333')->assertSee($order->reference);
        $this->post(route('orders.transfer', $order), ['payment_reference' => ' 99887766 '])
            ->assertSessionHas('status', "Thanks! We'll confirm your payment soon.");

        expect($order->fresh())->payment_reference->toBe('99887766')->paid_at->toBeNull();
        $this->get(route('orders.show', $order))->assertSee('We received transaction number');
    });

    it('pays online through the test gateway', function () {
        config(['payments.online_gateway' => 'test']);
        $this->post(route('checkout.store'), checkoutForm(['payment_method' => 'online']));
        $order = Order::sole();

        $this->get(route('orders.show', $order))->assertSee('Pay $120 now');
        $checkout = $this->post(route('orders.pay', $order))->assertRedirect()->headers->get('Location');
        $payment = Payment::sole();
        expect($payment)->order_id->toBe($order->id)->event_registration_id->toBeNull()->amount_cents->toBe(12000);

        $this->get($checkout)->assertOk()->assertSee($order->reference);
        $this->post(route('payments.test.simulate', $payment->external_id), ['outcome' => 'pay']);
        $this->get(route('payments.return', $payment->external_id))->assertRedirect(route('orders.show', $order));

        expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded)
            ->and($order->fresh())->paid_at->not->toBeNull()->payment_reference->toStartWith('TEST-');
        $this->get(route('orders.show', $order))->assertSee('✓ Paid (Online payment)', false);
        $this->post(route('orders.pay', $order))->assertSessionHas('error');
    });

    it('keeps order pages behind their secret link', function () {
        $this->post(route('checkout.store'), checkoutForm());
        $order = Order::sole();

        $this->get('/orders/'.$order->id)->assertNotFound();
        $this->get('/orders/'.$order->reference)->assertNotFound();
    });
});
