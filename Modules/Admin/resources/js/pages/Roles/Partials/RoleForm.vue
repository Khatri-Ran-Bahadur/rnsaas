<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

export interface PermissionItem {
    name: string;
    label: string;
}

export interface PermissionSubGroup {
    key: string;
    label: string;
    permissions: PermissionItem[];
}

export interface PermissionGroup {
    key: string;
    label: string;
    permissions: PermissionItem[];
    sub_groups?: PermissionSubGroup[];
}

const props = withDefaults(
    defineProps<{
        permissionGroups: PermissionGroup[];
        initialName?: string;
        initialDescription?: string;
        initialIsActive?: boolean;
        initialPermissions?: string[];
        isEdit?: boolean;
        isSystem?: boolean;
        roleId?: number;
        submitUrl: string;
        submitMethod?: 'post' | 'put';
        cancelUrl?: string;
    }>(),
    {
        initialName: '',
        initialDescription: '',
        initialIsActive: true,
        initialPermissions: () => [],
        isEdit: false,
        isSystem: false,
        roleId: undefined,
        submitMethod: 'post',
        cancelUrl: '/admin/roles',
    }
);

// ─── Form State ─────────────────────────────────────────────────────────────

const form = useForm({
    name: props.initialName,
    description: props.initialDescription,
    is_active: props.initialIsActive,
    permissions: [...props.initialPermissions],
});

// ─── Normalizing Permission Groups ──────────────────────────────────────────

const normalizedGroups = computed<PermissionGroup[]>(() => {
    return props.permissionGroups.map((group) => {
        if (group.sub_groups && group.sub_groups.length > 0) {
            return group;
        }

        const subGroupMap = new Map<
            string,
            { key: string; label: string; permissions: PermissionItem[] }
        >();

        group.permissions.forEach((perm) => {
            const parts = perm.name.split('.');
            const subKey = parts[0] || group.key;
            const subLabel = subKey.replace(/[-_]/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

            if (!subGroupMap.has(subKey)) {
                subGroupMap.set(subKey, {
                    key: subKey,
                    label: subLabel,
                    permissions: [],
                });
            }
            subGroupMap.get(subKey)!.permissions.push(perm);
        });

        return {
            ...group,
            sub_groups: Array.from(subGroupMap.values()),
        };
    });
});

// ─── Active Tab State ───────────────────────────────────────────────────────

const activeTabKey = ref<string>(normalizedGroups.value[0]?.key || '');

watch(
    normalizedGroups,
    (groups) => {
        if (
            !groups.some((g) => g.key === activeTabKey.value) &&
            groups.length > 0
        ) {
            activeTabKey.value = groups[0].key;
        }
    },
    { immediate: true }
);

// ─── Search Filtering ───────────────────────────────────────────────────────

const searchQuery = ref('');

const filteredGroups = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) {
        return normalizedGroups.value;
    }

    return normalizedGroups.value
        .map((group) => {
            const subGroups = (group.sub_groups || [])
                .map((subGroup) => {
                    const matchedPermissions = subGroup.permissions.filter(
                        (p) =>
                            p.name.toLowerCase().includes(query) ||
                            p.label.toLowerCase().includes(query) ||
                            subGroup.label.toLowerCase().includes(query)
                    );

                    return {
                        ...subGroup,
                        permissions: matchedPermissions,
                    };
                })
                .filter((subGroup) => subGroup.permissions.length > 0);

            const allFilteredPermissions = subGroups.flatMap((sg) => sg.permissions);

            return {
                ...group,
                permissions: allFilteredPermissions,
                sub_groups: subGroups,
            };
        })
        .filter((group) => group.permissions.length > 0);
});

const activeGroup = computed(() => {
    return (
        filteredGroups.value.find((g) => g.key === activeTabKey.value) ||
        filteredGroups.value[0] ||
        null
    );
});

watch(filteredGroups, (groups) => {
    if (searchQuery.value.trim() && groups.length > 0) {
        const currentHasResults = groups.some((g) => g.key === activeTabKey.value);
        if (!currentHasResults) {
            activeTabKey.value = groups[0].key;
        }
    }
});

// ─── Checkbox Helpers ───────────────────────────────────────────────────────

const isPermissionSelected = (name: string): boolean => {
    return form.permissions.includes(name);
};

const togglePermission = (name: string) => {
    const idx = form.permissions.indexOf(name);
    if (idx >= 0) {
        form.permissions.splice(idx, 1);
    } else {
        form.permissions.push(name);
    }
};

