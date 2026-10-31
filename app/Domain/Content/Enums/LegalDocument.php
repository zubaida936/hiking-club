<?php

namespace App\Domain\Content\Enums;

/**
 * The legal pages of the site, each edited in CMS → Legal pages.
 */
enum LegalDocument: string
{
    case Privacy = 'privacy';
    case Terms = 'terms';

    /**
     * Title used until an editor sets one.
     */
    public function defaultTitle(): string
    {
        return match ($this) {
            self::Privacy => 'Privacy Policy',
            self::Terms => 'Terms & Conditions',
        };
    }

    /**
     * Public route name (the footer and consent checkboxes link by name).
     */
    public function routeName(): string
    {
        return match ($this) {
            self::Privacy => 'legal.privacy',
            self::Terms => 'legal.terms',
        };
    }
}
