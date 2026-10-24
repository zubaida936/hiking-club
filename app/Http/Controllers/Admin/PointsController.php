<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Loyalty\Actions\AdjustPoints;
use App\Domain\Loyalty\Enums\RankTier;
use App\Domain\Loyalty\Leaderboard;
use App\Domain\Rewards\Actions\RecordPerkUse;
use App\Domain\Rewards\Exceptions\RewardException;
use App\Domain\Rewards\HikerRewards;
use App\Http\Controllers\Controller;
use App\Models\Hiker;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PointsController extends Controller
{
    /**
     * Hikers in rank order: points, then distance hiked.
     */
    public function index(Request $request, Leaderboard $leaderboard): View
    {
        Gate::authorize('viewAny', Hiker::class);

        $search = $request->string('q')->trim()->toString();
        $digits = Phone::searchDigits($search);

        $hikers = $leaderboard->query()
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->when($digits !== null, fn ($q) => $q->orWhere('phone', 'like', "%{$digits}%"))))
            ->paginate(config('cms.per_page'))
            ->withQueryString();

        return view('admin.points.index', [
            'hikers' => $hikers,
            'positions' => $leaderboard->positions(),
            'tiers' => RankTier::cases(),
        ]);
    }

    public function handOver(Request $request, Hiker $hiker, RecordPerkUse $action): RedirectResponse
    {
        Gate::authorize('adjustPoints', $hiker);

        $validated = $request->validate([
            'reward' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $action->handle($hiker, $validated['reward'], $validated['note'] ?? null, $request->user());
        } catch (RewardException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Reward marked as handed over.');
    }

    public function show(Hiker $hiker, Leaderboard $leaderboard, HikerRewards $rewards): View
    {
        Gate::authorize('viewAny', Hiker::class);

        $hiker->loadSum('pointTransactions as total_points', 'points')->load('user');

        return view('admin.points.show', [
            'hiker' => $hiker,
            'position' => $leaderboard->positionOf($hiker),
            'transactions' => $hiker->pointTransactions()->with(['registration.event', 'creator'])->latest('id')->get(),
            'bookings' => $hiker->registrations()->with('event')->latest('id')->get(),
            'rewards' => $rewards->statesFor($hiker),
        ]);
    }

    public function adjust(Request $request, Hiker $hiker, AdjustPoints $action): RedirectResponse
    {
        Gate::authorize('adjustPoints', $hiker);

        $validated = $request->validate([
            'points' => ['required', 'integer', 'not_in:0', 'between:-1000,1000'],
            'reason' => ['required', 'string', 'max:200'],
        ], ['points.not_in' => 'Enter a number of points other than 0.']);

        $action->handle($hiker, (int) $validated['points'], $validated['reason'], $request->user());

        return back()->with('success', 'Points adjusted.');
    }
}
