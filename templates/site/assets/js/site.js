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
                        $('#buscaResultado').html("<div class='card'><div class='card-body'><ul class='list-group list-group-flush'>"+resultado+"</ul></div></div>");
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






















document.addEventListener('DOMContentLoaded', (event) => {
    document.querySelectorAll('pre').forEach((el) => {
        hljs.highlightElement(el);
    });
});

