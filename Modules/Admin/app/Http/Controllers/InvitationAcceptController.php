<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LogicException;
use Modules\Tenancy\Application\Actions\Membership\AcceptTenantInvitationAction;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\TenantMembership;

class InvitationAcceptController extends Controller
{
    public function __invoke(
        Request $request,
        string $token,
        AcceptTenantInvitationAction $action,
    ): RedirectResponse {
        $membership = TenantMembership::query()
            ->where('invitation_token', $token)
            ->with(['tenant', 'user'])
            ->first();

        if (! $membership) {
            return redirect()->route('login')
                ->with('error', 'This invitation link is invalid or has already been accepted.');
        }

        $tenantIsActive = $membership->tenant && (
            $membership->tenant->status instanceof TenantStatus
                ? $membership->tenant->status === TenantStatus::Active
                : $membership->tenant->status === TenantStatus::Active->value
        );

        if (! $tenantIsActive) {
            return redirect()->route('login')
                ->with('error', 'This organization is currently inactive or suspended.');
        }

        if ($membership->isInvitationExpired()) {
            return redirect()->route('login')
                ->with('error', 'This invitation link has expired. Please ask your administrator to resend it.');
        }

        $user = $membership->user;

        try {
            $action->execute($membership, $user);
        } catch (LogicException $e) {
            return redirect()->route('login')->with('error', $e->getMessage());
        }

        // Log the user in if not logged in or logged in as a different user
        if (! Auth::check() || Auth::id() !== $user->id) {
            Auth::login($user);
        }

        $request->session()->put('current_tenant_id', $membership->tenant_id);

        return redirect()->route('admin.dashboard')
            ->with('success', "Welcome to {$membership->tenant->name}! You have joined as an organization member.");
    }
}
