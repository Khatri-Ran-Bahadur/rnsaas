import { ref, watch, onMounted } from 'vue';

export type Theme = 'light' | 'dark' | 'system';

const THEME_STORAGE_KEY = 'sathisaas_theme_preference';

const currentTheme = ref<Theme>('light');
const isDark = ref(false);

const applyTheme = (theme: Theme) => {
    let dark = false;
    if (theme === 'dark') {
        dark = true;
    } else if (theme === 'light') {
        dark = false;
    } else if (theme === 'system') {
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

        const stored = localStorage.getItem(THEME_STORAGE_KEY) as Theme | null;
        // Default is light theme as requested
        currentTheme.value = stored || 'light';
        applyTheme(currentTheme.value);

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

    const setTheme = (theme: Theme) => {
        currentTheme.value = theme;
        if (typeof window !== 'undefined') {
            localStorage.setItem(THEME_STORAGE_KEY, theme);
        }
        applyTheme(theme);
    };

    const toggleDark = () => {
        const nextTheme = isDark.value ? 'light' : 'dark';
        setTheme(nextTheme);
    };

    return {
        theme: currentTheme,
        isDark,
        initTheme,
        setTheme,
        toggleDark,
    };
}
