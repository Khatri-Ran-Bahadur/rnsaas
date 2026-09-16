<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Modules\SuperAdmin\Models\CustomPage;

class PageViewerController extends Controller
{
    public function show(string $slug): Response
    {
        $page = CustomPage::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $headerPages = CustomPage::where('is_published', true)
            ->where('show_in_header', true)
            ->orderBy('sort_order')
            ->select('title', 'slug')
            ->get();

        $footerPages = CustomPage::where('is_published', true)
            ->where('show_in_footer', true)
            ->orderBy('sort_order')
            ->select('title', 'slug')
            ->get();

        return Inertia::render('PublicPage', [
            'page' => $page,
            'headerPages' => $headerPages,
            'footerPages' => $footerPages,
        ]);
    }
}
