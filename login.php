<?php
session_start();

// Variável para mensagem de erro
$mensagem_erro = '';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recebe os dados do formulário
    $usuario = $_POST['usuario'];
    $senha = $_POST['senha'];

    // Usuário e senha válidos (futuramente virão do B.D)
    if ($usuario == 'admin' && $senha == '123') {
        // Login bem-sucedido
        $_SESSION['logado'] = true;
        $_SESSION['usuario'] = $usuario;
        header('Location: index.html'); // Redireciona para a página principal
        exit;
    } else {
        // Mensagem de erro para login inválido
        $mensagem_erro = "Usuário ou senha inválidos!";
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
                <?php if ($mensagem_erro): ?>
                    <p style="color: red;"><?php echo $mensagem_erro; ?></p>
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