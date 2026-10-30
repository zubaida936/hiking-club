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
use App\Models\XApplication;
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
     * The page texts (same as the website's X Events page), upcoming X hikes soonest first. With a token,
     * `access` tells whether the member can book them (approved, or Elite rank) and the status of their latest
     * application. Without a token `access` is null.
     *
     * @unauthenticated
     */
    public function index(Request $request, XAccess $xAccess): JsonResponse
    {
        /** @var ?User $user */
        $user = $request->user();
        $hiker = $user?->hiker;
        $application = $hiker?->latestXApplication();

        $events = Event::published()->upcoming()->where('is_xtreme', true)->withSeatCounts()->orderBy('starts_on')->orderBy('id')->get();

        // Loops and casts (not maps over plain arrays) so the API docs type every field.
        $page = (array) config('xevents.page');
        $requirements = [];
        foreach ((array) $page['requirements'] as $item) {
            $requirements[] = ['title' => (string) $item['title'], 'text' => (string) $item['text']];
        }
        $steps = [];
        foreach ((array) $page['steps'] as $item) {
            $steps[] = ['title' => (string) $item['title'], 'text' => (string) $item['text']];
        }

        return response()->json(['data' => [
            'page' => [
                'heading' => (string) $page['heading'],
                'intro' => (string) $page['intro'],
                'requirements' => $requirements,
                'steps' => $steps,
                'no_events' => (string) $page['no_events'],
            ],
            'events' => EventResource::collection($events),
            'access' => $user === null ? null : [
                'can_book' => $xAccess->allows($hiker),
                // pending, approved, rejected or revoked; null when the member never applied.
                'application' => $application === null ? null : $this->applicationState($application),
                // Applying is possible again after a rejection or a revoked access.
                'can_apply' => $hiker?->x_approved_at === null && $application?->status !== XApplicationStatus::Pending,
            ],
        ]]);
    }

    /**
     * Application form.
     *
     * The choices for the form (keys to send, labels to show), the age under which a parent or guardian
     * is needed, the consent text, and the member's details to fill in.
     *
     * @unauthenticated
     */
    public function form(Request $request): JsonResponse
    {
        /** @var ?User $user */
        $user = $request->user();
        $hiker = $user?->hiker;

        return response()->json(['data' => [
            'frequency' => $this->options('frequency'),
            'pace' => $this->options('pace'),
            'experience' => $this->options('experience'),
            'gear' => $this->options('gear'),
            'guardian_under_age' => (int) config('xevents.guardian_under_age'),
            // The text of the "I agree" box (`consent`), and the privacy policy it refers to.
            'consent' => (string) config('xevents.consent'),
            'privacy_url' => route('legal.privacy'),
            'member' => $user === null ? null : [
                'name' => (string) ($hiker->name ?? $user->name),
                'phone' => (string) ($hiker->phone ?? $user->phone),
                'email' => $hiker->email ?? $user->email,
            ],
        ]]);
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
            ...$this->applicationState($application),
            'message' => 'Thanks! Our guides will review your application and contact you on WhatsApp.',
        ]], 201);
    }

    /**
     * An application's status and when it was sent. A typed parameter, so the API docs know it is never null.
     *
     * @return array{status: array{key: string, label: string}, submitted_at: string}
     */
    private function applicationState(XApplication $application): array
    {
        return [
            'status' => ['key' => $application->status->value, 'label' => $application->status->label()],
            'submitted_at' => $application->created_at->toIso8601String(),
        ];
    }

    /**
     * One group of form choices from config/xevents.php, in config order.
     *
     * @return list<array{key: string, label: string}>
     */
    private function options(string $group): array
    {
        $options = [];
        foreach ((array) config("xevents.{$group}") as $key => $label) {
            $options[] = ['key' => (string) $key, 'label' => (string) $label];
        }

        return $options;
    }
}
