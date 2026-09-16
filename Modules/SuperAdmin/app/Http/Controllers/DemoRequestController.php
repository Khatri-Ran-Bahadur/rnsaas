<?php

namespace Modules\SuperAdmin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\SuperAdmin\Models\DemoRequest;

class DemoRequestController extends Controller
{
    /**
     * SuperAdmin: List all demo requests.
     */
    public function index(Request $request): Response
    {
        $query = DemoRequest::query()->orderBy('created_at', 'desc');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(20)->withQueryString();

        return Inertia::render('SuperAdmin/DemoRequests/Index', [
            'requests' => $requests,
            'filters' => [
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    /**
     * Public: Submit a demo request.
     */
    public function storePublic(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_name' => ['required', 'string', 'max:255'],
            'company_size' => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['status'] = 'pending';

        DemoRequest::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you! Your demo request has been received. Our enterprise team will contact you shortly.',
            ]);
        }

        return back()->with('success', 'Thank you! Your demo request has been received.');
    }

    /**
     * SuperAdmin: Update demo request status or notes.
     */
    public function update(Request $request, DemoRequest $demoRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,contacted,scheduled,completed'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $demoRequest->update($validated);

        return back()->with('success', 'Demo request updated successfully.');
    }

    /**
     * SuperAdmin: Delete demo request.
     */
    public function destroy(DemoRequest $demoRequest): RedirectResponse
    {
        $demoRequest->delete();

        return back()->with('success', 'Demo request deleted.');
    }
}
