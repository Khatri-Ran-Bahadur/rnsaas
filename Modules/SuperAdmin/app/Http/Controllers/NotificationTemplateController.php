<?php

namespace Modules\SuperAdmin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\SuperAdmin\Models\NotificationTemplate;

class NotificationTemplateController extends Controller
{
    public function index(): Response
    {
        $templates = NotificationTemplate::orderBy('name')->get();

        return Inertia::render('SuperAdmin/NotificationTemplates/Index', [
            'templates' => $templates,
        ]);
    }

    public function edit(NotificationTemplate $template): Response
    {
        return Inertia::render('SuperAdmin/NotificationTemplates/Edit', [
            'template' => $template,
        ]);
    }

    public function update(Request $request, NotificationTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $template->update($validated);

        return redirect()->route('superadmin.notification-templates.index')
            ->with('success', "Notification template '{$template->name}' updated successfully.");
    }

    public function toggle(NotificationTemplate $template): RedirectResponse
    {
        $template->update(['is_active' => ! $template->is_active]);

        return back()->with('success', 'Template status updated.');
    }
}
