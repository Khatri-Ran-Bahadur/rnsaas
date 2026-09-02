import type { User } from './auth';

export type TenantStatus = 'pending' | 'active' | 'suspended' | 'cancelled';
export type TenantMembershipStatus = 'invited' | 'active' | 'suspended' | 'revoked';

export interface Tenant {
    id: number;
    public_id: string;
    name: string;
    slug: string;
    industry: string | null;
    status: TenantStatus;
    country_code: string | null;
    timezone: string;
    locale: string;
    currency: string;
    settings: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    users_count?: number;
    users?: TenantUser[];
}

export interface TenantMembershipPivot {
    status: TenantMembershipStatus;
    joined_at: string | null;
    invited_at: string | null;
    suspended_at: string | null;
    revoked_at: string | null;
    invited_by: number | null;
    suspended_by: number | null;
    revoked_by: number | null;
    settings: Record<string, unknown> | null;
    version: number;
}

export interface TenantUser extends User {
    pivot?: TenantMembershipPivot;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: PaginationLink[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}
