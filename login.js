$(document).ready(function() {
    $('#btnLogin').on('click', function() {
        const email = $('#email').val();
        const senha = $('#senha').val();

        if (email.trim() === '' || senha.trim() === '') {
            $('#mensagem').text('Por favor, preencha todos os campos!');
            return;
        }

        $.ajax({
            url: 'validarLogin.php',
            type: 'POST',
            data: { email: email, senha: senha },
            dataType: 'json',
            success: function(response) {
                if (response.sucesso) {
                    window.location.href = 'index.html'; // Redirecione para a página inicial após login bem-sucedido
                } else {
                    $('#mensagem').text(response.mensagem); // Exibe mensagem de erro
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição:', error);
                $('#mensagem').text('Erro na comunicação com o servidor.');
            }
        });
    });
});
