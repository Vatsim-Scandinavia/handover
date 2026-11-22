/**
 * Theme switching between system, light and dark.
 *
 * The preference lives in this browser's localStorage only. Without one, or
 * when storage is blocked, the theme follows the system setting. The inline
 * script in layouts/theme-head.blade.php applies it before the page paints.
 */

type Preference = 'system' | 'light' | 'dark';
type Theme = Exclude<Preference, 'system'>;

const STORAGE_KEY = 'theme_preference';

// Order is the toggle's click cycle: system -> light -> dark.
const PREFERENCES = {
    system: { icon: 'fa-desktop', label: 'System' },
    light: { icon: 'fa-sun', label: 'Light' },
    dark: { icon: 'fa-moon', label: 'Dark' },
} as const satisfies Record<Preference, { icon: string; label: string }>;
const ORDER = Object.keys(PREFERENCES) as Preference[];
const ICONS = ORDER.map((preference) => PREFERENCES[preference].icon);

const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');

function isPreference(value: string | null): value is Preference {
    return value !== null && Object.hasOwn(PREFERENCES, value);
}

function readPreference(): Preference {
    let stored: string | null = null;
    try {
        stored = localStorage.getItem(STORAGE_KEY);
    } catch { /* storage blocked */ }

    return isPreference(stored) ? stored : 'system';
}

// Kept in memory too, so the toggle still cycles when storage is blocked.
let current = readPreference();

function storePreference(preference: Preference): void {
    try {
        if (preference === 'system') {
            localStorage.removeItem(STORAGE_KEY);
        } else {
            localStorage.setItem(STORAGE_KEY, preference);
        }
    } catch { /* the theme still applies to this page */ }
}

function apply(preference: Preference): void {
    const theme: Theme = preference === 'system' ? (darkQuery.matches ? 'dark' : 'light') : preference;

    // data-bs-theme mirrors data-theme so Bootstrap's dark cascade applies.
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.setAttribute('data-bs-theme', theme);

    const toggle = document.getElementById('theme-toggle');
    if (toggle) {
        const { icon, label } = PREFERENCES[preference];
        const iconElement = toggle.querySelector('[data-theme-icon]');
        iconElement?.classList.remove(...ICONS);
        iconElement?.classList.add(icon);
        toggle.title = `Theme: ${label} (click to change)`;
    }
}

function setup(): void {
    apply(current);

    document.getElementById('theme-toggle')?.addEventListener('click', () => {
        current = ORDER[(ORDER.indexOf(current) + 1) % ORDER.length];
        storePreference(current);
        apply(current);
    });

    darkQuery.addEventListener('change', () => apply(current));

    // Follow a change made in another tab.
    window.addEventListener('storage', (e) => {
        if (e.key === STORAGE_KEY || e.key === null) {
            current = readPreference();
            apply(current);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setup);
} else {
    setup();
}
