<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha - ProLink</title>
    <link rel="icon" type="image/x-icon" href="src/imgs/icons/logo-ico.ico">
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
    </style>
</head>
<body>
    <div class="password-reset-container">
        <h1>Recuperação de Senha</h1>
        <div id="message" class="message"></div>
        
        <form method="post" id="requestForm">
            <div class="textfield">
                <label for="email">Email cadastrado</label>
                <input type="email" id="email" name="email" placeholder="Digite seu email" required>
            </div>
            <button type="submit" class="btn-submit" id="btnRequest">Solicitar Redefinição</button>
            <div class="login-link">
                Lembrou sua senha? <a href="index.html">Faça login</a>
            </div>
        </form>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            include("../php/conexao.php"); 
            $pdo = conectar();

            // Verificar se o email existe na tabela Usuario
            $sql = "SELECT email FROM Usuario WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(":email", $email);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado) {
                $_POST['email'] = $email;
                include("../php/recuperarSenha.php"); 
                echo "<script>
                    document.getElementById('message').className = 'message success';
                    document.getElementById('message').style.display = 'block';
                    document.getElementById('message').textContent = 'Mensagem enviada, por favor verifique sua caixa de entrada.';
                    setTimeout(function() {
                        document.getElementById('message').style.display = 'none';
                    }, 5000);
                </script>";
            } else {
                echo "<script>
                    document.getElementById('message').className = 'message error';
                    document.getElementById('message').style.display = 'block';
                    document.getElementById('message').textContent = 'Este email não está cadastrado em nosso sistema.';
                    setTimeout(function() {
                        document.getElementById('message').style.display = 'none';
                    }, 5000);
                </script>";
            }
        } else {
            echo "<script>
                document.getElementById('message').className = 'message error';
                document.getElementById('message').style.display = 'block';
                document.getElementById('message').textContent = 'Por favor, insira um email válido.';
                setTimeout(function() {
                    document.getElementById('message').style.display = 'none';
                }, 5000);
            </script>";
        }
    }
    ?>
</body>
</html>