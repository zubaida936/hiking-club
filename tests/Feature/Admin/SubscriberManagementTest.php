<?php

use App\Domain\Identity\Enums\Role;
use App\Models\Subscriber;

beforeEach(function () {
    $this->admin = userWithRole(Role::Admin);
});

it('is open to admins but not to content editors or guides', function () {
    $subscriber = Subscriber::factory()->create();

    $this->actingAs($this->admin)->get(route('admin.subscribers.index'))->assertOk();

    foreach ([Role::Editor, Role::Guide] as $role) {
        $user = userWithRole($role);
        $this->actingAs($user)->get(route('admin.subscribers.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.subscribers.export'))->assertForbidden();
        $this->actingAs($user)->delete(route('admin.subscribers.destroy', $subscriber))->assertForbidden();
    }

    expect(Subscriber::count())->toBe(1);
});

it('lists subscribers by status with counts', function () {
    Subscriber::factory()->confirmed()->create(['email' => 'confirmed@example.com']);
    Subscriber::factory()->create(['email' => 'pending@example.com']);
    Subscriber::factory()->phoneOnly()->create(['phone' => '+96170123456']);
    Subscriber::factory()->confirmed()->unsubscribed()->create(['email' => 'gone@example.com']);

    $this->actingAs($this->admin)->get(route('admin.subscribers.index'))
        ->assertSee(['confirmed@example.com', 'pending@example.com', '+96170123456', 'Awaiting confirmation', 'Phone only'])
        ->assertDontSee('gone@example.com');

    $see = fn (string $status) => $this->actingAs($this->admin)->get(route('admin.subscribers.index', ['status' => $status]));
    $see('confirmed')->assertSee('confirmed@example.com')->assertDontSee('pending@example.com');
    $see('pending')->assertSee('pending@example.com')->assertDontSee('confirmed@example.com');
    $see('phone')->assertSee('+96170123456')->assertDontSee('pending@example.com');
    $see('unsubscribed')->assertSee('gone@example.com')->assertDontSee('confirmed@example.com');

    $this->actingAs($this->admin)->get(route('admin.subscribers.index', ['q' => '70 123']))->assertSee('+96170123456')->assertDontSee('pending@example.com');
    $this->actingAs($this->admin)->get(route('admin.subscribers.index', ['q' => 'pending@']))->assertSee('pending@example.com')->assertDontSee('+96170123456');
});

it('downloads the filtered list as CSV with formulas neutralised', function () {
    Subscriber::factory()->confirmed()->create(['email' => 'rima@example.com', 'phone' => '+96170123456']);
    Subscriber::factory()->create(['email' => '=cmd@example.com']);
    Subscriber::factory()->unsubscribed()->create(['email' => 'gone@example.com']);

    $csv = $this->actingAs($this->admin)->get(route('admin.subscribers.export'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->streamedContent();

    expect($csv)->toStartWith("\xEF\xBB\xBFEmail,Phone,Status,\"Signed up\",Unsubscribed")
        ->toContain("rima@example.com,'+96170123456,Confirmed", "'=cmd@example.com")
        ->not->toContain('gone@example.com');

    expect($this->actingAs($this->admin)->get(route('admin.subscribers.export', ['status' => 'unsubscribed']))->streamedContent())
        ->toContain('gone@example.com')->not->toContain('rima@example.com');
});

it('unsubscribes and deletes subscribers', function () {
    $subscriber = Subscriber::factory()->confirmed()->create();

    $this->actingAs($this->admin)->from(route('admin.subscribers.index'))
        ->post(route('admin.subscribers.unsubscribe', $subscriber))
        ->assertRedirect(route('admin.subscribers.index'));
    expect($subscriber->fresh()->isActive())->toBeFalse()
        ->and(Subscriber::receivingEmails()->count())->toBe(0);

    $this->actingAs($this->admin)->delete(route('admin.subscribers.destroy', $subscriber))->assertRedirect();
    expect(Subscriber::count())->toBe(0);
});
