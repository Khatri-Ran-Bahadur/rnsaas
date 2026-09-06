<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admin\Http\Requests\StoreInvitationRequest;
use Modules\Tenancy\Application\Actions\Membership\InviteTenantMemberAction;
use Modules\Tenancy\Application\Actions\Membership\ResendTenantInvitationAction;
use Modules\Tenancy\Application\Actions\Membership\RevokeTenantMemberAction;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantRole;

class InvitationController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('invitations.view');

        $tenantId = $this->currentTenant->id();
        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();
        $perPage = $request->integer('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $invitations = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $query): void {
                $query->where('status', TenantMembershipStatus::Invited)
                    ->orWhereNotNull('invited_at');
            })
            ->with([
                'user:id,name,email',
                'role:id,name,slug',
                'invitedBy:id,name,email',
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('user', function (Builder $uq) use ($search): void {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', function (Builder $query) use ($status): void {
                if ($status === 'expired') {
                    $query->where('status', TenantMembershipStatus::Invited)
                        ->where('expires_at', '<', now());
                } elseif ($status === 'pending') {
                    $query->where('status', TenantMembershipStatus::Invited)
                        ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
                } else {
                    $query->where('status', $status);
                }
            })
            ->latest('invited_at')
            ->paginate($perPage)
            ->withQueryString();

        // Append calculated state
        $invitations->through(function (TenantMembership $item) {
            $data = $item->toArray();
            $data['is_expired'] = $item->isInvitationExpired();
            $data['invitation_url'] = $item->invitation_token
                ? url("/invitations/{$item->invitation_token}/accept")
                : null;

            return $data;
        });

        $roles = TenantRole::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Admin/Invitations/Index', [
            'invitations' => $invitations,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(
        StoreInvitationRequest $request,
        InviteTenantMemberAction $action,
    ): RedirectResponse {
        $this->authorize('invitations.manage');

        $tenant = $this->currentTenant->get();
        $email = $request->validated('email');
        $name = $request->validated('name');
        $roleId = $request->validated('role_id');

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $name ?? explode('@', $email)[0],
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        $action->execute($tenant, $user, $request->user(), $roleId);

        return redirect()->route('admin.invitations.index')
            ->with('success', "Invitation sent to {$user->email} successfully.");
    }

    public function resend(
        int $id,
        ResendTenantInvitationAction $action,
    ): RedirectResponse {
        $this->authorize('invitations.manage');

        $tenantId = $this->currentTenant->id();
        $membership = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->firstOrFail();

        $action->execute($membership);

        return redirect()->back()->with('success', 'Invitation resent successfully.');
    }

    public function revoke(
        Request $request,
        int $id,
        RevokeTenantMemberAction $action,
    ): RedirectResponse {
        $this->authorize('invitations.manage');

        $tenantId = $this->currentTenant->id();
        $membership = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->firstOrFail();

        $action->execute($membership, $request->user());

        return redirect()->back()->with('success', 'Invitation revoked.');
    }
}