const isSubGroupFullySelected = (subGroup: PermissionSubGroup): boolean => {
    if (subGroup.permissions.length === 0) return false;
    return subGroup.permissions.every((p) => form.permissions.includes(p.name));
};

const isSubGroupPartiallySelected = (subGroup: PermissionSubGroup): boolean => {
    if (isSubGroupFullySelected(subGroup)) return false;
    return subGroup.permissions.some((p) => form.permissions.includes(p.name));
};

const toggleSubGroup = (subGroup: PermissionSubGroup) => {
    const permNames = subGroup.permissions.map((p) => p.name);
    if (isSubGroupFullySelected(subGroup)) {
        form.permissions = form.permissions.filter(
            (name) => !permNames.includes(name)
        );
    } else {
        permNames.forEach((name) => {
            if (!form.permissions.includes(name)) {
                form.permissions.push(name);
            }
        });
    }
};

const selectAllGlobal = () => {
    const all: string[] = [];
    normalizedGroups.value.forEach((g) => {
        g.permissions.forEach((p) => all.push(p.name));
    });
    form.permissions = Array.from(new Set(all));
};

const clearAllGlobal = () => {
    form.permissions = [];
};

// ─── Submit ─────────────────────────────────────────────────────────────────

const submitForm = () => {
    if (props.submitMethod === 'put') {
        form.put(props.submitUrl);
    } else {
        form.post(props.submitUrl);
    }
};
</script>

