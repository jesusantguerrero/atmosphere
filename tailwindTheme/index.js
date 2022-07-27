const colors = require("tailwindcss/colors");
const customColors = require('./colors');

module.exports = {
    defaultDark: {
        primary: colors.pink[500],
        "secondary": "#95b3f9",
        "accent": "#7c5bbf",
        "neutral": "#232130",
        "base-deep-1": customColors.rhino[800],
        base: customColors.rhino[900],
        "base-lvl-1": customColors.rhino[800],
        "base-lvl-2": customColors.rhino[700],
        "base-lvl-3": customColors.rhino[600],
        info: "#3D68F5",
        success: "#79E7AE",
        warning: "#D39E17",
        error: "#F61909",
        "body": "white",
        "body-1": colors.slate[200]
    },
    defaultLight: {
        primary: "#d527b7",
        secondary: "#8a00d4",
        "accent": "#f782c2",
        "neutral": "#e3e3e3",
        "base-deep-1": colors.slate[400],
        base: "#e3e3e3",
        "base-lvl-1": colors.slate[100],
        "base-lvl-2": colors.slate[50],
        "base-lvl-3": colors.white,
        info: "#3D68F5",
        success: "#79E7AE",
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
