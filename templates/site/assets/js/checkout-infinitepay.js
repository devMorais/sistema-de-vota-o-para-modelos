/**
 * Checkout InfinitePay - Versão Simplificada
 */
(function () {
    'use strict';

    // Timer do checkout
    function initTimer() {
        let timer = localStorage.getItem('checkout_timer')
            ? parseInt(localStorage.getItem('checkout_timer'))
            : 600;

        const display = document.querySelector('#countdown');

        const interval = setInterval(function () {
            const minutes = parseInt(timer / 60, 10);
            const seconds = parseInt(timer % 60, 10);
            const minutesStr = minutes < 10 ? "0" + minutes : minutes;
            const secondsStr = seconds < 10 ? "0" + seconds : seconds;

            if (display) {
                display.textContent = minutesStr + ":" + secondsStr;
            }

            localStorage.setItem('checkout_timer', timer);

            if (--timer < 0) {
                localStorage.removeItem('checkout_timer');
                clearInterval(interval);
                window.location.reload();
            }
        }, 1000);
    }

    // Máscaras de input
    function initMasks() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.mask) {
            $('#cpf').mask('000.000.000-00');
            $('#telefone_infinitepay').mask('(00) 00000-0000');
        }
    }

    // Inicializa
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initTimer();
            initMasks();
        });
    } else {
        initTimer();
        initMasks();
    }

})();