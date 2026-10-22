<?php

use App\Domain\Events\Actions\PublishEvent;
use App\Domain\Events\Actions\UnpublishEvent;
use App\Domain\Identity\Enums\Role;
use App\Mail\ConfirmSubscriptionMail;
use App\Mail\NewEventAnnouncementMail;
use App\Models\Event;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

function subscribe(array $input)
{
    return test()->from(route('home'))->post(route('newsletter.subscribe'), $input);
}

describe('signing up', function () {
    it('stores an email subscriber and sends a confirmation mail', function () {
        subscribe(['email' => '  Hiker@Example.COM '])
            ->assertRedirect(route('home').'#newsletter')
            ->assertSessionHas('newsletter', fn ($m) => str_contains($m, 'Check your inbox'));

        $subscriber = Subscriber::sole();
        expect($subscriber->email)->toBe('hiker@example.com')
            ->and($subscriber->confirmed_at)->toBeNull()
            ->and($subscriber->token)->toHaveLength(64);

        Mail::assertQueued(ConfirmSubscriptionMail::class, fn ($mail) => $mail->hasTo('hiker@example.com'));
    });

    it('stores a phone-only subscriber without sending mail', function () {
        subscribe(['phone' => '+961 70-123 456'])
            ->assertSessionHas('newsletter', fn ($m) => str_contains($m, "You're on the list"));

        expect(Subscriber::sole())->email->toBeNull()->phone->toBe('+96170123456');
        Mail::assertNothingQueued();
    });

    it('stores email and phone together', function () {
        subscribe(['email' => 'both@example.com', 'phone' => '03 123 456']);

        expect(Subscriber::sole())->email->toBe('both@example.com')->phone->toBe('03123456');
    });

    it('requires an email or a phone', function () {
        subscribe(['email' => '', 'phone' => ''])
            ->assertRedirect(route('home').'#newsletter')
            ->assertSessionHasErrors(['email' => 'Enter your email or your phone number.']);

        expect(Subscriber::count())->toBe(0);
    });

    it('rejects invalid input', function (array $input, string $field) {
        subscribe($input)->assertSessionHasErrors($field);
        expect(Subscriber::count())->toBe(0);
    })->with([
        'bad email' => [['email' => 'not-an-email'], 'email'],
        'bad phone' => [['phone' => 'call me maybe'], 'phone'],
        'honeypot filled' => [['email' => 'bot@example.com', 'website' => 'http://spam.test'], 'website'],
    ]);

    it('does not duplicate an existing subscriber and gives the same answer', function () {
        $existing = Subscriber::factory()->confirmed()->create(['email' => 'hiker@example.com']);

        subscribe(['email' => 'HIKER@example.com', 'phone' => '+96170123456'])
            ->assertSessionHas('newsletter', fn ($m) => str_contains($m, 'Check your inbox'));

        expect(Subscriber::count())->toBe(1)
            ->and($existing->fresh()->phone)->toBe('+96170123456')
            ->and($existing->fresh()->confirmed_at)->not->toBeNull();
        Mail::assertNothingQueued(); // already confirmed
    });

    it('resends the confirmation to an unconfirmed email', function () {
        Subscriber::factory()->create(['email' => 'hiker@example.com']);

        subscribe(['email' => 'hiker@example.com']);

        expect(Subscriber::count())->toBe(1);
        Mail::assertQueued(ConfirmSubscriptionMail::class);
    });

    it('reactivates an unsubscribed email, which must confirm again', function () {
        $subscriber = Subscriber::factory()->confirmed()->unsubscribed()->create(['email' => 'back@example.com']);

        subscribe(['email' => 'back@example.com']);

        expect($subscriber->fresh())->unsubscribed_at->toBeNull()->confirmed_at->toBeNull();
        Mail::assertQueued(ConfirmSubscriptionMail::class);
    });

    it('does not move a phone that belongs to another subscriber', function () {
        $phoneOwner = Subscriber::factory()->phoneOnly()->create(['phone' => '+96170123456']);
        $emailOwner = Subscriber::factory()->create(['email' => 'hiker@example.com']);

        subscribe(['email' => 'hiker@example.com', 'phone' => '+96170123456']);

        expect($emailOwner->fresh()->phone)->toBeNull()
            ->and($phoneOwner->fresh()->phone)->toBe('+96170123456');
    });

    it('limits how often one visitor can sign up', function () {
        foreach (range(1, 5) as $i) {
            subscribe(['email' => "hiker{$i}@example.com"])->assertRedirect();
        }

        subscribe(['email' => 'hiker6@example.com'])->assertTooManyRequests();
    });

    it('shows the result message instead of the form after signing up', function () {
        $this->followingRedirects()
            ->from(route('home'))
            ->post(route('newsletter.subscribe'), ['email' => 'hiker@example.com'])
            ->assertSee('Almost there! Check your inbox')
            ->assertDontSee('name="email"', false);
    });
});

