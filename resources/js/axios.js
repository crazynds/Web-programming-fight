/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

$(document).on("click", "a.silent-click", function (e) {
    e.preventDefault(); // Previne o redirecionamento padrão
    const url = $(this).attr("href"); // Obtém a URL do atributo href
    const $link = $(this);
    $link.addClass("disabled").attr("disabled", true).css({
        pointerEvents: "none", // Evita cliques futuros
        color: "gray", // Estilo visual para indicar que está desabilitado
    });
    axios
        .get(url)
        .then((response) => {})
        .catch((error) => {})
        .finally(() => {
            $link.removeClass("disabled").removeAttr("disabled").css({
                pointerEvents: "", // Permite cliques novamente
                color: "", // Remove o estilo visual de desabilitado
            });
        });
});
$(document).on("click", "a.single-silent-click", function (e) {
    e.preventDefault(); // Previne o redirecionamento padrão
    const url = $(this).attr("href"); // Obtém a URL do atributo href

    $(this).addClass("disabled").attr("disabled", true).css({
        display: "none",
    });
    axios
        .get(url)
        .then((response) => {})
        .catch((error) => {});
});
