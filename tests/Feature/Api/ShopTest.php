<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\Shop\Enums\OrderStatus;
use App\Mail\NewOrderMail;
use App\Mail\OrderPlacedMail;
use App\Models\Hiker;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    config([
        'shop.delivery_fee_cents' => 400,
        'shop.notify_email' => 'shop@hikingclub.test',
        'payments.online_gateway' => null,
    ]);
});

function apiVariant(Product $product, ?string $name = null): ProductVariant
{
    return $product->variants()->when($name, fn ($q) => $q->where('name', $name))->firstOrFail();
}

function apiOrder(array $items, array $overrides = []): array
{
    return [
        'name' => 'Rima Haddad',
        'phone' => '+961 70 123 456',
        'email' => 'rima@example.com',
        'fulfilment' => 'pickup',
        'payment_method' => 'cash',
        'terms' => true,
        'items' => $items,
        ...$overrides,
    ];
}

describe('products', function () {
    it('lists active products with price, sale and stock', function () {
        $shirt = Product::factory()->withStock(['S' => 0, 'M' => 3])->create(['name' => 'Club T-shirt', 'price_cents' => 2000, 'sale_price_cents' => 1500, 'position' => 1]);
        Product::factory()->withStock(20)->create(['name' => 'Trail Cap', 'position' => 2]);
        Product::factory()->withStock(5)->create(['is_active' => false]);

        $this->getJson(route('api.v1.shop.products'))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Club T-shirt', 'Trail Cap'])
            ->assertJsonPath('data.0.price', ['cents' => 1500, 'formatted' => Product::money(1500)])
            ->assertJsonPath('data.0.regular_price', ['cents' => 2000, 'formatted' => Product::money(2000)])
            ->assertJsonPath('data.0.stock', ['total' => 3, 'sold_out' => false, 'low' => true])
            ->assertJsonPath('data.0.variants.*.name', ['S', 'M'])
            ->assertJsonPath('data.0.variants.0.stock', 0)
            ->assertJsonPath('data.0.web_url', route('shop.show', $shirt))
            ->assertJsonPath('data.1.regular_price', null);
    });

    it('filters by category', function () {
        $footwear = ProductCategory::firstOrCreate(['slug' => 'footwear'], ['name' => 'Footwear']);
        $boots = Product::factory()->withStock()->create(['product_category_id' => $footwear->id]);
        Product::factory()->withStock()->create();

        $this->getJson(route('api.v1.shop.categories'))->assertJsonFragment(['slug' => 'footwear', 'name' => 'Footwear']);
        $this->getJson(route('api.v1.shop.products', ['category' => 'footwear']))->assertJsonPath('data.*.id', [$boots->id]);
        $this->getJson(route('api.v1.shop.products', ['category' => 'nope']))->assertJsonValidationErrors('category');
    });

    it('shows one product and hides inactive or deleted ones', function () {
        $product = Product::factory()->withStock(['S' => 2, 'M' => 0])->create();
        $hidden = Product::factory()->withStock()->create(['is_active' => false]);
        $deleted = Product::factory()->withStock()->create();
        $deleted->delete();

        $this->getJson(route('api.v1.shop.products.show', $product))->assertOk()->assertJsonPath('data.slug', $product->slug);
        $this->getJson(route('api.v1.shop.products.show', $hidden))->assertNotFound();
        $this->getJson("/api/v1/shop/products/{$deleted->slug}")->assertNotFound();
    });
});

describe('cart', function () {
    it('prices the phone cart with current prices and stock', function () {
        $shirt = Product::factory()->withStock(['M' => 2])->create(['price_cents' => 2000, 'sale_price_cents' => 1500]);
        $cap = Product::factory()->withStock(10)->create(['price_cents' => 1200]);
        $gone = Product::factory()->withStock(5)->create(['is_active' => false]);

        $this->postJson(route('api.v1.shop.cart'), ['items' => [
            ['variant_id' => apiVariant($shirt)->id, 'quantity' => 3],
            ['variant_id' => apiVariant($cap)->id, 'quantity' => 2],
            ['variant_id' => apiVariant($gone)->id, 'quantity' => 1],
        ]])
            ->assertOk()
            ->assertJsonPath('data.lines.0.unit_price.cents', 1500)
            ->assertJsonPath('data.lines.0.line_total.cents', 4500)
            ->assertJsonPath('data.lines.0.stock', 2)
            ->assertJsonPath('data.lines.0.in_stock', false)
            ->assertJsonPath('data.lines.1.in_stock', true)
            ->assertJsonPath('data.unavailable', [apiVariant($gone)->id])
            ->assertJsonPath('data.subtotal.cents', 6900)
            ->assertJsonPath('data.can_checkout', false);

        $this->postJson(route('api.v1.shop.cart'), ['items' => [['variant_id' => apiVariant($cap)->id, 'quantity' => 2]]])
            ->assertJsonPath('data.can_checkout', true);
    });

    it('validates the items', function () {
        $this->postJson(route('api.v1.shop.cart'), ['items' => []])->assertJsonValidationErrors('items');
        $this->postJson(route('api.v1.shop.cart'), ['items' => [['variant_id' => 1, 'quantity' => 99]]])->assertJsonValidationErrors('items.0.quantity');
    });

    it('gives the checkout options', function () {
        $this->getJson(route('api.v1.shop.checkout-form'))
            ->assertOk()
            ->assertJsonPath('data.fulfilment.*.key', ['pickup', 'delivery'])
            ->assertJsonPath('data.fulfilment.1.fee.cents', 400)
            ->assertJsonPath('data.fulfilment.1.needs_address', true)
            ->assertJsonPath('data.payment_methods.*.key', ['whish', 'omt', 'cash'])
            ->assertJsonPath('data.max_quantity', 10);
    });
});

