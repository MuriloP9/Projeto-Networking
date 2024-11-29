$('#btnLogin').click(function () {
    var email = $('#email').val();
    var senha = $('#senha').val();

    // Verifica se os campos de email e senha estão preenchidos
    if (email && senha) {
        var formData = new FormData();
        formData.append('email', email);
        formData.append('senha', senha);

        console.log("Enviando Email: " + email); // Depuração: Verificando o email
        console.log("Enviando Senha: " + senha); // Depuração: Verificando a senha

        $.ajax({
            url: 'login.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                console.log("Resposta do servidor: " + response); // Depuração: Verificando a resposta
                if (response.trim() === 'sucesso') {
                    window.location.href = 'index.html'; // Redireciona para a página inicial (home)
                } else {
                    $('#mensagem').text('Email ou senha incorretos.'); // Exibe a mensagem de erro
                }
            },
            error: function () {
                alert('Erro ao processar o login.');
            }
        });
    } else {
        alert('Por favor, preencha todos os campos.');
    }
});
