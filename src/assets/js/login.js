$(document).ready(function () {
    function realizarLogin() {
        const email = $('#email').val();
        const senha = $('#senha').val();

        if (email.trim() === '' || senha.trim() === '') {
            $('#mensagem').text('Por favor, preencha todos os campos!');
            return;
        }

        $('#btnLogin').prop('disabled', true).text('Carregando...'); // Desabilita botão e muda texto

        $.ajax({
            url: '../php/validarLogin.php',
            type: 'POST',
            data: { email: email, senha: senha },
            dataType: 'json',
            success: function (response) {
                if (response.sucesso) {
                    window.location.href = '../php/index.php';
                } else {
                    $('#mensagem').text(response.mensagem);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição:', error);
                $('#mensagem').text('Erro na comunicação com o servidor.');
            },
            complete: function () {
                // Reabilita o botão depois da requisição, independentemente do resultado
                $('#btnLogin').prop('disabled', false).text('Login');
            }
        });
    }

    // Clique no botão
    $('#btnLogin').on('click', function () {
        realizarLogin();
    });

    // Pressionar Enter no input
    $('#form-login input').on('keypress', function (e) {
        if (e.which === 13) { // Tecla Enter
            realizarLogin();
        }
    });
});
