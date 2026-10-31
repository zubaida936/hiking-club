<?php

namespace App\Http\Controllers\Site;

use App\Domain\Content\Enums\LegalDocument;
use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use Illuminate\View\View;

/**
 * Privacy Policy and Terms (CMS → Legal pages).
 */
class LegalPageController extends Controller
{
    public function privacy(): View
    {
        return $this->show(LegalDocument::Privacy);
    }

    public function terms(): View
    {
        return $this->show(LegalDocument::Terms);
    }

    private function show(LegalDocument $document): View
    {
        return view('site.legal.show', ['page' => LegalPage::for($document)]);
    }
}
