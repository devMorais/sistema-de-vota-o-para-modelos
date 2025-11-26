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
                            $('#buscaResultado').html(resultado);
                            $('#buscaResultado').fadeIn(200); // Efeito suave
                        } else {
                            $('#buscaResultado').fadeOut(200);
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
            let subtotalProdutos = 0;
            let taxaUnica = 0;
            let temItemSelecionado = false;

            selects.forEach(select => {
                const quantidade = parseInt(select.value);
                const card = select.closest('.pacote-card');

                if (quantidade > 0) {
                    const preco = parseFloat(select.getAttribute('data-preco') || 0);
                    const taxaItem = parseFloat(select.getAttribute('data-taxa') || 0);

                    subtotalProdutos += (preco * quantidade);

                    if (taxaItem > taxaUnica) {
                        taxaUnica = taxaItem;
                    }

                    temItemSelecionado = true;
                    if (card) card.classList.add('ativo');
                } else {
                    if (card) card.classList.remove('ativo');
                }
            });

            let totalGeral = temItemSelecionado ? (subtotalProdutos + taxaUnica) : 0;

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