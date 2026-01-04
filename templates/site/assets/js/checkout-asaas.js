/**
 * Checkout Asaas - Com PIX e Cartão
 */
(function () {
    'use strict';

    // Toggle método de pagamento
    window.toggleMetodoPagamento = function () {
        const cartaoSelecionado = document.getElementById('metodo_cartao').checked;
        const camposCartao = document.getElementById('campos-cartao');
        const camposExtras = document.getElementById('campos-extras-cartao');
        const btnTexto = document.getElementById('texto-btn');
        const btnIcon = document.getElementById('icon-btn');

        if (cartaoSelecionado) {
            camposCartao.style.display = 'block';
            camposExtras.style.display = 'block';
            btnTexto.textContent = 'Finalizar Pagamento';
            btnIcon.className = 'fa-solid fa-lock me-2';
            setRequiredFields(true);
        } else {
            camposCartao.style.display = 'none';
            camposExtras.style.display = 'none';
            btnTexto.textContent = 'Gerar PIX e Finalizar';
            btnIcon.className = 'fa-brands fa-pix me-2';
            setRequiredFields(false);
        }
    };

    function setRequiredFields(required) {
        const fields = ['email', 'telefone', 'cartao_nome', 'cartao_numero', 'cartao_mes', 'cartao_ano', 'cartao_cvv', 'cep'];
        fields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) field.required = required;
        });
    }

    function initTimer() {
        let timer = localStorage.getItem('checkout_timer') ? parseInt(localStorage.getItem('checkout_timer')) : 600;
        const display = document.querySelector('#countdown');

        const interval = setInterval(function () {
            const minutes = parseInt(timer / 60, 10);
            const seconds = parseInt(timer % 60, 10);
            const minutesStr = minutes < 10 ? "0" + minutes : minutes;
            const secondsStr = seconds < 10 ? "0" + seconds : seconds;

            if (display) display.textContent = minutesStr + ":" + secondsStr;
            localStorage.setItem('checkout_timer', timer);

            if (--timer < 0) {
                localStorage.removeItem('checkout_timer');
                clearInterval(interval);
                window.location.reload();
            }
        }, 1000);
    }

    function initMasks() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.mask) {
            $('#cpf').mask('000.000.000-00');
            $('#telefone').mask('(00) 00000-0000');
            $('#cartao_numero').mask('0000 0000 0000 0000');
            $('#cartao_cvv').mask('0000');
            $('#cep').mask('00000-000');
        }
    }

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