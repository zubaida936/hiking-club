<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Newsletter\Actions\Unsubscribe;
use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Support\Csv;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Next Destination Alerts" subscribers: list, CSV export (e.g. phones for a
 * WhatsApp broadcast), unsubscribe on request, delete on an erasure request.
 */
class SubscriberController extends Controller
{
    public const STATUSES = [
        '' => 'Subscribed',
        'confirmed' => 'Email confirmed',
        'pending' => 'Awaiting confirmation',
        'phone' => 'With a phone number',
        'unsubscribed' => 'Unsubscribed',
    ];

    public function index(Request $request): View
    {
        Gate::authorize('manage', Subscriber::class);

        return view('admin.subscribers.index', [
            'subscribers' => $this->filtered($request)->paginate(config('cms.per_page'))->withQueryString(),
            'counts' => [
                'emails' => Subscriber::receivingEmails()->count(),
                'phones' => Subscriber::whereNotNull('phone')->whereNull('unsubscribed_at')->count(),
                'pending' => Subscriber::whereNotNull('email')->whereNull('confirmed_at')->whereNull('unsubscribed_at')->count(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('manage', Subscriber::class);

        $timezone = config('site.timezone');
        $rows = $this->filtered($request)->lazy()->map(fn (Subscriber $s) => [
            (string) $s->email,
            (string) $s->phone,
            $s->statusLabel(),
            $s->created_at->setTimezone($timezone)->format('Y-m-d H:i'),
            (string) $s->unsubscribed_at?->setTimezone($timezone)->format('Y-m-d H:i'),
        ]);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            if ($handle !== false) {
                Csv::write($handle, [['Email', 'Phone', 'Status', 'Signed up', 'Unsubscribed'], ...$rows]);
                fclose($handle);
            }
        }, 'subscribers-'.now()->setTimezone($timezone)->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function unsubscribe(Subscriber $subscriber, Unsubscribe $action): RedirectResponse
    {
        Gate::authorize('manage', Subscriber::class);

        $action->handle($subscriber);

        return back()->with('success', 'Unsubscribed. They get no more announcements.');
    }

    /**
     * For "delete my data" requests; otherwise prefer unsubscribing, which keeps the record of the opt-out.
     */
    public function destroy(Subscriber $subscriber): RedirectResponse
    {
        Gate::authorize('manage', Subscriber::class);

        $subscriber->delete();

        return back()->with('success', 'Subscriber deleted.');
    }

    /**
     * @return Builder<Subscriber>
     */
    private function filtered(Request $request): Builder
    {
        $status = $request->string('status')->toString();
        $search = $request->string('q')->trim()->toString();
        $digits = Phone::searchDigits($search);

        return Subscriber::query()
            ->when($status === '', fn ($q) => $q->whereNull('unsubscribed_at'))
            ->when($status === 'confirmed', fn ($q) => $q->receivingEmails())
            ->when($status === 'pending', fn ($q) => $q->whereNotNull('email')->whereNull('confirmed_at')->whereNull('unsubscribed_at'))
            ->when($status === 'phone', fn ($q) => $q->whereNotNull('phone')->whereNull('unsubscribed_at'))
            ->when($status === 'unsubscribed', fn ($q) => $q->whereNotNull('unsubscribed_at'))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('email', 'like', "%{$search}%")
                ->when($digits !== null, fn ($q) => $q->orWhere('phone', 'like', "%{$digits}%"))))
            ->latest('id');
    }
}
