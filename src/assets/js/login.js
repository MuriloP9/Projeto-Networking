$(document).ready(function () {
    function realizarLogin() {
        const email = $('#email').val();
        const senha = $('#senha').val();

        if (email.trim() === '' || senha.trim() === '') {
            $('#mensagem').text('Por favor, preencha todos os campos!');
            return;
        }

        // Validação de email no frontend
        if (!validateEmail(email)) {
            $('#mensagem').text('Por favor, insira um email válido!');
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

    // Função para validar o formato do email
    function validateEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
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