<template>
    <div class="rounded-xl border border-zinc-200/80 bg-white p-6 sm:p-8 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <form @submit.prevent="submitForm" class="space-y-6">
            <!-- ─── System Role Banner (If applicable) ────────────────────────── -->
            <div
                v-if="isSystem"
                class="rounded-xl border border-purple-200 bg-purple-50/50 p-4 text-xs text-purple-900 dark:border-purple-900 dark:bg-purple-950/40 dark:text-purple-200"
            >
                <div class="flex items-center gap-2 font-semibold">
                    <svg class="h-4 w-4 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>System Role</span>
                </div>
                <p class="mt-1 text-[11px] text-purple-700 dark:text-purple-300">
                    This is a standard system role. Its name and system status are locked, but you can customize granted permissions below.
                </p>
            </div>

            <!-- ─── Role Information Fields ──────────────────────────────────── -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <!-- Role Name -->
                <div>
                    <label
                        for="role-name"
                        class="block text-xs font-semibold text-zinc-800 dark:text-zinc-200 mb-1.5"
                    >
                        Role Name <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="role-name"
                        v-model="form.name"
                        type="text"
                        required
                        :disabled="isSystem"
                        placeholder="e.g. HR Supervisor, Senior Accountant"
                        :class="[
                            'w-full rounded-md border px-3.5 py-2 text-xs transition-colors focus:outline-hidden focus:ring-1 focus:ring-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed',
                            form.errors.name
                                ? 'border-rose-300 bg-rose-50/20 text-rose-900 focus:border-rose-500 dark:border-rose-700 dark:bg-rose-950/20 dark:text-rose-100'
                                : 'border-zinc-300 bg-white text-zinc-900 placeholder-zinc-400 hover:border-zinc-400 focus:border-indigo-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:hover:border-zinc-600',
                        ]"
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- Description -->
                <div>
                    <label
                        for="role-description"
                        class="block text-xs font-semibold text-zinc-800 dark:text-zinc-200 mb-1.5"
                    >
                        Description
                    </label>
                    <input
                        id="role-description"
                        v-model="form.description"
                        type="text"
                        placeholder="Describe role responsibilities..."
                        class="w-full rounded-md border border-zinc-300 bg-white px-3.5 py-2 text-xs text-zinc-900 placeholder-zinc-400 transition-colors hover:border-zinc-400 focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:hover:border-zinc-600"
                    />
                    <p
                        v-if="form.errors.description"
                        class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400"
                    >
                        {{ form.errors.description }}
                    </p>
                </div>
            </div>

            <!-- Active Checkbox -->
            <div v-if="!isSystem" class="pt-1">
                <label class="inline-flex items-center gap-2.5 text-xs font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer select-none">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-0 dark:border-zinc-700 dark:bg-zinc-800 cursor-pointer"
                    />
                    <span>Enable this role for assignment to organization members</span>
                </label>
            </div>

            <hr class="border-zinc-200/80 dark:border-zinc-800" />

            <!-- ─── Search & Global Actions Bar ──────────────────────────────── -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search features..."
                        class="w-full sm:w-72 rounded-md border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 placeholder-zinc-400 transition-colors hover:border-zinc-400 focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:hover:border-zinc-600"
                    />
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        Selected: <strong class="font-semibold text-indigo-600 dark:text-indigo-400">{{ form.permissions.length }}</strong> permissions
                    </span>
                    <button
                        type="button"
                        class="rounded-md border border-zinc-300 bg-white px-2.5 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 cursor-pointer"
                        @click="selectAllGlobal"
                    >
                        Select All
                    </button>
                    <button
                        type="button"
                        class="rounded-md border border-zinc-300 bg-white px-2.5 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 cursor-pointer"
                        @click="clearAllGlobal"
                    >
                        Clear All
                    </button>
                </div>
            </div>

            <!-- ─── Module Tabs Bar (Exact SuperAdmin Matching Style) ─────────── -->
            <div
                class="bg-slate-100/90 dark:bg-zinc-800/70 p-1 rounded-lg flex items-center gap-1 overflow-x-auto scrollbar-none"
            >
                <button
                    v-for="group in filteredGroups"
                    :key="group.key"
                    type="button"
                    :class="[
                        'px-3.5 py-1.5 text-xs rounded-md transition-all whitespace-nowrap cursor-pointer',
                        activeTabKey === group.key
                            ? 'font-semibold text-zinc-900 dark:text-white bg-white dark:bg-zinc-900 border border-zinc-300/80 dark:border-zinc-700 shadow-2xs'
                            : 'font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white',
                    ]"
                    @click="activeTabKey = group.key"
                >
                    {{ group.label }}
                </button>
            </div>

            <!-- ─── Sub-Group Cards (Exact Reference Border Separated Layout) ─── -->
            <div class="space-y-4">
                <!-- Empty state when no features match search -->
                <div
                    v-if="!activeGroup || activeGroup.sub_groups?.length === 0"
                    class="rounded-lg border border-dashed border-zinc-300 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        No features found matching "{{ searchQuery }}".
                    </p>
                </div>

                <!-- Sub-Group Bordered Card -->
                <div
                    v-for="subGroup in activeGroup?.sub_groups"
                    :key="subGroup.key"
                    class="rounded-lg border border-zinc-200 bg-white p-5 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <!-- Feature Header with Select All Checkbox -->
                    <div class="mb-3">
                        <label
                            class="inline-flex items-center gap-2.5 cursor-pointer select-none"
                        >
                            <input
                                type="checkbox"
                                :checked="isSubGroupFullySelected(subGroup)"
                                :indeterminate.prop="isSubGroupPartiallySelected(subGroup)"
                                @change="toggleSubGroup(subGroup)"
                                class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-0 cursor-pointer dark:border-zinc-600 dark:bg-zinc-800"
                            />
                            <span class="text-xs font-bold text-zinc-900 dark:text-white">
                                {{ subGroup.label }}
                            </span>
                        </label>
                    </div>

                    <!-- 3-Column Checkbox Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-3">
                        <label
                            v-for="permission in subGroup.permissions"
                            :key="permission.name"
                            class="flex items-center gap-2.5 cursor-pointer select-none"
                        >
                            <input
                                type="checkbox"
                                :checked="isPermissionSelected(permission.name)"
                                @change="togglePermission(permission.name)"
                                class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-0 cursor-pointer dark:border-zinc-600 dark:bg-zinc-800"
                            />
                            <span
                                class="text-xs text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white transition-colors"
                            >
                                {{ permission.label }}
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Error message if no permissions selected -->
            <p v-if="form.errors.permissions" class="text-xs font-semibold text-rose-500">
                {{ form.errors.permissions }}
            </p>

            <!-- ─── Action Buttons Bar ───────────────────────────────────────── -->
            <div
                class="flex items-center justify-end gap-3 pt-5 border-t border-zinc-100 dark:border-zinc-800"
            >
                <Link
                    :href="cancelUrl"
                    class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50 transition-colors dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                >
                    Cancel
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 active:bg-indigo-800 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                >
                    <svg
                        v-if="form.processing"
                        class="h-3.5 w-3.5 animate-spin"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        />
                    </svg>
                    <span>{{ isEdit ? 'Update Role' : 'Save Role' }}</span>
                </button>
            </div>
        </form>
    </div>
</template>
