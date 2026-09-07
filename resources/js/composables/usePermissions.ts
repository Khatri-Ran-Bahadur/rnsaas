import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();

    const membership = computed(() => (page.props.current_membership as any) ?? null);
    const isAdmin = computed(() => Boolean(membership.value?.is_admin));
    const permissions = computed<string[]>(() => membership.value?.permissions ?? []);

    const can = (permission: string): boolean => {
        if (isAdmin.value) return true;
        return permissions.value.includes(permission);
    };

    const cannot = (permission: string): boolean => !can(permission);

    return {
        membership,
        isAdmin,
        permissions,
        can,
        cannot,
    };
}
