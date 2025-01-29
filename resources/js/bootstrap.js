//import * as $ from "jquery";
//window.$ = $;
import * as bootstrap from "bootstrap";
window.bootstrap = bootstrap;

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

$(document).on('click', 'a.silent-click', function (e) {
    e.preventDefault(); // Previne o redirecionamento padrão
    const url = $(this).attr('href'); // Obtém a URL do atributo href
    const $link = $(this);
    $link.addClass('disabled').attr('disabled', true).css({
        pointerEvents: 'none', // Evita cliques futuros
        color: 'gray'          // Estilo visual para indicar que está desabilitado
    });
    axios.get(url)
        .then(response => {
        })
        .catch(error => {
        }).finally(()=>{
            $link.removeClass('disabled').removeAttr('disabled').css({
                pointerEvents: '', // Permite cliques novamente
                color: ''          // Remove o estilo visual de desabilitado
            });
        });
});
$(document).on('click', 'a.single-silent-click', function (e) {
    e.preventDefault(); // Previne o redirecionamento padrão
    const url = $(this).attr('href'); // Obtém a URL do atributo href

    $(this).addClass('disabled').attr('disabled', true).css({
        display: 'none'
    });
    axios.get(url)
      .then(response => {
      })
      .catch(error => {
      });
});

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     wsHost: import.meta.env.VITE_PUSHER_HOST ?? `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });

// Re-captcha required
window.addEventListener('load', () => {
    const $recaptcha = document.querySelector('#g-recaptcha-response');
    if ($recaptcha) {
        $recaptcha.setAttribute('required', 'required');
    }
})
/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
