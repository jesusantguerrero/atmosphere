const colors = require("tailwindcss/colors");
const customColors = {
    rhino: { DEFAULT: '#2c395b', 50: '#f4f5f7', 100: '#eaebef', 200: '#caced6', 300: '#abb0bd', 400: '#6b748c', 500: '#2c395b', 600: '#283352', 700: '#212b44', 800: '#1a2237', 900: '#161c2d' },
    mauvelous: { DEFAULT: '#F37EA1', 50: '#FFFFFF', 100: '#FFFFFF', 200: '#FDEEF3', 300: '#FAC9D7', 400: '#F6A3BC', 500: '#F37EA1', 600: '#EE4B7C', 700: '#E91756', 800: '#B81143', 900: '#840C30', 950: '#6B0A27' },
};

module.exports = {
    defaultDark: {
        primary: "#F2617A",
        "secondary": "#95b3f9",
        "accent": "#7c5bbf",
        "neutral": "#232130",
        "base-deep-1": "#202631",
        base: "#0B0E13",
        "base-lvl-1": "#0F131A",
        "base-lvl-2": "#14181F",
        "base-lvl-3": "#191E27",
        info: "#3D68F5",
        success: "#56C08A",
        warning: "#D39E17",
        error: "#EF6B67",
        "body": "#E7EAF0",
        "body-1": "#9BA4B2"
    },
    defaultLight: {
        primary: "#F37EA1",
        primaryDark: customColors.mauvelous[800],
        secondary: "#7B77D1",
        "accent": "#f782c2",
        "neutral": "#F3F4F6",
        "base-deep-1": colors.slate[400],
        base: "#F3F4F6",
        "base-lvl-1": colors.slate[100],
        "base-lvl-2": colors.slate[50],
        "base-lvl-3": colors.white,
        info: "#3D68F5",
        success: "#00C875",
        warning: "#D39E17",
        error: "#F61909",
        "body": colors.gray[900],
        "body-1": colors.gray[700]
    },
    pinkLight: {
        primary: "#d527b7", // "#3a4a73"
        secondary: "#8a00d4",
        "accent": "#f782c2",
        "neutral": "#F3F4F6",
        "base-deep-1": colors.slate[400],
        base: "#F3F4F6",
        "base-lvl-1": colors.slate[100],
        "base-lvl-2": colors.slate[50],
        "base-lvl-3": colors.white,
        info: "#3D68F5",
        success: "#00C875",
        warning: "#D39E17",
        error: "#F61909",
        "body": colors.gray[900],
        "body-1": colors.gray[700]
    },
    blueLight: {
        primary: "#1f436e",
        "secondary": "#162f4d",
        "accent": "#a3cdff",
        "neutral": "#d1e6ff",
        "base-deep-1": colors.slate[400],
        base: "#e3e3e3",
        "base-lvl-1": colors.slate[100],
        "base-lvl-2": colors.slate[50],
        "base-lvl-3": colors.white,
        info: "#3D68F5",
        success: "#79E7AE",
        warning: "#D39E17",
        error: "#F61909",
        "body": "#2E384D",
        "body-1": "#9298AD"
    }
}


// --- Runtime theming helpers (light/dark via CSS variables) ---
const __hexToRgb = (input) => {
    let hex = String(input == null ? '' : input).trim();
    if (hex === 'white') hex = '#ffffff';
    if (hex === 'black') hex = '#000000';
    hex = hex.replace('#', '');
    if (hex.length === 3) hex = hex.split('').map((ch) => ch + ch).join('');
    const r = parseInt(hex.slice(0, 2), 16);
    const g = parseInt(hex.slice(2, 4), 16);
    const b = parseInt(hex.slice(4, 6), 16);
    return [r, g, b].every((n) => Number.isFinite(n)) ? (r + ' ' + g + ' ' + b) : null;
};
// Map each token to a CSS-var-backed color (keeps alpha utilities working).
// Non-hex values fall back to their literal value so nothing breaks.
module.exports.cssVarColors = (theme) =>
    Object.fromEntries(Object.keys(theme).map((k) =>
        [k, __hexToRgb(theme[k]) ? ('rgb(var(--c-' + k + ') / <alpha-value>)') : theme[k]]));
// Produce { '--c-token': 'R G B' } for :root / .dark injection.
module.exports.themeVars = (theme) =>
    Object.fromEntries(Object.entries(theme)
        .map(([k, v]) => ['--c-' + k, __hexToRgb(v)])
        .filter(([, v]) => v));
