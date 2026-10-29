<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\XEvents\Actions\SubmitApplication;
use App\Domain\XEvents\Enums\XApplicationStatus;
use App\Domain\XEvents\XAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\XApplicationRequest;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * X Events: the upcoming X hikes, the member's access, and the "apply once" application
 * (same form, rules and messages as the website).
 */
class XEventsController extends Controller
{
    /**
     * X Events.
     *
     * Upcoming X hikes, soonest first. With a token, `access` tells whether the member can book them
     * (approved, or Elite rank) and the status of their latest application. Without a token `access` is null.
     *
     * @unauthenticated
     *
     * @return array{data: array<string, mixed>}
     */
    public function index(Request $request, XAccess $xAccess): array
    {
        /** @var ?User $user */
        $user = $request->user();
        $hiker = $user?->hiker;
        $application = $hiker?->latestXApplication();

        $events = Event::published()->upcoming()->where('is_xtreme', true)->withSeatCounts()->orderBy('starts_on')->orderBy('id')->get();

        return ['data' => [
            'events' => EventResource::collection($events)->resolve($request),
            'access' => $user === null ? null : [
                'can_book' => $xAccess->allows($hiker),
                // pending, approved, rejected or revoked; null when the member never applied.
                'application' => $application === null ? null : [
                    'status' => ['key' => $application->status->value, 'label' => $application->status->label()],
                    'submitted_at' => $application->created_at->toIso8601String(),
                ],
                // Applying is possible again after a rejection or a revoked access.
                'can_apply' => $hiker?->x_approved_at === null && $application?->status !== XApplicationStatus::Pending,
            ],
        ]];
    }

    /**
     * Application form.
     *
     * The choices for the form (keys to send, labels to show), the age under which a parent or guardian
     * is needed, and the member's details to fill in.
     *
     * @unauthenticated
     *
     * @return array{data: array<string, mixed>}
     */
    public function form(Request $request): array
    {
        /** @var ?User $user */
        $user = $request->user();
        $hiker = $user?->hiker;
        $options = fn (string $group) => collect((array) config("xevents.{$group}"))
            ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])
            ->values()->all();

        return ['data' => [
            'frequency' => $options('frequency'),
            'pace' => $options('pace'),
            'experience' => $options('experience'),
            'gear' => $options('gear'),
            'guardian_under_age' => (int) config('xevents.guardian_under_age'),
            'member' => $user === null ? null : [
                'name' => $hiker->name ?? $user->name,
                'phone' => $hiker->phone ?? $user->phone,
                'email' => $hiker->email ?? $user->email,
            ],
        ]];
    }

    /**
     * Apply for X Events.
     *
     * Same fields, rules and messages as the website form (`consent` must be true; `guardian_name` and
     * `guardian_phone` under `guardian_under_age`). One open application per WhatsApp number: a second one,
     * or applying when already approved, answers 422 with a message. Organizers review it in the CMS and
     * answer on WhatsApp.
     *
     * @unauthenticated
     */
    public function apply(XApplicationRequest $request, SubmitApplication $action): JsonResponse
    {
        $application = $action->handle($request->toData());

        return response()->json(['data' => [
            'status' => ['key' => $application->status->value, 'label' => $application->status->label()],
            'submitted_at' => $application->created_at->toIso8601String(),
            'message' => 'Thanks! Our guides will review your application and contact you on WhatsApp.',
        ]], 201);
    }
}