describe('confirming and unsubscribing', function () {
    it('confirms an email from the link', function () {
        $subscriber = Subscriber::factory()->create();

        $this->get(route('newsletter.confirm', $subscriber->token))
            ->assertOk()
            ->assertSee("You're subscribed");

        expect($subscriber->fresh()->confirmed_at)->not->toBeNull();
    });

    it('builds the confirmation mail with the confirm link', function () {
        $subscriber = Subscriber::factory()->create();

        (new ConfirmSubscriptionMail($subscriber))
            ->assertSeeInHtml(route('newsletter.confirm', $subscriber->token))
            ->assertSeeInHtml('Confirm my email');
    });

    it('rejects unknown tokens', function () {
        $this->get(route('newsletter.confirm', str_repeat('x', 64)))->assertNotFound();
        $this->get(route('newsletter.unsubscribe', str_repeat('x', 64)))->assertNotFound();
    });

    it('asks before unsubscribing and only unsubscribes on the button', function () {
        $subscriber = Subscriber::factory()->confirmed()->create(['email' => 'hiker@example.com']);

        $this->get(route('newsletter.unsubscribe', $subscriber->token))
            ->assertOk()
            ->assertSee('hiker@example.com')
            ->assertSee('action="'.route('newsletter.unsubscribe.confirm', $subscriber->token).'"', false);
        expect($subscriber->fresh()->isActive())->toBeTrue();

        $this->post(route('newsletter.unsubscribe.confirm', $subscriber->token))
            ->assertOk()
            ->assertSee("You're unsubscribed");
        expect($subscriber->fresh()->isActive())->toBeFalse();

        $this->get(route('newsletter.unsubscribe', $subscriber->token))->assertSee('already unsubscribed');
    });
});

describe('new event announcements', function () {
    beforeEach(function () {
        $this->admin = userWithRole(Role::Admin);
        $this->confirmed = Subscriber::factory()->confirmed()->count(2)->create();
        Subscriber::factory()->create();                         // not confirmed
        Subscriber::factory()->confirmed()->unsubscribed()->create();
        Subscriber::factory()->phoneOnly()->create();
    });

    it('emails confirmed subscribers when an upcoming event is published for the first time', function () {
        $event = Event::factory()->withMeetingPoints()->create();

        app(PublishEvent::class)->handle($event, $this->admin);

        Mail::assertQueuedCount(2);
        foreach ($this->confirmed as $subscriber) {
            Mail::assertQueued(NewEventAnnouncementMail::class, fn ($mail) => $mail->hasTo($subscriber->email) && $mail->event->is($event));
        }
    });

    it('does not announce again when an event is republished', function () {
        $event = Event::factory()->withMeetingPoints()->create();
        app(PublishEvent::class)->handle($event, $this->admin);
        app(UnpublishEvent::class)->handle($event, $this->admin);

        app(PublishEvent::class)->handle($event->fresh(), $this->admin);

        Mail::assertQueuedCount(2); // from the first publish only
    });

    it('does not announce past events published as archive entries', function () {
        $event = Event::factory()->past()->create();

        app(PublishEvent::class)->handle($event, $this->admin);

        Mail::assertNothingQueued();
    });

    it('builds the announcement with event details and an unsubscribe link', function () {
        $event = Event::factory()->create(['title' => 'Qadisha Valley Descent', 'location' => 'Bcharre', 'fee_cents' => 3000]);
        $subscriber = $this->confirmed->first();

        $mail = new NewEventAnnouncementMail($event, $subscriber);

        $mail->assertHasSubject('New hike: Qadisha Valley Descent · '.$event->starts_on->format('D j M'))
            ->assertSeeInHtml('Bcharre')
            ->assertSeeInHtml('$30')
            ->assertSeeInHtml($event->difficulty_level->label())
            ->assertSeeInHtml(route('events.index'))
            ->assertSeeInHtml(route('newsletter.unsubscribe', $subscriber->token));
        expect($mail->headers()->text)->toHaveKey('List-Unsubscribe');
    });
});
