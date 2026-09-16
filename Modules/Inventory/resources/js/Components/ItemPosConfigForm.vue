<script setup lang="ts">
import { Card, Badge, Button } from '@/components';

interface ModifierOption {
    id?: string | number;
    name: string;
    extra_price: number;
}

interface ModifierGroup {
    id?: string | number;
    name: string; // e.g. "Ice Level", "Toppings", "Cooking Temperature"
    is_required: boolean;
    min_selection: number;
    max_selection: number;
    options: ModifierOption[];
}

const props = defineProps<{
    form: {
        is_pos_available: boolean;
        is_pos_favorite: boolean;
        pos_display_name: string;
        pos_color: string;
        pos_kitchen_station: string;
        prompt_for_quantity: boolean;
        is_weighable: boolean;
        modifier_groups: ModifierGroup[];
    };
}>();

const colorOptions = [
    { name: 'Default Zinc', value: 'zinc', bgClass: 'bg-zinc-700' },
    { name: 'Emerald Green', value: 'emerald', bgClass: 'bg-emerald-600' },
    { name: 'Amber Orange', value: 'amber', bgClass: 'bg-amber-600' },
    { name: 'Sky Blue', value: 'sky', bgClass: 'bg-sky-600' },
    { name: 'Rose Red', value: 'rose', bgClass: 'bg-rose-600' },
    { name: 'Purple', value: 'purple', bgClass: 'bg-purple-600' },
    { name: 'Indigo', value: 'indigo', bgClass: 'bg-indigo-600' },
];

const addModifierGroup = () => {
    if (!props.form.modifier_groups) {
        props.form.modifier_groups = [];
    }
    props.form.modifier_groups.push({
        id: Date.now(),
        name: 'New Modifier Group',
        is_required: false,
        min_selection: 0,
        max_selection: 1,
        options: [
            { id: Date.now() + 1, name: 'Option 1', extra_price: 0 },
        ],
    });
};

const removeModifierGroup = (index: number) => {
    props.form.modifier_groups.splice(index, 1);
};

const addOption = (group: ModifierGroup) => {
    group.options.push({
        id: Date.now(),
        name: `Option ${group.options.length + 1}`,
        extra_price: 0,
    });
};

