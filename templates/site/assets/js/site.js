$(document).ready(function () {

    // -------------------------------------------------------------------------
    // 1. BUSCA AJAX
    // -------------------------------------------------------------------------
    if ($("#busca").length) {
        $("#busca").keyup(function () {
            var busca = $(this).val();
            var urlBusca = $('form').attr('data-url-busca');

            if (busca.length > 0 && urlBusca) {
                $.ajax({
                    url: urlBusca,
                    method: 'POST',
                    data: { busca: busca },
                    success: function (resultado) {
                        if (resultado) {
                            $('#buscaResultado').html("<div class='card'><div class='card-body'><ul class='list-group list-group-flush'>" + resultado + "</ul></div></div>");
                        } else {
                            $('#buscaResultado').html('<div class="alert alert-warning">Nenhum resultado encontrado!</div>');
                        }
                    }
                });
                $('#buscaResultado').show();
            } else {
                $('#buscaResultado').hide();
            }
        });
    }
});

// -------------------------------------------------------------------------
// 2. FUNÇÕES AO CARREGAR A PÁGINA (DOM)
// -------------------------------------------------------------------------
document.addEventListener("DOMContentLoaded", function () {

    // A. CALCULADORA DO CHECKOUT
    // ---------------------------------------------------------------------
    const selects = document.querySelectorAll('.qtd-select');
    const totalDisplay = document.getElementById('totalDisplay');

    if (totalDisplay) {
        function calcularTotal() {
            let totalGeral = 0;

            selects.forEach(select => {
                const quantidade = parseInt(select.value);
                const card = select.closest('.pacote-card');

                if (quantidade > 0) {
                    const preco = parseFloat(select.getAttribute('data-preco') || 0);
                    const taxa = parseFloat(select.getAttribute('data-taxa') || 0);
                    totalGeral += (preco + taxa) * quantidade;

                    if (card) card.classList.add('ativo');
                } else {
                    if (card) card.classList.remove('ativo');
                }
            });

            totalDisplay.innerText = totalGeral.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        selects.forEach(select => {
            select.addEventListener('change', calcularTotal);
        });

        calcularTotal();
    }

    // B. HIGHLIGHT JS (Colorir códigos)
    // ---------------------------------------------------------------------
    if (typeof hljs !== 'undefined') {
        document.querySelectorAll('pre').forEach((el) => {
            hljs.highlightElement(el);
        });
    }
});

// -------------------------------------------------------------------------
// 3. FUNÇÃO DE COPIAR PIX (GLOBAL)
// -------------------------------------------------------------------------
function copiarPix() {
    var copyText = document.getElementById("pixCopiaCola");

    if (copyText) {
        copyText.select();
        copyText.setSelectionRange(0, 99999); // Mobile

        if (navigator.clipboard) {
            navigator.clipboard.writeText(copyText.value).then(mostrarNotificacaoCopia);
        } else {
            document.execCommand("copy");
            mostrarNotificacaoCopia();
        }
    }
}

function mostrarNotificacaoCopia() {
    if (typeof jBox !== 'undefined') {
        new jBox('Notice', {
            content: 'Código PIX copiado!',
            color: 'green',
            attributes: {
                x: 'right',
                y: 'bottom'
            }
        });
    } else {
        alert("Código PIX copiado!");
    }
}