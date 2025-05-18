import * as bootstrap from "bootstrap";
import "bootstrap/js/dist/modal";
window.bootstrap = bootstrap;

// Re-captcha required
window.addEventListener("load", () => {
    const $recaptcha = document.querySelector("#g-recaptcha-response");
    if ($recaptcha) {
        $recaptcha.setAttribute("required", "required");
    }
});

import Echo from "laravel-echo";

import Pusher from "pusher-js";
window.Pusher = Pusher;

if (!window.env.LIVEWIRE) {
    console.log("declara livewire");
    window.Echo = new Echo({
        broadcaster: "reverb",
        key: window.env.REVERB_APP_KEY,
        wsHost: window.env.REVERB_HOST,
        wsPort: window.env.REVERB_PORT ?? 80,
        wssPort: window.env.REVERB_PORT ?? 443,
        wsPath: window.env.REVERB_PATH ?? "/",
        forceTLS: (window.env.REVERB_SCHEME ?? "https") === "https",
        enabledTransports: ["ws", "wss"],
    });
}
