<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\Shop\Enums\Fulfilment;
use App\Domain\Shop\Enums\OrderStatus;
use App\Mail\NewOrderMail;
use App\Mail\OrderPlacedMail;
use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Mail::fake();
    Storage::fake('public');
    $this->admin = userWithRole(Role::Admin);
    $this->category = ProductCategory::where('slug', 'jacket')->firstOrFail();
});

function productForm(array $overrides = []): array
{
    return [
        'product_category_id' => ProductCategory::where('slug', 'jacket')->value('id'),
        'name' => 'Storm Shell Jacket',
        'description' => 'Waterproof shell.',
        'price' => '89.50',
        'sale_price' => '',
        'tags' => 'Waterproof, Breathable, Waterproof',
        'is_active' => '1',
        'variants' => [['id' => '', 'name' => 'S', 'stock' => 2], ['id' => '', 'name' => 'M', 'stock' => 6], ['id' => '', 'name' => '', 'stock' => 0]],
        ...$overrides,
    ];
}

/**
 * An order placed straight in the database: 2 × "Trail Cap" taken from a stock of 5.
 */
function placedOrder(array $attributes = []): Order
{
    $product = Product::factory()->withStock(3)->create(['name' => 'Trail Cap', 'price_cents' => 1500]);
    $order = Order::create([
        'reference' => strtoupper(Str::random(8)), 'token' => Str::random(64), 'status' => OrderStatus::New,
        'name' => 'Rima Haddad', 'phone' => '+96170123456', 'email' => 'rima@example.com', 'fulfilment' => Fulfilment::Pickup,
        'subtotal_cents' => 3000, 'delivery_fee_cents' => 0, 'total_cents' => 3000, 'payment_method' => 'cash',
        ...$attributes,
    ]);
    $order->items()->create([
        'product_id' => $product->id, 'product_variant_id' => $product->variants()->value('id'),
        'product_name' => 'Trail Cap', 'variant_name' => 'One size', 'unit_price_cents' => 1500, 'quantity' => 2,
    ]);

    return $order;
}

describe('products', function () {
    it('creates a product with sizes, tags and photos', function () {
        $this->actingAs($this->admin)->get(route('admin.products.create'))->assertOk()->assertSee('Sizes and stock');

        $this->actingAs($this->admin)->post(route('admin.products.store'), productForm([
            'images' => [UploadedFile::fake()->image('front.jpg', 2000, 2000), UploadedFile::fake()->image('back.png', 800, 800)],
        ]))->assertRedirect();

        $product = Product::sole();
        expect($product)
            ->slug->toBe('storm-shell-jacket')->price_cents->toBe(8950)->sale_price_cents->toBeNull()
            ->tags->toBe(['Waterproof', 'Breathable'])->is_active->toBeTrue()
            ->and($product->variants->pluck('stock', 'name')->all())->toBe(['S' => 2, 'M' => 6])
            ->and($product->images)->toHaveCount(2);
        Storage::disk('public')->assertExists($product->images->first()->path);

        $this->get(route('shop.show', $product))->assertOk()->assertSee('Storm Shell Jacket')->assertSee('$89.5');
    });

    it('edits sizes in place, removes photos and keeps the slug unique', function () {
        $this->actingAs($this->admin)->post(route('admin.products.store'), productForm(['images' => [UploadedFile::fake()->image('a.jpg')]]));
        $product = Product::sole();
        [$small, $medium] = $product->variants->all();
        $image = $product->images->sole();

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), productForm([
            'sale_price' => '70',
            'variants' => [['id' => $medium->id, 'name' => 'M', 'stock' => 9], ['id' => '', 'name' => 'L', 'stock' => 1]],
            'remove_images' => [$image->id],
        ]))->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();
        expect($product->sale_price_cents)->toBe(7000)
            ->and($product->variants()->pluck('stock', 'name')->all())->toBe(['M' => 9, 'L' => 1])
            ->and($medium->fresh()->stock)->toBe(9)
            ->and($small->fresh())->toBeNull()
            ->and(ProductImage::count())->toBe(0);
        Storage::disk('public')->assertMissing($image->path);

        $this->actingAs($this->admin)->post(route('admin.products.store'), productForm());
        expect(Product::latest('id')->first()->slug)->toBe('storm-shell-jacket-2');
    });

    it('validates prices and sizes', function () {
        $this->actingAs($this->admin)->post(route('admin.products.store'), productForm([
            'price' => '20', 'sale_price' => '25',
            'variants' => [['name' => 'M', 'stock' => 1], ['name' => 'm', 'stock' => -1]],
        ]))->assertSessionHasErrors(['sale_price' => 'The sale price must be lower than the price.', 'variants.1.name', 'variants.1.stock']);

        $this->actingAs($this->admin)->post(route('admin.products.store'), productForm(['variants' => [['name' => '', 'stock' => 0]]]))
            ->assertSessionHasErrors(['variants' => 'Add at least one size (or "One size").']);
        expect(Product::count())->toBe(0);
    });

    it('lists products with stock and hides deleted ones from the shop', function () {
        $product = Product::factory()->withStock(['S' => 1, 'M' => 0])->create(['name' => 'Fleece']);

        $this->actingAs($this->admin)->get(route('admin.products.index'))->assertOk()->assertSeeInOrder(['Fleece', 'Accessories', '1', 'In the shop']);

        $this->actingAs($this->admin)->delete(route('admin.products.destroy', $product))->assertRedirect(route('admin.products.index'));
        expect($product->fresh()->trashed())->toBeTrue();
        $this->get(route('shop.show', $product->slug))->assertNotFound();
    });

    it('manages categories but keeps ones with products', function () {
        $this->actingAs($this->admin)->post(route('admin.product-categories.store'), ['name' => 'Camping gear'])->assertSessionHas('success');
        $camping = ProductCategory::where('slug', 'camping-gear')->firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.product-categories.update', $camping), ['name' => 'Camping', 'position' => 0]);
        expect($camping->fresh())->name->toBe('Camping')->position->toBe(0);

        Product::factory()->create(['product_category_id' => $this->category->id]);
        $this->actingAs($this->admin)->delete(route('admin.product-categories.destroy', $this->category))
            ->assertSessionHas('error', 'Move or delete its products first.');
        $this->actingAs($this->admin)->delete(route('admin.product-categories.destroy', $camping))->assertSessionHas('success');
        expect($camping->fresh())->toBeNull()->and($this->category->fresh())->not->toBeNull();
    });
});

