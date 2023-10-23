import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";
import { getAnalytics } from "firebase/analytics";
import { config } from "../config";
import { reactive } from "vue";

const firebaseConfig = {
    apiKey: config.FIREBASE_API_KEY,
    authDomain: `${config.FIREBASE_PROJECT_ID}.firebaseapp.com`,
    databaseURL: `https://${config.FIREBASE_PROJECT_ID}.firebaseio.com`,
    projectId: config.FIREBASE_PROJECT_ID,
    storageBucket: `${config.FIREBASE_PROJECT_ID}.appspot.com`,
    messagingSenderId: config.FIREBASE_SENDER_ID,
    measurementId: config.MEASUREMENT_ID,
    appId: config.FIREBASE_APP_ID,
}

const firebase = reactive({
    app: null,
    messaging: null,
    analytics: null
})


const init = () => {
    try {
       firebase.app = initializeApp(firebaseConfig)
        firebase.messaging = getMessaging(firebase.app)
        firebase.analytics = getAnalytics(firebase.app);
    } catch (e) {

    }
}
function requestPermission() {
    console.log('Requesting permission...');
    Notification.requestPermission().then((permission) => {
      if (permission === 'granted') {
        console.log('Notification permission granted.');
    }})
}

export const useMessaging = (onTokenGenerated) => {
    getToken(firebase.messaging, { vapidKey: CONFIG.FIREBASE_VAPID_KEY })
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

    return firebase.messaging;
}

export const useOnMessage = (onMessaged) => {
    firebase.messaging && onMessage(firebase.messaging, (payload) => {
        onMessaged && onMessaged(payload)
    })
}


export const useAnalytics = () => {
   analytics
}
