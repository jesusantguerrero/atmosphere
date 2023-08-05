import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";
import { getAnalytics } from "firebase/analytics";
import CONFIG from "../config";
import { reactive } from "vue";

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
    console.log("Running" , firebase.messaging, firebase.app)
    onMessage(firebase.messaging, (payload) => {
        console.log(payload)
        onMessaged && onMessaged(payload)
    })
}


export const useAnalytics = () => {
   analytics
}
