$(document).ready(function () {

    //    BUSCA
    $("#busca").keyup(function () {
        var busca = $(this).val();
        if (busca.length > 0) {
            $.ajax({
                url: $('form').attr('data-url-busca'),
                method: 'POST',
                data: {
                    busca: busca
                },
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
    //    FIM BUSCA




});

document.addEventListener("DOMContentLoaded", function () {
    const selects = document.querySelectorAll('.qtd-select');
    const totalDisplay = document.getElementById('totalDisplay');

    function calcularTotal() {
        let totalGeral = 0;

        selects.forEach(select => {
            const quantidade = parseInt(select.value);
            const card = select.closest('.pacote-card');

            if (quantidade > 0) {
                const preco = parseFloat(select.getAttribute('data-preco'));
                const taxa = parseFloat(select.getAttribute('data-taxa'));
                totalGeral += (preco + taxa) * quantidade;

                card.classList.add('ativo');
            } else {
                card.classList.remove('ativo');
            }
        });

        totalDisplay.innerText = totalGeral.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    selects.forEach(select => {
        select.addEventListener('change', calcularTotal);
    });

    // Garante que o total inicial seja calculado ao carregar a página (caso tenha valor pré-selecionado)
    calcularTotal();
});


function copiarPix() {
    var copyText = document.getElementById("pixCopiaCola");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    alert("Código PIX copiado!");
}






















document.addEventListener('DOMContentLoaded', (event) => {
    document.querySelectorAll('pre').forEach((el) => {
        hljs.highlightElement(el);
    });
});

