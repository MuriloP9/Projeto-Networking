<?php
session_start();

$error_message = ''; // Inicializa a variável de erro

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtendo dados via POST
    $username = $_POST['usuario']; 
    $password = $_POST['senha']; 

    // Implementação B.D
    $valid_username = 'admin';
    $valid_password = '123';

    if ($username === $valid_username && $password === $valid_password) {
        // Login bem-sucedido
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        header('Location: index.html'); // Redirecionar para a página de sucesso
        exit;
    } else {
        // Login falhou
        $error_message = "Usuário ou senha inválidos!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;400;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="loginpage.css"> <!-- Certifique-se que este arquivo está no mesmo diretório -->
    <title>Login</title>
</head>
<body>
    <div class="main-login">
        <div class="left-login">
            <h1>Faça o Login<br>E entre para o nosso time</h1>
            <img src="./img/networking.svg" class="left-login-image" alt="networking">
        </div>
        <div class="right-login">
            <div class="card-login">
                <h1>LOGIN</h1>
                <?php if ($error_message): ?>
                    <p style="color: red;"><?php echo $error_message; ?></p>
                <?php endif; ?>
                <form method="post" action="">
                    <div class="textfield">
                        <label for="usuario">Usuário</label>
                        <input type="text" name="usuario" placeholder="Usuário" required>
                    </div>
                    <div class="textfield">
                        <br>
                        <label for="senha">Senha</label>
                        <input type="password" name="senha" placeholder="Digite sua Senha" required>
                    </div>
                    <button type="submit" class="btn-login">Login</button>
                </form>
            </div>
        </div>
    </div>
    <div>
        <ul class="voltar">
            <li><a href="index.html">Voltar</a></li>
        </ul>
    </div>
</body>
</html>
