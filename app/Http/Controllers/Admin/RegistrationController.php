<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Registrations\Actions\ApproveRegistration;
use App\Domain\Registrations\Actions\CancelRegistration;
use App\Domain\Registrations\Actions\MarkAttendance;
use App\Domain\Registrations\Actions\SetPaymentStatus;
use App\Domain\Registrations\Actions\UpdateRegistration;
use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Domain\Registrations\Exceptions\RegistrationException;
use App\Domain\Registrations\Exports\RegistrationExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RegistrationRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationController extends Controller
{
    /**
     * Events with their booking numbers: upcoming first by default, or past ones.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', EventRegistration::class);

        $past = $request->query('when') === 'past';

        $events = Event::query()
            ->where(fn (Builder $q) => $q->published()->orWhereHas('registrations'))
            ->when($past, fn (Builder $q) => $q->past()->orderByDesc('starts_on'), fn (Builder $q) => $q->upcoming()->orderBy('starts_on'))
            ->withSeatCounts()
            ->withCount([
                'registrations as pending_count' => fn ($q) => $q->where('status', RegistrationStatus::Pending),
                'registrations as waitlisted_count' => fn ($q) => $q->where('status', RegistrationStatus::Waitlisted),
                'registrations as paid_count' => fn ($q) => $q->active()->whereNotNull('paid_at'),
                'registrations as attended_count' => fn ($q) => $q->where('attendance', Attendance::Attended),
            ])
            ->paginate(config('cms.per_page'))
            ->withQueryString();

        return view('admin.registrations.index', ['events' => $events, 'past' => $past]);
    }

    public function show(Request $request, Event $event): View
    {
        Gate::authorize('viewAny', EventRegistration::class);

        $event->loadSeatCount()->load('meetingPoints');

        return view('admin.registrations.show', [
            'event' => $event,
            'registrations' => $this->filtered($event, $request)->get(),
            'pickups' => $event->registrations()->active()
                ->selectRaw('pickup_point, pickup_time, count(*) as total')
                ->groupBy('pickup_point', 'pickup_time')
                ->orderBy('pickup_time')
                ->toBase()
                ->get(),
            'whatsapp' => (new RegistrationExport($event, $event->registrations()->active()->get()))->whatsappText(),
            'statuses' => RegistrationStatus::cases(),
        ]);
    }

    public function export(Request $request, Event $event): StreamedResponse
    {
        Gate::authorize('viewAny', EventRegistration::class);

        $export = new RegistrationExport($event, $this->filtered($event, $request)->get());

        return response()->streamDownload(function () use ($export) {
            $handle = fopen('php://output', 'w');
            if ($handle !== false) {
                $export->writeCsv($handle);
                fclose($handle);
            }
        }, $export->filename(), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function edit(EventRegistration $registration): View
    {
        Gate::authorize('update', $registration);

        return view('admin.registrations.edit', ['registration' => $registration->load('event.meetingPoints')]);
    }

    public function update(RegistrationRequest $request, EventRegistration $registration, UpdateRegistration $action): RedirectResponse
    {
        Gate::authorize('update', $registration);

        try {
            $action->handle($registration, $request->toData(), $request->user());
        } catch (RegistrationException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.events.registrations', $registration->event_id)->with('success', "Booking {$registration->reference} saved.");
    }

    public function approve(Request $request, EventRegistration $registration, ApproveRegistration $action): RedirectResponse
    {
        Gate::authorize('update', $registration);

        return $this->attempt(fn () => $action->handle($registration, $request->user()), "{$registration->name} is confirmed.");
    }

    public function cancel(Request $request, EventRegistration $registration, CancelRegistration $action): RedirectResponse
    {
        Gate::authorize('update', $registration);

        $validated = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
            'notify' => ['boolean'],
        ]);

        return $this->attempt(
            fn () => $action->handle($registration, $validated['cancellation_reason'] ?? null, $request->user(), $request->boolean('notify')),
            "Booking {$registration->reference} cancelled.",
        );
    }

    public function payment(Request $request, EventRegistration $registration, SetPaymentStatus $action): RedirectResponse
    {
        Gate::authorize('update', $registration);

        $paid = (bool) $request->validate(['paid' => ['required', 'boolean']])['paid'];

        return $this->attempt(
            fn () => $action->handle($registration, $paid, $request->user()),
            $paid ? "{$registration->name} marked as paid." : "{$registration->name} marked as unpaid.",
        );
    }

    public function attendance(Request $request, EventRegistration $registration, MarkAttendance $action): RedirectResponse
    {
        Gate::authorize('markAttendance', $registration);

        $value = $request->validate(['attendance' => ['nullable', Rule::enum(Attendance::class)]])['attendance'] ?? null;
        $attendance = $value !== null ? Attendance::from($value) : null;

        return $this->attempt(
            fn () => $action->handle($registration, $attendance, $request->user()),
            $attendance ? "{$registration->name}: {$attendance->label()}." : "{$registration->name}: attendance cleared.",
        );
    }

    /**
     * The event's registrations narrowed by the list filters (status, pickup point, search),
     * in booking order. Without a status filter, cancelled bookings are left out.
     *
     * @return Builder<EventRegistration>
     */
    private function filtered(Event $event, Request $request): Builder
    {
        $status = RegistrationStatus::tryFrom((string) $request->query('status'));
        $pickup = $request->string('pickup')->toString();
        $search = $request->string('q')->trim()->toString();
        $digits = (string) preg_replace('/\D/', '', $search);

        return EventRegistration::query()->whereBelongsTo($event)
            ->when($status, fn ($q) => $q->where('status', $status), fn ($q) => $q->where('status', '!=', RegistrationStatus::Cancelled))
            ->when($pickup !== '', fn ($q) => $pickup === '-' ? $q->whereNull('pickup_point') : $q->where('pickup_point', $pickup))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('reference', strtoupper($search))
                ->when(strlen($digits) >= 3, fn ($q) => $q->orWhere('phone', 'like', "%{$digits}%"))))
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * Run a domain action and turn rule violations into a flash error.
     */
    private function attempt(callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (RegistrationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $message);
    }
}
