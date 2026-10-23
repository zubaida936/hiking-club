<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Members\MemberLinks;
use App\Domain\XEvents\Actions\ReviewApplication;
use App\Domain\XEvents\Enums\XApplicationStatus;
use App\Domain\XEvents\Exceptions\XApplicationException;
use App\Http\Controllers\Controller;
use App\Models\XApplication;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Review queue for X Events applications: read, note, approve / reject, revoke,
 * and send the approved hiker their personal access link on WhatsApp.
 */
class XApplicationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', XApplication::class);

        $status = XApplicationStatus::tryFrom((string) $request->query('status'));

        return view('admin.x-applications.index', [
            'applications' => XApplication::query()
                ->with('hiker')
                ->when($status, fn ($q) => $q->where('status', $status))
                // Waiting ones first, oldest first (fair queue); then the rest, newest first.
                ->orderByRaw('case when status = ? then 0 else 1 end', [XApplicationStatus::Pending->value])
                ->orderByRaw('case when status = ? then created_at end asc', [XApplicationStatus::Pending->value])
                ->latest('id')
                ->paginate(config('cms.per_page'))
                ->withQueryString(),
            'statuses' => XApplicationStatus::cases(),
            'pendingCount' => XApplication::where('status', XApplicationStatus::Pending)->count(),
        ]);
    }

    public function show(XApplication $application): View
    {
        Gate::authorize('review', $application);

        $hiker = $application->hiker->loadSum('pointTransactions as total_points', 'points');

        return view('admin.x-applications.show', [
            'application' => $application->load('reviewer'),
            'hiker' => $hiker,
            'others' => $hiker->xApplications()->whereKeyNot($application->id)->latest('id')->get(),
        ]);
    }

    public function approve(Request $request, XApplication $application, ReviewApplication $review): RedirectResponse
    {
        return $this->decide($request, $application, fn (?string $note) => $review->approve($application, $request->user(), $note), 'Approved. Now send the access link.');
    }

    public function reject(Request $request, XApplication $application, ReviewApplication $review): RedirectResponse
    {
        return $this->decide($request, $application, fn (?string $note) => $review->reject($application, $request->user(), $note), 'Application not approved.');
    }

    public function revoke(Request $request, XApplication $application, ReviewApplication $review): RedirectResponse
    {
        return $this->decide($request, $application, fn (?string $note) => $review->revoke($application, $request->user(), $note), 'X Events access revoked.');
    }

    public function note(Request $request, XApplication $application, ReviewApplication $review): RedirectResponse
    {
        Gate::authorize('review', $application);

        $note = $request->validate(['internal_note' => ['nullable', 'string', 'max:5000']])['internal_note'] ?? null;
        $review->saveNote($application, $note);

        return back()->with('success', 'Note saved.');
    }

    /**
     * WhatsApp message with the approved hiker's personal access: an account invite
     * when they have no account yet, otherwise the X Events page to log in and book.
     */
    public function accessLink(XApplication $application, MemberLinks $links): RedirectResponse
    {
        Gate::authorize('review', $application);

        if ($application->status !== XApplicationStatus::Approved) {
            return back()->with('error', XApplicationException::notApproved()->getMessage());
        }

        $hiker = $application->hiker;
        $url = $hiker->user_id === null ? $links->activation($hiker) : route('x-events');
        $message = $hiker->user_id === null
            ? "Hi {$hiker->name}! You're approved for X Events 🎉 Activate your account with this personal link (valid ".MemberLinks::INVITE_DAYS." days), then book any X event: {$url}"
            : "Hi {$hiker->name}! You're approved for X Events 🎉 Log in with your phone number and book any X event here: {$url}";

        $application->update(['access_sent_at' => now()]);

        return back()->with('memberLink', ['hiker' => $hiker->name, 'url' => $url, 'whatsapp' => WhatsApp::to($application->phone, $message)]);
    }

    /**
     * @param  callable(?string): void  $action
     */
    private function decide(Request $request, XApplication $application, callable $action, string $message): RedirectResponse
    {
        Gate::authorize('review', $application);

        $note = $request->validate(['decision_note' => ['nullable', 'string', 'max:500']])['decision_note'] ?? null;

        try {
            $action($note);
        } catch (XApplicationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $message);
    }
}
