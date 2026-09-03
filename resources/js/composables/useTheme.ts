import { ref } from 'vue';

export type ThemeMode = 'light' | 'dark' | 'system';
export type ColorTheme = 'indigo' | 'emerald' | 'blue';

export interface ColorThemeOption {
    id: ColorTheme;
    name: string;
    label: string;
    colorHex: string;
    bgClass: string;
    description: string;
}

export const AVAILABLE_COLOR_THEMES: ColorThemeOption[] = [
    {
        id: 'indigo',
        name: 'Indigo',
        label: 'Modern Indigo',
        colorHex: '#4f46e5',
        bgClass: 'bg-indigo-600',
        description: 'Sleek & vibrant modern SaaS aesthetic',
    },
    {
        id: 'emerald',
        name: 'Emerald',
        label: 'Tech Emerald',
        colorHex: '#059669',
        bgClass: 'bg-emerald-600',
        description: 'Fresh & energetic tech aesthetic',
    },
    {
        id: 'blue',
        name: 'Blue',
        label: 'Ocean Blue',
        colorHex: '#2563eb',
        bgClass: 'bg-blue-600',
        description: 'Clean & executive enterprise aesthetic',
    },
];

const THEME_STORAGE_KEY = 'sathisaas_theme_preference';
const COLOR_THEME_STORAGE_KEY = 'sathisaas_color_theme';

const currentTheme = ref<ThemeMode>('light');
const currentColorTheme = ref<ColorTheme>('indigo');
const isDark = ref(false);

const applyColorTheme = (colorTheme: ColorTheme) => {
    if (typeof document === 'undefined') return;
    document.documentElement.setAttribute('data-theme', colorTheme);
};

const applyThemeMode = (mode: ThemeMode) => {
    if (typeof document === 'undefined') return;

    let dark = false;
    if (mode === 'dark') {
        dark = true;
    } else if (mode === 'light') {
        dark = false;
    } else if (mode === 'system') {
        dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    isDark.value = dark;
    if (dark) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

export function useTheme() {
    const initTheme = () => {
        if (typeof window === 'undefined') return;

        // Initialize color theme (Default: Indigo)
        const storedColor = localStorage.getItem(COLOR_THEME_STORAGE_KEY) as ColorTheme | null;
        if (storedColor && ['indigo', 'emerald', 'blue'].includes(storedColor)) {
            currentColorTheme.value = storedColor;
        } else {
            currentColorTheme.value = 'indigo';
        }
        applyColorTheme(currentColorTheme.value);

        // Initialize light/dark theme (Default: Light)
        const storedMode = localStorage.getItem(THEME_STORAGE_KEY) as ThemeMode | null;
        currentTheme.value = storedMode || 'light';
        applyThemeMode(currentTheme.value);

        // Listen for OS system theme changes if set to system
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (currentTheme.value === 'system') {
                isDark.value = e.matches;
                if (e.matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
        });
    };

    const setTheme = (mode: ThemeMode) => {
        currentTheme.value = mode;
        if (typeof window !== 'undefined') {
            localStorage.setItem(THEME_STORAGE_KEY, mode);
        }
        applyThemeMode(mode);
    };

    const setColorTheme = (color: ColorTheme) => {
        currentColorTheme.value = color;
        if (typeof window !== 'undefined') {
            localStorage.setItem(COLOR_THEME_STORAGE_KEY, color);
        }
        applyColorTheme(color);
    };

    const toggleDark = () => {
        const nextMode = isDark.value ? 'light' : 'dark';
        setTheme(nextMode);
    };

    return {
        theme: currentTheme,
        colorTheme: currentColorTheme,
        isDark,
        initTheme,
        setTheme,
        setColorTheme,
        toggleDark,
    };
}
