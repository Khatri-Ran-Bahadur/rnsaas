<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admin\Actions\GetOrganizationMembersAction;

final class MemberController
{
    public function index(
        Request $request,
        GetOrganizationMembersAction $action,
    ): Response {
        $perPage = min(
            max($request->integer('per_page', 20), 10),
            100,
        );

        return Inertia::render('Admin/Members/Index', [
            'members' => $action->handle($perPage),
        ]);
    }
}
