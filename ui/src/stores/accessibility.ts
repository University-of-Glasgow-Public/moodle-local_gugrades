import { defineStore } from 'pinia';
import { reactive, watch } from 'vue';

/**
 * Accessibility profiles ("themes") a user can switch between to help with
 * visual issues while using MyGrades. These are applied to the root <html>
 * element so they affect the entire app regardless of a component's own
 * hardcoded colours.
 */
export type A11yTheme = 'default' | 'contrast' | 'dark' | 'reading';

export interface IA11ySettings {
    /** Overall visual profile / accessibility theme. */
    theme: A11yTheme;
    /** Root font scale multiplier (1 = 100%). Scales all rem based sizing. */
    fontScale: number;
    /** Use a dyslexia friendly font with generous spacing. */
    dyslexiaFont: boolean;
    /** Increase line height for easier reading. */
    lineSpacing: boolean;
    /** Increase letter/word spacing for easier reading. */
    letterSpacing: boolean;
    /** Always underline links so they are easier to spot. */
    highlightLinks: boolean;
    /** Disable animations and transitions. */
    reduceMotion: boolean;
}

const STORAGE_KEY = 'gugrades_a11y';
// Caches the last known "enabled" state from the admin setting so that, on
// subsequent page loads, we can decide whether to apply saved settings up
// front (no flash for enabled users) or do nothing at all (zero footprint
// for disabled users) before the server confirms the current value.
const ENABLED_CACHE_KEY = 'gugrades_a11y_enabled';

export const MIN_FONT_SCALE = 0.9;
export const MAX_FONT_SCALE = 1.6;
export const FONT_SCALE_STEP = 0.1;

const defaults: IA11ySettings = {
    theme: 'default',
    fontScale: 1,
    dyslexiaFont: false,
    lineSpacing: false,
    letterSpacing: false,
    highlightLinks: false,
    reduceMotion: false,
};

/**
 * Optional data-theme hint kept for any leftover semantic colour utilities
 * (base-100, primary, etc.) defined in MyGrades.css. DaisyUI itself has been
 * removed; these values are harmless no-ops for components that don't read them.
 */
const dataThemeFor: Record<A11yTheme, string> = {
    default: 'light',
    contrast: 'light',
    dark: 'light',
    reading: 'light',
};

function clampScale(value: number): number {
    if (Number.isNaN(value)) {
        return 1;
    }
    return Math.min(MAX_FONT_SCALE, Math.max(MIN_FONT_SCALE, Math.round(value * 100) / 100));
}

function loadSettings(): IA11ySettings {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return { ...defaults };
        }
        const parsed = JSON.parse(raw) as Partial<IA11ySettings>;
        return {
            ...defaults,
            ...parsed,
            fontScale: clampScale(Number(parsed.fontScale ?? defaults.fontScale)),
        };
    } catch {
        return { ...defaults };
    }
}

/** True when the user already has MyGrades-specific settings saved. */
export function hasStoredAccessibilitySettings(): boolean {
    try {
        return localStorage.getItem(STORAGE_KEY) !== null;
    } catch {
        return false;
    }
}

/**
 * Hillhead preferences as returned by local_gugrades_get_accessibility_enabled,
 * already mapped into the MyGrades settings shape on the server.
 */
export interface IHillheadMappedSettings {
    hassettings: boolean;
    theme: A11yTheme;
    fontscale: number;
    dyslexiafont: boolean;
    linespacing: boolean;
    letterspacing: boolean;
    highlightlinks: boolean;
    reducemotion: boolean;
}

/**
 * Convert the server-side Hillhead mapping into IA11ySettings.
 * Returns null when Hillhead has nothing set.
 */
export function mapHillheadToSettings(
    hillhead: IHillheadMappedSettings | null | undefined,
): IA11ySettings | null {
    if (!hillhead || !hillhead.hassettings) {
        return null;
    }

    const theme: A11yTheme = (['default', 'contrast', 'dark', 'reading'] as A11yTheme[])
        .includes(hillhead.theme)
        ? hillhead.theme
        : 'default';

    return {
        theme,
        fontScale: clampScale(Number(hillhead.fontscale) || 1),
        dyslexiaFont: !!hillhead.dyslexiafont,
        lineSpacing: !!hillhead.linespacing,
        letterSpacing: !!hillhead.letterspacing,
        highlightLinks: !!hillhead.highlightlinks,
        reduceMotion: !!hillhead.reducemotion,
    };
}

/**
 * Apply the given settings to the document root. Safe to call before the app
 * is mounted so the chosen profile is active immediately (no flash).
 */
