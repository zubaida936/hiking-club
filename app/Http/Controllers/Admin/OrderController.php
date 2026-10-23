<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Shop\Actions\ManageOrder;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Exceptions\ShopException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Order::class);

        $status = OrderStatus::tryFrom((string) $request->query('status'));
        $search = $request->string('q')->trim()->toString();
        $digits = (string) preg_replace('/\D/', '', $search);

        return view('admin.orders.index', [
            'orders' => Order::query()
                ->with('items')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('reference', strtoupper($search))
                    ->when(strlen($digits) >= 3, fn ($q) => $q->orWhere('phone', 'like', "%{$digits}%"))))
                ->latest('id')
                ->paginate(config('cms.per_page'))
                ->withQueryString(),
            'statuses' => OrderStatus::cases(),
            'openCount' => Order::whereIn('status', [OrderStatus::New, OrderStatus::Preparing, OrderStatus::Ready])->count(),
        ]);
    }

    public function show(Order $order): View
    {
        Gate::authorize('viewAny', Order::class);

        return view('admin.orders.show', [
            'order' => $order->load('items'),
            'whatsapp' => WhatsApp::to($order->phone, "Hi {$order->name}! About your order {$order->reference}: "),
        ]);
    }

    public function status(Request $request, Order $order, ManageOrder $manage): RedirectResponse
    {
        Gate::authorize('manage', Order::class);

        $status = OrderStatus::from($request->validate(['status' => ['required', Rule::enum(OrderStatus::class)]])['status']);

        return $this->attempt(fn () => $manage->setStatus($order, $status, $request->user()), 'Order: '.$status->label($order->fulfilment).'.');
    }

    public function cancel(Request $request, Order $order, ManageOrder $manage): RedirectResponse
    {
        Gate::authorize('manage', Order::class);

        $validated = $request->validate(['cancellation_reason' => ['nullable', 'string', 'max:500'], 'notify' => ['boolean']]);

        return $this->attempt(
            fn () => $manage->cancel($order, $validated['cancellation_reason'] ?? null, $request->user(), $request->boolean('notify')),
            'Order cancelled and the items put back in stock.',
        );
    }

    public function payment(Request $request, Order $order, ManageOrder $manage): RedirectResponse
    {
        Gate::authorize('manage', Order::class);

        $paid = (bool) $request->validate(['paid' => ['required', 'boolean']])['paid'];

        return $this->attempt(fn () => $manage->setPaid($order, $paid, $request->user()), $paid ? 'Marked as paid.' : 'Marked as unpaid.');
    }

    private function attempt(callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (ShopException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $message);
    }
}
