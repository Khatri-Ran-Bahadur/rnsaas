import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    LineElement,
    PointElement,
    ArcElement,
    CategoryScale,
    LinearScale,
    Filler,
} from 'chart.js';

let registered = false;

export function ensureChartRegistered() {
    if (!registered) {
        ChartJS.register(
            Title,
            Tooltip,
            Legend,
            BarElement,
            LineElement,
            PointElement,
            ArcElement,
            CategoryScale,
            LinearScale,
            Filler
        );
        registered = true;
    }
}

export const CHART_PALETTE = [
    '#4f46e5', // indigo-600
    '#06b6d4', // cyan-500
    '#10b981', // emerald-500
    '#f59e0b', // amber-500
    '#ec4899', // pink-500
    '#8b5cf6', // violet-500
    '#3b82f6', // blue-500
    '#14b8a6', // teal-500
    '#f97316', // orange-500
    '#64748b', // slate-500
];

export function getChartTheme() {
    const isDark = typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
    return {
        isDark,
        textColor: isDark ? '#94a3b8' : '#64748b',
        gridColor: isDark ? 'rgba(51, 65, 85, 0.35)' : 'rgba(226, 232, 240, 0.7)',
        tooltipBg: isDark ? '#0f172a' : '#1e293b',
        tooltipText: '#ffffff',
        tooltipBorder: isDark ? '#334155' : '#475569',
    };
}