const removeOption = (group: ModifierGroup, optIdx: number) => {
    group.options.splice(optIdx, 1);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Section Header -->
        <div>
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                POS Touchscreen & Restaurant Station Configuration
            </h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                Optimize fast touchscreen checkout, quick-sale favorites, kitchen print routing, scale integration, and custom item modifiers.
            </p>
        </div>

        <!-- POS Master Switch Card -->
        <Card class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                        Available on Point of Sale (POS)
                    </h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Display this product in touchscreen registers, handheld POS terminals, and self-ordering kiosks.
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="form.is_pos_available"
                        class="sr-only peer"
                    />
                    <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-primary-600 dark:peer-checked:bg-primary-600"></div>
                </label>
            </div>

            <div v-if="form.is_pos_available" class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-800 space-y-6">
                <!-- Touch Tile Settings -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            POS Button Display Label
                        </label>
                        <input
                            v-model="form.pos_display_name"
                            type="text"
                            placeholder="Short name (e.g. Iced Latte)"
                            maxlength="30"
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 dark:focus:ring-primary-500/20 dark:focus:border-primary-500 dark:text-white"
                        />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Short label rendered on touch button tiles (max 30 chars).
                        </p>
                    </div>

                    <!-- Button Tile Color Palette -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Tile Color Palette
                        </label>
                        <div class="flex items-center gap-2 pt-1">
                            <button
                                v-for="c in colorOptions"
                                :key="c.value"
                                type="button"
                                @click="form.pos_color = c.value"
                                :class="[
                                    'w-7 h-7 rounded-full border-2 transition-transform',
                                    c.bgClass,
                                    form.pos_color === c.value ? 'ring-2 ring-offset-2 ring-primary-500 dark:ring-primary-400 scale-110 border-white' : 'border-transparent opacity-80 hover:opacity-100'
                                ]"
                                :title="c.name"
                            ></button>
                        </div>
                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                            Color coding speeds up cashier item lookup.
                        </p>
                    </div>
                </div>

                <!-- Toggles Grid: Favorites, Weighable, Prompt Qty -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                    <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">⭐ Pin to Favorites</span>
                            <p class="text-[11px] text-zinc-500">Fast access on home screen</p>
                        </div>
                        <input
                            type="checkbox"
                            v-model="form.is_pos_favorite"
                            class="rounded border-zinc-300 text-primary-600 focus:ring-primary-500"
                        />
                    </div>

                    <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">⚖️ Weighable Scale Item</span>
                            <p class="text-[11px] text-zinc-500">Produce / meat weight scale</p>
                        </div>
                        <input
                            type="checkbox"
                            v-model="form.is_weighable"
                            class="rounded border-zinc-300 text-primary-600 focus:ring-primary-500"
                        />
                    </div>

                    <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">🔢 Prompt for Quantity</span>
                            <p class="text-[11px] text-zinc-500">Ask cashier on tap</p>
                        </div>
                        <input
                            type="checkbox"
                            v-model="form.prompt_for_quantity"
                            class="rounded border-zinc-300 text-primary-600 focus:ring-primary-500"
                        />
                    </div>
                </div>

                <!-- Kitchen / Station Order Routing -->
                <div class="pt-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Kitchen / Preparation Routing Station
                    </label>
                    <select
                        v-model="form.pos_kitchen_station"
                        class="w-full max-w-md px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 dark:focus:ring-primary-500/20 dark:focus:border-primary-500 dark:text-white"
                    >
                        <option value="">None (Receipt Only)</option>
                        <option value="kitchen_main">Main Kitchen / Hot Line (KDS 1)</option>
                        <option value="kitchen_cold">Cold Prep / Salad Station</option>
                        <option value="bar">Bar / Beverage Station</option>
                        <option value="bakery">Bakery / Dessert Station</option>
                        <option value="grill">Grill / BBQ Station</option>
                    </select>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Automatically routes order ticket to kitchen display (KDS) or thermal printer when order is placed.
                    </p>
                </div>
            </div>
        </Card>

        <!-- Product Modifiers & Add-ons Card -->
        <Card v-if="form.is_pos_available" class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                        Modifier & Customization Groups
                    </h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Configure options, extra toppings, temperature, or add-ons chosen by customer at checkout.
                    </p>
                </div>
                <Button type="button" variant="outline" size="sm" @click="addModifierGroup">
                    + Add Modifier Group
                </Button>
            </div>

            <div class="space-y-4">
                <div
                    v-for="(grp, gIdx) in form.modifier_groups"
                    :key="grp.id || gIdx"
                    class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 space-y-4"
                >
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 dark:border-zinc-800 pb-3">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[11px] font-semibold text-zinc-500 uppercase mb-1">Group Title</label>
                            <input
                                v-model="grp.name"
                                type="text"
                                placeholder="e.g. Sugar Level, Extra Toppings"
                                class="w-full px-2.5 py-1 text-xs font-semibold bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded focus:ring-1 focus:ring-primary-500 dark:text-white"
                            />
                        </div>

                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-1.5 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="grp.is_required"
                                    class="rounded border-zinc-300 text-primary-600 focus:ring-primary-500"
                                />
                                <span>Mandatory Choice</span>
                            </label>

                            <button
                                type="button"
                                @click="removeModifierGroup(gIdx)"
                                class="text-xs text-rose-600 hover:underline"
                            >
                                Delete Group
                            </button>
                        </div>
                    </div>

                    <!-- Modifier Choices Table -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Available Choices</span>
                            <button
                                type="button"
                                @click="addOption(grp)"
                                class="text-xs text-indigo-600 hover:text-indigo-700 font-medium"
                            >
                                + Add Option
                            </button>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="(opt, oIdx) in grp.options"
                                :key="opt.id || oIdx"
                                class="flex items-center gap-3"
                            >
                                <input
                                    v-model="opt.name"
                                    type="text"
                                    placeholder="Option name (e.g. Less Sugar 50%)"
                                    class="flex-1 px-2.5 py-1 text-xs bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded focus:ring-1 focus:ring-primary-500 dark:text-white"
                                />
                                <div class="relative w-32">
                                    <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-[10px] text-zinc-400">+</span>
                                    <input
                                        v-model.number="opt.extra_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        class="w-full pl-5 pr-2 py-1 text-xs bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded focus:ring-1 focus:ring-primary-500 dark:text-white font-mono"
                                    />
                                </div>
                                <button
                                    type="button"
                                    @click="removeOption(grp, oIdx)"
                                    class="text-zinc-400 hover:text-rose-600 text-sm px-1"
                                >
                                    &times;
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="!form.modifier_groups || form.modifier_groups.length === 0" class="text-center py-6 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-lg">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        No modifier groups added. Click "+ Add Modifier Group" to configure options (e.g. drinks, customizable dishes).
                    </p>
                </div>
            </div>
        </Card>
    </div>
</template>
