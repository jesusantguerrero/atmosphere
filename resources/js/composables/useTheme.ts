const hexToRgb = (hex: string = "") => {
    return hex?.replace?.(/^#?([a-f\d])([a-f\d])([a-f\d])$/i, (m, r, g, b) => '#' + r + r + g + g + b + b)?.substring(1)?.match(/.{2}/g)?.map(x => parseInt(x, 16)).join(' ') ?? ""
}

const setProperty = (colorName: string, colorValue: string) => {
    document.documentElement.style.setProperty(colorName, colorValue)
}

export const setTheme = (defaultTheme: Record<string, string>) => {
    const theme = defaultTheme
    setProperty('--lc-primary', hexToRgb(theme.primary));
    setProperty('--lc-secondary', hexToRgb(theme?.secondary));
    setProperty('--lc-user-default', hexToRgb(theme?.userDefaultColor));
    setProperty('--lc-tertiary', hexToRgb(theme?.tertiary));
    setProperty('--lc-brand-gray-extra-light', hexToRgb(theme?.brandGrayExtralight));
    setProperty('--lc-danger', hexToRgb(theme?.danger));
    setProperty('--lc-primary-dark', hexToRgb(theme?.primaryDark));
    setProperty('--lc-primary-light', hexToRgb(theme?.primaryLight));
    setProperty('--lc-secondary-light', hexToRgb(theme?.secondaryLight));
    setProperty('--lc-secondary-dark-rgb', hexToRgb(theme?.secondaryDark));

    setProperty('--lc-secondary', theme?.primary_rgb);
    setProperty('--lc-user-default-rgb', theme?.userDefaultColor_rgb);
    setProperty('--lc-tertiary-rgb', theme?.tertiary_rgb);
    setProperty('--lc-brand-gray-extra-light-secondary-rgb', theme?.brandGrayExtralightSecondary_rgb);
    setProperty('--lc-brand-dray-extra-light-rgb', theme?.brandGrayExtralight_rgb);
    setProperty('--lc-danger-rgb', theme?.danger_rgb);
    setProperty('--lc-primary-dark-rgb', theme?.primaryDark_rgb);
    setProperty('--lc-primary-light-rgb', theme?.primaryLight_rgb);
    setProperty('--lc-secondary-rgb', theme?.secondary_rgb);
    setProperty('--lc-secondary-dark-rgb', theme?.secondaryDark_rgb);
    setProperty('--lc-secondary-light-rgb', theme?.secondaryLight_rgb);
}
