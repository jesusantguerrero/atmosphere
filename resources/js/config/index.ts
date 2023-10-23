
interface AppConfig {
    FIREBASE_API_KEY: string;
    FIREBASE_PROJECT_ID: string;
    FIREBASE_APP_ID: string;
    FIREBASE_SENDER_ID: string;
    PUSH_PK: string;
    MEASUREMENT_ID: string;
    GOOGLE_APP_KEY: string;
    GOOGLE_APP_CLIENT: string;
    FIREBASE_VAPID_KEY: string;
    IS_DEMO: boolean;
}

const isDemo = import.meta.env?.VITE_APP_DEMO

export const config: AppConfig = {
    FIREBASE_API_KEY: import.meta.env.VITE_FIREBASE_APP_KEY,
    FIREBASE_PROJECT_ID: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    FIREBASE_APP_ID: import.meta.env.VITE_FIREBASE_APP_ID,
    FIREBASE_SENDER_ID: import.meta.env.VITE_FIREBASE_SENDER_ID,
    PUSH_PK: import.meta.env.VITE_PUSH_PK,
    MEASUREMENT_ID: import.meta.env.VITE_MEASUREMENT_ID,
    GOOGLE_APP_KEY: import.meta.env.VITE_GOOGLE_APP_KEY,
    GOOGLE_APP_CLIENT: import.meta.env.VITE_GOOGLE_CLIENT_ID,
    FIREBASE_VAPID_KEY: import.meta.env.VITE_FIREBASE_VAPID_KEY,
    IS_DEMO: Boolean(isDemo) && isDemo !== 'false'
}
