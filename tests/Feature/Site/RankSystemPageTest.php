<?php

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Identity\Enums\Role;
use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Loyalty\Enums\RankTier;
use App\Domain\Rewards\RewardCatalog;
use App\Models\Hiker;
use App\Models\User;

function rankPageMember(int $points): User
{
    $user = User::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456']);
    $user->assignRole(Role::Member->value);
    $hiker = Hiker::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456', 'user_id' => $user->id]);
    $hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => $points, 'description' => 'Test']);

    return $user;
}

it('explains every rank with its points and rewards, from the config', function () {
    $response = $this->get(route('rank-system'))->assertOk()->assertDontSee('This page is on its way');

    $response->assertSeeInOrder(['Pathfinder', 'From your first hike', 'Trekker', '80+ points', 'Mountaineer', '200+ points', 'Highlander', '400+ points', 'Elite', '700+ points']);

    foreach (app(RewardCatalog::class)->all() as $reward) {
        $response->assertSee($reward->label)->assertSee($reward->howItWorks());
    }

    $response->assertSee('After 5 completed hikes. Choose it on the booking form when you book.')
        ->assertSee('Choose it on the booking form when you book (2 uses).')
        ->assertSee('Added to your points automatically when you reach the rank.')
        ->assertSee('Log in');
});

it('shows how points are earned with the configured values', function () {
    config()->set('points.hike', [1 => 8, 2 => 14, 3 => 22, 4 => 35]);
    config()->set('points.early_booking', ['points' => 4, 'days' => 5]);
    config()->set('points.referral', 10);

    $this->get(route('rank-system'))
        ->assertSeeInOrder([DifficultyLevel::Foundation->label(), '+8', DifficultyLevel::Endurance->label(), '+14', DifficultyLevel::Advanced->label(), '+22', DifficultyLevel::Xtreme->label(), '+35'])
        ->assertSeeInOrder(['Early booking bonus', 'at least 5 days before', '+4', 'Invite a friend', '+10'])
        ->assertSee('14 + 4 = <strong>18 points</strong>', false)
        ->assertSee('About 5 of those take you to Trekker.')
        ->assertSee('and the hike is paid')
        ->assertSee(route('leaderboard'))
        ->assertSee('Terms');

    // The page follows config changes.
    config()->set('points.hike.2', 20);
    config()->set('points.tiers.trekker', 100);
    $this->get(route('rank-system'))->assertSee('+20')->assertSee('100+ points')->assertSee('20 + 4 = <strong>24 points</strong>', false);
});

it('shows a logged-in member their rank and progress', function () {
    $this->actingAs(rankPageMember(150))->get(route('rank-system'))
        ->assertSee('Trekker · 150 points')
        ->assertSee('50 points to Mountaineer.')
        ->assertSee('aria-valuenow="58"', false)
        ->assertSee('You are here')
        ->assertDontSee('to see your rank and rewards');
});

it('tells a member at the top rank they made it', function () {
    $this->actingAs(rankPageMember(RankTier::Elite->minPoints() + 5))->get(route('rank-system'))
        ->assertSee('Elite · ')
        ->assertSee('You reached the top rank.');
});

it('is linked from the menu, the leaderboard, My account and the sitemap', function () {
    $this->get(route('home'))->assertSee(route('rank-system'));
    $this->get(route('leaderboard'))->assertSee(route('rank-system'));
    $this->actingAs(rankPageMember(10))->get(route('account'))->assertSee(route('rank-system'));
    $this->get(route('sitemap'))->assertSee('<loc>'.route('rank-system').'</loc>', false);
});
