<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\Actions\SaveLegalPage;
use App\Domain\Content\Enums\LegalDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LegalPageRequest;
use App\Models\LegalPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Privacy Policy and Terms: a fixed set of pages, edited but never created or deleted.
 */
class LegalPageController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage', LegalPage::class);

        return view('admin.legal-pages.index', [
            'pages' => array_map(LegalPage::for(...), LegalDocument::cases()),
        ]);
    }

    public function edit(LegalDocument $document): View
    {
        Gate::authorize('manage', LegalPage::class);

        return view('admin.legal-pages.edit', ['page' => LegalPage::for($document)]);
    }

    public function update(LegalPageRequest $request, LegalDocument $document, SaveLegalPage $action): RedirectResponse
    {
        Gate::authorize('manage', LegalPage::class);

        $action->handle($document, $request->toData());

        return redirect()->route('admin.legal-pages.edit', $document)->with('success', 'Page saved.');
    }
}