export function applySettings(settings: IA11ySettings): void {
    const root = document.documentElement;

    // Keep a light semantic base so the invert-based dark profile darkens
    // everything uniformly (see accessibility.css).
    root.setAttribute('data-theme', dataThemeFor[settings.theme] ?? 'light');

    // Accessibility profile / toggles as classes.
    root.classList.remove(
        'a11y-theme-default',
        'a11y-theme-contrast',
        'a11y-theme-dark',
        'a11y-theme-reading',
    );
    root.classList.add(`a11y-theme-${settings.theme}`);

    root.classList.toggle('a11y-dyslexia', settings.dyslexiaFont);
    root.classList.toggle('a11y-line-spacing', settings.lineSpacing);
    root.classList.toggle('a11y-letter-spacing', settings.letterSpacing);
    root.classList.toggle('a11y-highlight-links', settings.highlightLinks);
    root.classList.toggle('a11y-reduce-motion', settings.reduceMotion);

    // Flag whether the user has engaged ANY accessibility option, so styles
    // that should only kick in "when accessibility options are selected"
    // (e.g. improving low contrast brand-purple text) can target it.
    const isActive =
        settings.theme !== defaults.theme ||
        settings.fontScale !== defaults.fontScale ||
        settings.dyslexiaFont ||
        settings.lineSpacing ||
        settings.letterSpacing ||
        settings.highlightLinks ||
        settings.reduceMotion;
    root.classList.toggle('a11y-active', isActive);

    // Font scaling: 16px is the browser default; scaling it rescales all rem
    // based Tailwind utilities (text, spacing, etc.) across the whole app. At
    // scale 1 we remove the inline style entirely so the DOM is left pristine
    // (important when the tool is disabled/reset).
    if (settings.fontScale === 1) {
        root.style.removeProperty('font-size');
        root.style.removeProperty('--a11y-font-scale');
    } else {
        root.style.fontSize = `${16 * settings.fontScale}px`;
        root.style.setProperty('--a11y-font-scale', String(settings.fontScale));
    }
}

/** Read the cached admin "enabled" state. null means we've never been told. */
function readEnabledCache(): boolean | null {
    try {
        const raw = localStorage.getItem(ENABLED_CACHE_KEY);
        if (raw === null) {
            return null;
        }
        return raw === '1';
    } catch {
        return null;
    }
}

/** Remember the admin "enabled" state for the next page load. */
export function setAccessibilityEnabledCache(enabled: boolean): void {
    try {
        localStorage.setItem(ENABLED_CACHE_KEY, enabled ? '1' : '0');
    } catch {
        // Ignore storage errors.
    }
}

/**
 * Bootstrap the persisted settings onto the document as early as possible so
 * the chosen profile is active before the app renders (no flash).
 *
 * This ONLY applies anything when the tool was known to be enabled on the
 * previous load. If it was disabled (or we've never checked), we apply
 * nothing and wait for the server to confirm — so a disabled tool leaves no
 * footprint on the page at all.
 */
export function bootstrapAccessibility(): void {
    if (readEnabledCache() === true) {
        applySettings(loadSettings());
    }
}

/**
 * Remove all accessibility effects from the document without touching the
 * user's saved preferences. Used when an admin has disabled the tool site
 * wide, so the interface returns to its standard appearance.
 */
export function clearAppliedAccessibility(): void {
    applySettings({ ...defaults });
}

export const useAccessibility = defineStore('accessibility', () => {
    const settings = reactive<IA11ySettings>(loadSettings());

    // Persist and re-apply whenever anything changes. Note: NOT immediate —
    // merely instantiating this store must not touch the document, otherwise
    // the tool would leave a footprint even while disabled. The initial apply
    // is done explicitly via bootstrapAccessibility()/activate() only once the
    // tool is confirmed enabled.
    watch(
        settings,
        (value) => {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(value));
            } catch {
                // Ignore storage errors (e.g. private mode / quota).
            }
            applySettings(value);
        },
        { deep: true },
    );

    /** Apply the user's current saved settings to the document. */
    function activate(): void {
        applySettings(settings);
    }

    /**
     * Seed MyGrades settings from Hillhead when the user has no MyGrades
     * preferences of their own yet. Persists via the watcher.
     */
    function importFromHillhead(hillhead: IHillheadMappedSettings): boolean {
        const mapped = mapHillheadToSettings(hillhead);
        if (!mapped) {
            return false;
        }
        Object.assign(settings, mapped);
        applySettings(settings);
        return true;
    }

    function setTheme(theme: A11yTheme): void {
        settings.theme = theme;
    }

    function increaseFont(): void {
        settings.fontScale = clampScale(settings.fontScale + FONT_SCALE_STEP);
    }

    function decreaseFont(): void {
        settings.fontScale = clampScale(settings.fontScale - FONT_SCALE_STEP);
    }

    function resetFont(): void {
        settings.fontScale = 1;
    }

    function reset(): void {
        Object.assign(settings, defaults);
    }

    return {
        settings,
        activate,
        importFromHillhead,
        setTheme,
        increaseFont,
        decreaseFont,
        resetFont,
        reset,
    };
});