describe('orders', function () {
    it('lists and searches orders', function () {
        $order = placedOrder();
        placedOrder(['name' => 'Karim Saad', 'phone' => '+96103999888', 'status' => OrderStatus::Completed]);

        $this->actingAs($this->admin)->get(route('admin.orders.index'))->assertOk()->assertSee('All (1 open)')->assertSee('Rima Haddad')->assertSee('Karim Saad');
        $this->actingAs($this->admin)->get(route('admin.orders.index', ['q' => strtolower($order->reference)]))->assertSee('Rima Haddad')->assertDontSee('Karim Saad');
        $this->actingAs($this->admin)->get(route('admin.orders.index', ['q' => '999 888']))->assertSee('Karim Saad')->assertDontSee('Rima Haddad');
        $this->actingAs($this->admin)->get(route('admin.orders.index', ['status' => 'completed']))->assertSee('Karim Saad')->assertDontSee('Rima Haddad');
    });

    it('moves the order through its steps and emails when ready', function () {
        $order = placedOrder();

        $this->actingAs($this->admin)->get(route('admin.orders.show', $order))->assertOk()
            ->assertSee('2 × $15')->assertSee('wa.me/96170123456', false)->assertSee('Being prepared');

        $this->actingAs($this->admin)->post(route('admin.orders.status', $order), ['status' => 'preparing'])->assertSessionHas('success');
        Mail::assertNothingQueued();

        $this->actingAs($this->admin)->post(route('admin.orders.status', $order), ['status' => 'ready'])
            ->assertSessionHas('success', 'Order: Ready for pickup.');
        Mail::assertQueued(OrderStatusMail::class, fn ($mail) => $mail->hasTo('rima@example.com'));

        $this->actingAs($this->admin)->post(route('admin.orders.status', $order), ['status' => 'completed']);
        expect($order->fresh())->status->toBe(OrderStatus::Completed)->updated_by->toBe($this->admin->id);
        $this->actingAs($this->admin)->post(route('admin.orders.status', $order), ['status' => 'preparing'])
            ->assertSessionHas('error', 'This order can no longer be changed that way.');
        $this->get(route('orders.show', $order))->assertSee('Completed');
    });

    it('cancels an order and puts the stock back', function () {
        $order = placedOrder(['paid_at' => now()]);
        $variant = $order->items->sole()->variant;

        $this->actingAs($this->admin)->post(route('admin.orders.cancel', $order), ['cancellation_reason' => 'Out of this colour', 'notify' => '1'])
            ->assertSessionHas('success');

        expect($order->fresh())->status->toBe(OrderStatus::Cancelled)->cancellation_reason->toBe('Out of this colour')
            ->and($variant->fresh()->stock)->toBe(5);
        Mail::assertQueued(OrderStatusMail::class);
        $this->actingAs($this->admin)->get(route('admin.orders.show', $order))->assertSee('Refund due');
        $this->get(route('orders.show', $order))->assertSee('This order was cancelled: Out of this colour')->assertSee("We'll refund your payment.");

        // Cancelling twice must not restock twice.
        $this->actingAs($this->admin)->post(route('admin.orders.cancel', $order), ['notify' => '0'])->assertSessionHas('error');
        expect($variant->fresh()->stock)->toBe(5);
    });

    it('cancels quietly when asked', function () {
        $order = placedOrder();
        $this->actingAs($this->admin)->post(route('admin.orders.cancel', $order), ['notify' => '0']);
        Mail::assertNothingQueued();
    });

    it('marks orders paid and unpaid', function () {
        $order = placedOrder();

        $this->actingAs($this->admin)->post(route('admin.orders.payment', $order), ['paid' => '1'])->assertSessionHas('success', 'Marked as paid.');
        expect($order->fresh()->paid_at)->not->toBeNull();
        $this->actingAs($this->admin)->post(route('admin.orders.payment', $order), ['paid' => '0']);
        expect($order->fresh()->paid_at)->toBeNull();
    });
});

it('is limited to staff who manage the shop', function (Role $role) {
    $order = placedOrder();
    $product = Product::factory()->create();
    $user = userWithRole($role);

    $this->actingAs($user)->get(route('admin.products.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.products.edit', $product))->assertForbidden();
    $this->actingAs($user)->get(route('admin.product-categories.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.orders.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.orders.cancel', $order))->assertForbidden();
    $this->actingAs($user)->get(route('admin.dashboard'))->assertDontSee('Shop orders');
})->with([Role::Editor, Role::Guide]);

it('shows the shop in the admin menu', function () {
    $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertSee('Shop orders')->assertSee('Shop products');
});

it('renders the shop emails', function () {
    $order = placedOrder();

    expect((new OrderPlacedMail($order))->render())->toContain($order->reference)->toContain('Trail Cap')
        ->and((new NewOrderMail($order))->render())->toContain(route('admin.orders.show', $order))
        ->and((new OrderStatusMail($order))->render())->toContain(route('orders.show', $order));
});
