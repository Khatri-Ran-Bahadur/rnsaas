<?php

namespace Modules\SuperAdmin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\SuperAdmin\Models\CustomPage;

class CustomPageController extends Controller
{
    public function index(): Response
    {
        $pages = CustomPage::orderBy('sort_order')->orderBy('created_at', 'desc')->get();

        return Inertia::render('SuperAdmin/Pages/Index', [
            'pages' => $pages,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('SuperAdmin/Pages/Form', [
            'page' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:custom_pages,slug'],
            'content' => ['required', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'is_published' => ['nullable', 'boolean'],
            'show_in_header' => ['nullable', 'boolean'],
            'show_in_footer' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slug = filled($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $counter = 1;
        $originalSlug = $slug;
        while (CustomPage::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }
        $validated['slug'] = $slug;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_published'] = $request->boolean('is_published', true);
        $validated['show_in_header'] = $request->boolean('show_in_header', false);
        $validated['show_in_footer'] = $request->boolean('show_in_footer', false);

        CustomPage::create($validated);

        return redirect()->route('superadmin.pages.index')
            ->with('success', "Page '{$validated['title']}' created successfully.");
    }

    public function edit(CustomPage $page): Response
    {
        return Inertia::render('SuperAdmin/Pages/Form', [
            'page' => $page,
        ]);
    }

    public function update(Request $request, CustomPage $page): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', "unique:custom_pages,slug,{$page->id}"],
            'content' => ['required', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'is_published' => ['required', 'boolean'],
            'show_in_header' => ['required', 'boolean'],
            'show_in_footer' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['slug'] = Str::slug($validated['slug']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $page->update($validated);

        return redirect()->route('superadmin.pages.index')
            ->with('success', "Page '{$page->title}' updated successfully.");
    }

    public function destroy(CustomPage $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('superadmin.pages.index')
            ->with('success', 'Page deleted successfully.');
    }
}
