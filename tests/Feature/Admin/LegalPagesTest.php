<?php

use App\Domain\Content\Enums\LegalDocument;
use App\Domain\Identity\Enums\Role;
use App\Models\LegalPage;

beforeEach(function () {
    $this->editor = userWithRole(Role::Editor);
});

describe('CMS', function () {
    it('lists both pages for content editors, before any is written', function () {
        $this->actingAs($this->editor)->get(route('admin.legal-pages.index'))
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('Terms &amp; Conditions', false)
            ->assertSee('No text yet')
            ->assertSee(route('admin.legal-pages.edit', LegalDocument::Terms));

        $this->actingAs($this->editor)->get(route('admin.dashboard'))->assertSee(route('admin.legal-pages.index'));
    });

    it('is closed to guides', function () {
        $guide = userWithRole(Role::Guide);

        $this->actingAs($guide)->get(route('admin.legal-pages.index'))->assertForbidden();
        $this->actingAs($guide)->get(route('admin.legal-pages.edit', LegalDocument::Privacy))->assertForbidden();
        $this->actingAs($guide)->put(route('admin.legal-pages.update', LegalDocument::Privacy), ['title' => 'Hacked', 'body' => 'x'])->assertForbidden();

        expect(LegalPage::count())->toBe(0);
    });

    it('saves a page, then updates the same row', function () {
        $this->actingAs($this->editor)->get(route('admin.legal-pages.edit', LegalDocument::Privacy))
            ->assertOk()->assertSee('value="Privacy Policy"', false);

        $this->actingAs($this->editor)->put(route('admin.legal-pages.update', LegalDocument::Privacy), [
            'title' => '  Privacy   Policy ',
            'body' => "## What we collect\n\nYour name and phone.",
        ])->assertRedirect(route('admin.legal-pages.edit', LegalDocument::Privacy))->assertSessionHas('success');

        $this->actingAs($this->editor)->put(route('admin.legal-pages.update', LegalDocument::Privacy), [
            'title' => 'Privacy Policy',
            'body' => 'Updated text.',
        ]);

        expect(LegalPage::sole())
            ->document->toBe(LegalDocument::Privacy)
            ->title->toBe('Privacy Policy')
            ->body->toBe('Updated text.');

        $this->actingAs($this->editor)->get(route('admin.legal-pages.index'))->assertSee('Published');
    });

    it('requires a title', function () {
        $this->actingAs($this->editor)->put(route('admin.legal-pages.update', LegalDocument::Terms), ['title' => '', 'body' => 'x'])
            ->assertSessionHasErrors('title');

        expect(LegalPage::count())->toBe(0);
    });

    it('returns 404 for an unknown page', function () {
        $this->actingAs($this->editor)->get('/'.trim(config('cms.path'), '/').'/legal-pages/cookies/edit')->assertNotFound();
    });
});

describe('site', function () {
    it('says the page is being prepared while it has no text', function () {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('We are preparing this page')
            ->assertSee('noindex', false);

        $this->get(route('legal.terms'))->assertOk()->assertSee('Terms &amp; Conditions', false);
    });

    it('shows the text as Markdown with its last updated date', function () {
        $this->travelTo('2026-11-05 12:00');
        LegalPage::factory()->create([
            'document' => LegalDocument::Terms,
            'title' => 'Terms of booking',
            'body' => "## Cancellations\n\nFull refund up to **48 hours** before.",
        ]);

        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Terms of booking')
            ->assertSee('<h2>Cancellations</h2>', false)
            ->assertSee('<strong>48 hours</strong>', false)
            ->assertSee('Last updated')
            ->assertSee('5 November 2026')
            ->assertDontSee('We are preparing this page');
    });

    it('escapes raw HTML in the text', function () {
        LegalPage::factory()->create(['body' => "<script>alert(1)</script>\n\nSee [this](javascript:alert(1)) and [that](https://example.com)."]);

        $html = $this->get(route('legal.privacy'))->getContent();

        expect($html)
            ->not->toContain('<script>alert(1)</script>')
            ->toContain('&lt;script&gt;')
            ->not->toContain('href="javascript:')
            ->toContain('href="https://example.com"');
    });

    it('lists only written legal pages in the sitemap', function () {
        LegalPage::factory()->create(['document' => LegalDocument::Privacy]);

        $this->get('/sitemap.xml')
            ->assertSee('<loc>'.route('legal.privacy').'</loc>', false)
            ->assertDontSee(route('legal.terms'), false);
    });
});
