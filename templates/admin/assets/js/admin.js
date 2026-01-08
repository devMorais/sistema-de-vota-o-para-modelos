$(document).ready(function () {
    const selectGateway = document.getElementById('gateway_pagamento');
    const avisoAsaas = document.getElementById('aviso-asaas');

    if (selectGateway && avisoAsaas) {
        function checkAsaas() {
            avisoAsaas.style.display = (selectGateway.value === 'ASAAS') ? 'block' : 'none';
        }

        selectGateway.addEventListener('change', checkAsaas);
        checkAsaas();
    }
});