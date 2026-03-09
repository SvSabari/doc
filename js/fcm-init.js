// Firebase Configuration - REPLACE WITH YOUR ACTUAL CONFIG
const firebaseConfig = {
    apiKey: "YOUR_API_KEY",
    authDomain: "YOUR_AUTH_DOMAIN",
    projectId: "YOUR_PROJECT_ID",
    storageBucket: "YOUR_STORAGE_BUCKET",
    messagingSenderId: "YOUR_SENDER_ID",
    appId: "YOUR_APP_ID"
};

// VAPID KEY - REPLACE WITH YOUR ACTUAL KEY
const PUBLIC_VAPID_KEY = "YOUR_PUBLIC_VAPID_KEY";

// Helper for UI Feedback
function logFCMStatus(msg, isError = false) {
    console.log(`%c[FCM] ${msg}`, isError ? 'color: red; font-weight: bold' : 'color: green; font-weight: bold');
    // We can also update a status element if it exists
    const statusEl = document.getElementById('fcm-status-text');
    if (statusEl) {
        statusEl.innerText = msg;
        statusEl.style.color = isError ? 'red' : 'green';
    }
}

// Check for placeholders
if (firebaseConfig.apiKey === "YOUR_API_KEY") {
    console.group("🚀 Firebase Initialization Needed");
    console.warn("Firebase config is using PLACEHOLDERS. Please replace them in /doc/js/fcm-init.js and /doc/firebase-messaging-sw.js");
    console.info("Go to Firebase Console > Project Settings > Web App to get your config.");
    console.groupEnd();
} else {
    firebase.initializeApp(firebaseConfig);
    const messaging = firebase.messaging();

    function initFCM() {
        logFCMStatus("Checking notification permissions...");
        Notification.requestPermission().then((permission) => {
            if (permission === 'granted') {
                logFCMStatus("Permission granted.");
                getToken();
            } else {
                logFCMStatus("Permission denied. Notifications will not work.", true);
            }
        });
    }

    function getToken() {
        if (PUBLIC_VAPID_KEY === "YOUR_PUBLIC_VAPID_KEY") {
            logFCMStatus("Missing Public VAPID Key. Check Firebase Settings.", true);
            return;
        }

        messaging.getToken({ vapidKey: PUBLIC_VAPID_KEY }).then((currentToken) => {
            if (currentToken) {
                logFCMStatus("Token generated. Refreshing on server...");
                saveTokenToServer(currentToken);
            } else {
                logFCMStatus("No token available. Ensure you are on HTTPS or localhost.", true);
            }
        }).catch((err) => {
            logFCMStatus("Error getting token: " + err.message, true);
        });
    }

    function saveTokenToServer(token) {
        fetch('../api/save_fcm_token.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: token }),
        })
            .then(response => response.json())
            .then(data => {
                logFCMStatus("Registered successfully. Ready for notifications!");
            })
            .catch((error) => {
                logFCMStatus("Failed to save token to server.", true);
            });
    }

    // Handle Foreground Messages
    messaging.onMessage((payload) => {
        console.log('Message received. ', payload);
        const { title, body } = payload.notification;

        // Show a professional toast or alert
        if (window.Notification && Notification.permission === "granted") {
            new Notification(title, { body: body, icon: '/doc/favicon_base.png' });
        } else {
            alert(`🔔 ${title}\n${body}`);
        }
    });

    if (Notification.permission === 'default' || Notification.permission === 'granted') {
        initFCM();
    }
}
