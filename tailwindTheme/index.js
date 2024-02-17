import colors from "tailwindcss/colors";

const customColors   = {  'rhino': {
    DEFAULT: '#2c395b',
    '50': '#f4f5f7',
    '100': '#eaebef',
    '200': '#caced6',
    '300': '#abb0bd',
    '400': '#6b748c',
    '500': '#2c395b',
    '600': '#283352',
    '700': '#212b44',
    '800': '#1a2237',
    '900': '#161c2d'
},
mauvelous: {
    DEFAULT: '#F37EA1',
    50: '#FFFFFF',
    100: '#FFFFFF',
    200: '#FDEEF3',
    300: '#FAC9D7',
    400: '#F6A3BC',
    500: '#F37EA1',
    600: '#EE4B7C',
    700: '#E91756',
    800: '#B81143',
    900: '#840C30',
    950: '#6B0A27'
  },
}

export const theme = {
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
