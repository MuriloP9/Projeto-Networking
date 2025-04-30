<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - ProLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;400;900&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            background: #201b2c;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .password-reset-container {
            width: 90%;
            max-width: 500px;
            background: #2f2841;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0px 10px 40px #00000056;
            text-align: center;
        }
        
        .password-reset-container h1 {
            color: hsl(187, 76%, 53%);
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        
        .textfield {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .textfield label {
            display: block;
            color: #f0ffffde;
            margin-bottom: 10px;
            text-align: left;
            font-size: 1rem;
        }
        
        .textfield input {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 15px;
            background: #514869;
            color: #f0ffffde;
            font-size: 1rem;
            outline: none;
            box-shadow: 0px 10px 40px #00000056;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px 0px;
            margin-top: 20px;
            border: none;
            border-radius: 8px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 2px;
            color: #2b124b;
            background: hsl(187, 76%, 53%);
            cursor: pointer;
            box-shadow: 0px 10px 40px -12px #17d1d452;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0px 12px 45px -10px #17d1d452;
        }
        
        .login-link {
            margin-top: 20px;
            color: #f0ffff94;
            font-size: 0.9rem;
        }
        
        .login-link a {
            color: hsl(187, 76%, 53%);
            text-decoration: none;
        }
        
        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
            display: none;
        }
        
        .success {
            background-color: #4CAF50;
            color: white;
        }
        
        .error {
            background-color: #f44336;
            color: white;
        }
        
        .token-invalid {
            text-align: center;
            color: #f44336;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="password-reset-container">
        <h1>Redefinir Senha</h1>
        <div id="message" class="message"></div>
        
        <div id="resetForm">
            <div class="textfield">
                <label for="nova_senha">Nova Senha</label>
                <input type="password" id="nova_senha" name="nova_senha" placeholder="Digite sua nova senha" required>
            </div>
            <div class="textfield">
                <label for="confirmar_senha">Confirmar Nova Senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Confirme sua nova senha" required>
            </div>
            <input type="hidden" id="token" name="token" value="<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>">
            <button type="button" class="btn-submit" id="btnReset">Redefinir Senha</button>
            <div class="login-link">
                <a href="../pages/login.html">Voltar para o login</a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#btnReset').click(function() {
                var novaSenha = $('#nova_senha').val();
                var confirmarSenha = $('#confirmar_senha').val();
                var token = $('#token').val();
                
                if (!novaSenha || !confirmarSenha) {
                    showMessage('Por favor, preencha todos os campos', 'error');
                    return;
                }
                
                if (novaSenha !== confirmarSenha) {
                    showMessage('As senhas não coincidem', 'error');
                    return;
                }
                
                if (novaSenha.length < 6) {
                    showMessage('A senha deve ter pelo menos 6 caracteres', 'error');
                    return;
                }
                
                $.ajax({
                    url: '../php/processar-redefinicao.php',
                    type: 'POST',
                    data: { 
                        token: token,
                        nova_senha: novaSenha,
                        confirmar_senha: confirmarSenha
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.sucesso) {
                            showMessage(response.mensagem, 'success');
                            $('#resetForm').fadeOut();
                        } else {
                            showMessage(response.mensagem, 'error');
                        }
                    },
                    error: function() {
                        showMessage('Erro ao processar sua solicitação. Tente novamente.', 'error');
                    }
                });
            });
            
            function showMessage(msg, type) {
                var messageDiv = $('#message');
                messageDiv.removeClass('success error').addClass(type).text(msg).fadeIn();
                setTimeout(function() {
                    messageDiv.fadeOut();
                }, 5000);
            }
        });
    </script>
</body>
</html>