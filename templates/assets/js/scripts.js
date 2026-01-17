//################# BOOTSTRAP #####################

var tooltipTriggerList = [].slice.call(document.querySelectorAll('[tooltip="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

//################# FIM BOOTSTRAP #################

$(document).ready(function () {

    $('.formularioAjax').submit(function (event) {
        event.preventDefault();

        var carregando = $('.ajaxLoading');
        var botao = $(':input[type="submit"]');
        var formulario = $(this);

        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
            processData: false,
            beforeSend: function () {
                carregando.show().fadeIn(200);
                botao.prop('disabled', true).addClass('disabled');
            },
            success: function (retorno) {

                if (retorno.erro) {
                    alerta(retorno.erro, 'red');
                }

                if (retorno.successo || retorno.sucesso) {
                    formulario[0].reset();
                    $('#contatoModal').modal('hide');
                    alerta(retorno.successo || retorno.sucesso, 'green');
                }

                if (retorno.redirecionar) {
                    setTimeout(function () {
                        window.location.href = retorno.redirecionar;
                    }, 2000);
                }

            },
            complete: function () {
                carregando.hide().fadeOut(200);
                botao.prop('disabled', false).removeClass('disabled');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log('Erro AJAX:', jqXHR, textStatus, errorThrown);
                alerta('Erro ao enviar mensagem. Tente novamente.', 'red');
            }
        });

    });

});


function alerta(mensagem, cor) {
    new jBox('Notice', {
        content: mensagem,
        color: cor,
        animation: 'pulse',
        showCountdown: true,
        autoClose: 5000
    });
}