describe('ordering', function () {
    it('places an order, takes the stock and emails the customer and the shop', function () {
        $shirt = Product::factory()->withStock(['M' => 5])->create(['name' => 'Club T-shirt', 'price_cents' => 2000]);

        $response = $this->postJson(route('api.v1.shop.orders.store'), apiOrder(
            [['variant_id' => apiVariant($shirt)->id, 'quantity' => 2]],
            ['fulfilment' => 'delivery', 'delivery_city' => 'Beirut', 'delivery_address' => 'Hamra Street 12', 'payment_method' => 'whish'],
        ))
            ->assertCreated()
            ->assertJsonPath('data.status', ['key' => 'new', 'label' => 'Received'])
            ->assertJsonPath('data.items.0.product_name', 'Club T-shirt')
            ->assertJsonPath('data.items.0.variant_name', 'M')
            ->assertJsonPath('data.items.0.product_slug', $shirt->slug)
            ->assertJsonPath('data.subtotal.cents', 4000)
            ->assertJsonPath('data.delivery_fee.cents', 400)
            ->assertJsonPath('data.total.cents', 4400)
            ->assertJsonPath('data.fulfilment.address', 'Hamra Street 12')
            ->assertJsonPath('data.payment.state', 'due')
            ->assertJsonPath('data.payment.instructions.type', 'transfer');

        $order = Order::sole();
        expect(apiVariant($shirt)->stock)->toBe(3)
            ->and($order->hiker->phone)->toBe('+96170123456');
        $response->assertJsonPath('data.token', $order->token)->assertJsonPath('data.web_url', route('orders.show', $order));
        Mail::assertQueued(OrderPlacedMail::class);
        Mail::assertQueued(NewOrderMail::class);

        $this->getJson(route('api.v1.orders.show', $order->token))->assertOk()->assertJsonPath('data.reference', $order->reference);
    });

    it('never oversells', function () {
        $shirt = Product::factory()->withStock(['M' => 1])->create();

        $this->postJson(route('api.v1.shop.orders.store'), apiOrder([['variant_id' => apiVariant($shirt)->id, 'quantity' => 2]]))
            ->assertUnprocessable()
            ->assertJsonStructure(['message']);

        expect(Order::count())->toBe(0)->and(apiVariant($shirt)->stock)->toBe(1);
    });

    it('uses the website checkout validation', function () {
        $cap = Product::factory()->withStock()->create();

        $this->postJson(route('api.v1.shop.orders.store'), apiOrder([['variant_id' => apiVariant($cap)->id, 'quantity' => 1]], ['fulfilment' => 'delivery', 'terms' => false]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.delivery_city.0', 'Enter the city for the delivery.')
            ->assertJsonPath('errors.delivery_address.0', 'Enter the delivery address.')
            ->assertJsonPath('errors.terms.0', 'Please accept the terms to place your order.');

        $this->postJson(route('api.v1.shop.orders.store'), apiOrder([]))->assertJsonValidationErrors('items');
    });

    it('lists my orders', function () {
        $user = User::factory()->create(['phone' => '+96170123456']);
        $user->assignRole(Role::Member->value);
        Hiker::factory()->create(['phone' => '+96170123456', 'user_id' => $user->id]);
        $cap = Product::factory()->withStock()->create();
        $this->postJson(route('api.v1.shop.orders.store'), apiOrder([['variant_id' => apiVariant($cap)->id, 'quantity' => 1]]))->assertCreated();
        $this->postJson(route('api.v1.shop.orders.store'), apiOrder([['variant_id' => apiVariant($cap)->id, 'quantity' => 1]], ['phone' => '+96171999888']))->assertCreated();

        $this->withToken($user->createToken('Phone')->plainTextToken)->getJson(route('api.v1.me.orders'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.phone', '+96170123456');
    });
});

describe('order payment', function () {
    it('pays online with the test gateway', function () {
        config(['payments.online_gateway' => 'test']);
        $cap = Product::factory()->withStock()->create();
        $token = $this->postJson(route('api.v1.shop.orders.store'), apiOrder([['variant_id' => apiVariant($cap)->id, 'quantity' => 1]], ['payment_method' => 'online']))
            ->assertJsonPath('data.payment.instructions', ['type' => 'online'])
            ->json('data.token');

        $this->postJson(route('api.v1.orders.pay', $token))->assertOk()->assertJsonStructure(['checkout_url']);
        $payment = Payment::sole();
        $back = $this->post(route('payments.test.simulate', $payment->external_id), ['outcome' => 'pay'])->headers->get('Location');
        $this->get($back);

        $this->getJson(route('api.v1.orders.show', $token))->assertJsonPath('data.payment.state', 'paid');
    });

    it('records a transfer number and shows a cancelled order', function () {
        $cap = Product::factory()->withStock()->create();
        $token = $this->postJson(route('api.v1.shop.orders.store'), apiOrder([['variant_id' => apiVariant($cap)->id, 'quantity' => 1]], ['payment_method' => 'omt']))->json('data.token');

        $this->postJson(route('api.v1.orders.transfer', $token), ['payment_reference' => '998877'])
            ->assertOk()
            ->assertJsonPath('data.payment.instructions.reported_transaction_number', '998877');

        Order::sole()->update(['status' => OrderStatus::Cancelled, 'cancelled_at' => now(), 'cancellation_reason' => 'Out of stock']);
        $this->getJson(route('api.v1.orders.show', $token))
            ->assertJsonPath('data.status.key', 'cancelled')
            ->assertJsonPath('data.cancellation.reason', 'Out of stock')
            ->assertJsonPath('data.payment.state', 'none');

        $this->getJson('/api/v1/orders/not-a-token')->assertNotFound();
    });
});
