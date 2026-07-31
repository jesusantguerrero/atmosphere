import { ref, watch } from "vue";

const STORAGE_KEY = "loger-theme";

const getInitial = (): boolean => {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) return saved === "dark";
        return window.matchMedia?.("(prefers-color-scheme: dark)").matches ?? false;
    } catch (e) {
        return false;
    }
};

const isDark = ref(getInitial());

const apply = (value: boolean) => {
    document.documentElement.classList.toggle("dark", value);
};

// Apply immediately on first import so the theme is set before render.
apply(isDark.value);

watch(isDark, (value) => {
    apply(value);
    try { localStorage.setItem(STORAGE_KEY, value ? "dark" : "light"); } catch (e) {}
});

export const useDarkMode = () => ({
    isDark,
    toggle: () => { isDark.value = !isDark.value; },
    setDark: (v: boolean) => { isDark.value = v; },
});
