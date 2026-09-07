export interface DocumentStaff {
    id?: number;
    public_id: string;
    name: string;
    employee_code: string;
    department?: string | null;
    designation?: string | null;
}

export interface EmployeeDocumentItem {
    public_id: string;
    tenant_staff_id?: number;
    staff?: DocumentStaff | null;
    title: string;
    document_type: string;
    document_type_label: string;
    document_number?: string | null;
    file_name: string;
    file_url?: string;
    file_size_formatted?: string;
    file_extension?: string;
    issue_date?: string | null;
    expiry_date?: string | null;
    status: 'active' | 'expired' | 'expiring_soon' | 'archived' | string;
    status_label: string;
    is_verified: boolean;
    verification_notes?: string | null;
    verified_at?: string | null;
    description?: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface DocumentFilters {
    search?: string | null;
    tenant_staff_id?: number | string | null;
    document_type?: string | null;
    status?: string | null;
    expiring_soon?: boolean | string | null;
    per_page?: number;
}

export interface DocumentStats {
    total: number;
    approved: number;
    pending: number;
    expiring_soon: number;
}

export interface PaginatedDocuments {
    data: EmployeeDocumentItem[];
    links?: Array<{ url: string | null; label: string; active: boolean }>;
    meta?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
}

export interface DocumentStaffOption {
    id: number;
    public_id: string;
    name: string;
    employee_code: string;
}

export interface DocumentTypeOption {
    value: string;
    label: string;
}

export interface DocumentIndexProps {
    documents: PaginatedDocuments;
    filters: DocumentFilters;
    staff_members: DocumentStaffOption[];
    document_types: DocumentTypeOption[];
    stats?: DocumentStats;
    can?: {
        manage?: boolean;
    };
}
