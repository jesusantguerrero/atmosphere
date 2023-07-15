import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";
import { getAnalytics } from "firebase/analytics";
import CONFIG from "../config";

const firebaseConfig = {
    apiKey: CONFIG.FIREBASE_API_KEY,
    authDomain: `${CONFIG.FIREBASE_PROJECT_ID}.firebaseapp.com`,
    databaseURL: `https://${CONFIG.FIREBASE_PROJECT_ID}.firebaseio.com`,
    projectId: CONFIG.FIREBASE_PROJECT_ID,
    storageBucket: `${CONFIG.FIREBASE_PROJECT_ID}.appspot.com`,
    messagingSenderId: CONFIG.FIREBASE_SENDER_ID,
    measurementId: CONFIG.MEASUREMENT_ID,
    appId: CONFIG.FIREBASE_APP_ID,
}

const app = initializeApp(firebaseConfig)
const messaging = getMessaging(app)
const analytics = getAnalytics(app);

function requestPermission() {
    console.log('Requesting permission...');
    Notification.requestPermission().then((permission) => {
      if (permission === 'granted') {
        console.log('Notification permission granted.');
    }})
}

export const useMessaging = (onTokenGenerated) => {
    getToken(messaging, { vapidKey: CONFIG.FIREBASE_VAPID_KEY })
    .then((currentToken) => {
        if (currentToken) {
            // Send the token to your server and update the UI if necessary
            onTokenGenerated && onTokenGenerated(currentToken)
        } else {
            requestPermission()
        }
    }).catch((err) => {
        console.log('An error occurred while retrieving token. ', err);
        // ...
    });

    return messaging;
}

export const useOnMessage = (onMessaged) => {
    console.log("Running" , messaging, app)
    onMessage(messaging, (payload) => {
        console.log(payload)
        onMessaged && onMessaged(payload)
    })
}


export const useAnalytics = () => {
   analytics
}
