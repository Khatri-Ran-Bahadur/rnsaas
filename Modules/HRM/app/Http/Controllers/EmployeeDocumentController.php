<?php

namespace Modules\HRM\Http\Controllers;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\HRM\Application\Actions\EmployeeDocuments\CreateEmployeeDocumentAction;
use Modules\HRM\Application\Actions\EmployeeDocuments\DeleteEmployeeDocumentAction;
use Modules\HRM\Application\Actions\EmployeeDocuments\ListEmployeeDocumentsAction;
use Modules\HRM\Application\Actions\EmployeeDocuments\ToggleEmployeeDocumentStatusAction;
use Modules\HRM\Application\Actions\EmployeeDocuments\UpdateEmployeeDocumentAction;
use Modules\HRM\Domain\Enums\EmployeeDocumentStatus;
use Modules\HRM\Domain\Enums\EmployeeDocumentType;
use Modules\HRM\Http\Requests\EmployeeDocuments\IndexEmployeeDocumentRequest;
use Modules\HRM\Http\Requests\EmployeeDocuments\StoreEmployeeDocumentRequest;
use Modules\HRM\Http\Requests\EmployeeDocuments\UpdateEmployeeDocumentRequest;
use Modules\HRM\Http\Resources\EmployeeDocumentResource;
use Modules\HRM\Models\EmployeeDocument;
use Modules\Tenancy\Models\TenantStaff;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController
{
    public function index(
        IndexEmployeeDocumentRequest $request,
        CurrentTenant $currentTenant,
        ListEmployeeDocumentsAction $action,
    ): Response {
        $tenantId = $currentTenant->id();

        $documents = $action->execute(
            $tenantId,
            $request->validated(),
        );

        // Calculate summary metrics for the header stats cards
        $stats = [
            'total' => EmployeeDocument::query()->forTenant($tenantId)->count(),
            'active' => EmployeeDocument::query()->forTenant($tenantId)->where('is_active', true)->count(),
            'expiring_soon' => EmployeeDocument::query()
                ->forTenant($tenantId)
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', now()->toDateString())
                ->whereDate('expiry_date', '<=', now()->addDays(30)->toDateString())
                ->where('status', '!=', EmployeeDocumentStatus::EXPIRED)
                ->count(),
            'pending' => EmployeeDocument::query()
                ->forTenant($tenantId)
                ->where('status', EmployeeDocumentStatus::PENDING)
                ->count(),
        ];

        // Active staff list for dropdown filters
        $staffMembers = TenantStaff::query()
            ->where('tenant_id', $tenantId)
            ->where('employment_status', 'active')
            ->with('user:id,name,email')
            ->orderBy('employee_code')
            ->get(['id', 'public_id', 'user_id', 'employee_code'])
            ->map(fn (TenantStaff $staff) => [
                'id' => $staff->id,
                'public_id' => $staff->public_id,
                'name' => $staff->user?->name ?? 'Staff #'.$staff->employee_code,
                'employee_code' => $staff->employee_code,
            ]);

        $documentTypes = collect(EmployeeDocumentType::cases())->map(fn (EmployeeDocumentType $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        $documentStatuses = collect(EmployeeDocumentStatus::cases())->map(fn (EmployeeDocumentStatus $status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ]);

        return Inertia::render('HRM/EmployeeDocuments/Index', [
            'documents' => EmployeeDocumentResource::collection($documents),
            'filters' => $request->validated(),
            'stats' => $stats,
            'staff_members' => $staffMembers,
            'document_types' => $documentTypes,
            'document_statuses' => $documentStatuses,
            'can' => [
                'manage' => (bool) $request->user()?->can('employee_documents.manage'),
            ],
        ]);
    }

    public function create(CurrentTenant $currentTenant): Response
    {
        abort_unless(
            auth()->user()?->can('employee_documents.manage'),
            403,
            'You do not have permission to upload employee documents.'
        );

        $tenantId = $currentTenant->id();

        $staffMembers = TenantStaff::query()
            ->where('tenant_id', $tenantId)
            ->where('employment_status', 'active')
            ->with('user:id,name,email')
            ->orderBy('employee_code')
            ->get(['id', 'public_id', 'user_id', 'employee_code'])
            ->map(fn (TenantStaff $staff) => [
                'id' => $staff->id,
                'public_id' => $staff->public_id,
                'name' => $staff->user?->name ?? 'Staff #'.$staff->employee_code,
                'employee_code' => $staff->employee_code,
            ]);

        $documentTypes = collect(EmployeeDocumentType::cases())->map(fn (EmployeeDocumentType $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        return Inertia::render('HRM/EmployeeDocuments/Create', [
            'staff_members' => $staffMembers,
            'document_types' => $documentTypes,
        ]);
    }

    public function store(
        StoreEmployeeDocumentRequest $request,
        CurrentTenant $currentTenant,
        CreateEmployeeDocumentAction $action,
    ): RedirectResponse {
        $action->execute(
            $request->toData(
                $currentTenant->id(),
                $request->user()->id,
            ),
        );

        return redirect()
            ->route('admin.hrm.employee-documents.index')
            ->with(
                'success',
                'Employee document uploaded successfully.',
            );
    }

    public function show(
        EmployeeDocument $employeeDocument,
        CurrentTenant $currentTenant,
    ): Response {
        abort_unless(
            auth()->user()?->can('employee_documents.view'),
            403,
            'You do not have permission to view this employee document.'
        );

        abort_unless(
            $employeeDocument->tenant_id === $currentTenant->id(),
            404,
        );

        $employeeDocument->load([
            'staff.user',
            'creator',
            'updater',
            'verifier',
        ]);

        return Inertia::render('HRM/EmployeeDocuments/Show', [
            'document' => new EmployeeDocumentResource($employeeDocument),
            'can' => [
                'manage' => (bool) auth()->user()?->can('employee_documents.manage'),
            ],
        ]);
    }

    public function edit(
        EmployeeDocument $employeeDocument,
        CurrentTenant $currentTenant,
    ): Response {
        abort_unless(
            auth()->user()?->can('employee_documents.manage'),
            403,
            'You do not have permission to edit employee documents.'
        );

        abort_unless(
            $employeeDocument->tenant_id === $currentTenant->id(),
            404,
        );

        $tenantId = $currentTenant->id();

        $employeeDocument->load(['staff.user']);

        $staffMembers = TenantStaff::query()
            ->where('tenant_id', $tenantId)
            ->where('employment_status', 'active')
            ->with('user:id,name,email')
            ->orderBy('employee_code')
            ->get(['id', 'public_id', 'user_id', 'employee_code'])
            ->map(fn (TenantStaff $staff) => [
                'id' => $staff->id,
                'public_id' => $staff->public_id,
                'name' => $staff->user?->name ?? 'Staff #'.$staff->employee_code,
                'employee_code' => $staff->employee_code,
            ]);

        $documentTypes = collect(EmployeeDocumentType::cases())->map(fn (EmployeeDocumentType $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        return Inertia::render('HRM/EmployeeDocuments/Edit', [
            'document' => new EmployeeDocumentResource($employeeDocument),
            'staff_members' => $staffMembers,
            'document_types' => $documentTypes,
        ]);
    }

    public function update(
        UpdateEmployeeDocumentRequest $request,
        EmployeeDocument $employeeDocument,
        CurrentTenant $currentTenant,
        UpdateEmployeeDocumentAction $action,
    ): RedirectResponse {
        $action->execute(
            $employeeDocument,
            $request->toData(
                $currentTenant->id(),
                $request->user()->id,
            ),
        );

        return redirect()
            ->route(
                'admin.hrm.employee-documents.show',
                $employeeDocument,
            )
            ->with(
                'success',
                'Employee document updated successfully.',
            );
    }

    public function toggleStatus(
        EmployeeDocument $employeeDocument,
        CurrentTenant $currentTenant,
        ToggleEmployeeDocumentStatusAction $action,
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->can('employee_documents.manage'),
            403,
        );

        $action->execute(
            $employeeDocument,
            $currentTenant->id(),
            auth()->id(),
        );

        return back()->with(
            'success',
            'Employee document status updated.',
        );
    }

    public function destroy(
        EmployeeDocument $employeeDocument,
        CurrentTenant $currentTenant,
        DeleteEmployeeDocumentAction $action,
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->can('employee_documents.manage'),
            403,
        );

        $action->execute(
            $employeeDocument,
            $currentTenant->id(),
        );

        return redirect()
            ->route('admin.hrm.employee-documents.index')
            ->with(
                'success',
                'Employee document deleted successfully.',
            );
    }

    public function download(
        EmployeeDocument $employeeDocument,
        CurrentTenant $currentTenant,
    ): StreamedResponse {
        abort_unless(
            auth()->user()?->can('employee_documents.view'),
            403,
            'You do not have permission to download this employee document.'
        );

        abort_unless(
            $employeeDocument->tenant_id === $currentTenant->id(),
            404,
        );

        $disk = Storage::disk($employeeDocument->file_disk);

        abort_unless(
            $disk->exists($employeeDocument->file_path),
            404,
            'The requested file could not be found on storage.'
        );

        return $disk->download(
            $employeeDocument->file_path,
            $employeeDocument->original_file_name,
            [
                'Content-Type' => $employeeDocument->mime_type ?: 'application/octet-stream',
            ]
        );
    }
}
