<?php
require_once 'conexao.php';

$token = $_GET['token'] ?? '';
$erro = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token) {
    try {
        $pdo = conectar();
        
        // Verificar token
        $stmt = $pdo->prepare("SELECT id_usuario FROM tokens_redefinicao WHERE token = ? AND expiracao > GETDATE()");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tokenData) {
            throw new Exception('Token inválido ou expirado.');
        }
        
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';
        
        if (empty($novaSenha) {
            throw new Exception('A nova senha é obrigatória.');
        }
        
        if ($novaSenha !== $confirmarSenha) {
            throw new Exception('As senhas não coincidem.');
        }
        
        // Atualizar senha
        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE Usuario SET senha = ? WHERE id_usuario = ?");
        $stmt->execute([$senhaHash, $tokenData['id_usuario']]);
        
        // Remover token usado
        $stmt = $pdo->prepare("DELETE FROM tokens_redefinicao WHERE token = ?");
        $stmt->execute([$token]);
        
        $sucesso = true;
        
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>

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
        <h1>Redefinir Senha</h1>
        
        <?php if ($sucesso): ?>
            <div class="message success">
                Senha redefinida com sucesso! Você pode fazer login agora.
            </div>
            <div class="login-link">
                <a href="../pages/login.html">Ir para login</a>
            </div>
        <?php else: ?>
            <?php if ($erro): ?>
                <div class="message error"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>
            
            <?php if ($token): ?>
                <form method="POST">
                    <div class="textfield">
                        <label for="nova_senha">Nova Senha</label>
                        <input type="password" id="nova_senha" name="nova_senha" placeholder="Digite sua nova senha" required>
                    </div>
                    <div class="textfield">
                        <label for="confirmar_senha">Confirmar Nova Senha</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Confirme sua nova senha" required>
                    </div>
                    <button type="submit" class="btn-submit">Redefinir Senha</button>
                </form>
            <?php else: ?>
                <div class="message error">
                    Token inválido ou não fornecido.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>