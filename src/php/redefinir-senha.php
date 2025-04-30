<?php
session_start();
require_once '../php/conexao.php';

$hash = filter_input(INPUT_GET, 'hash', FILTER_SANITIZE_STRING);

try {
    $pdo = conectar();
    
    // Verifica se o hash é válido e não expirou
    $stmt = $pdo->prepare("SELECT id_usuario FROM Usuario WHERE reset_hash = ? AND hash_expiracao > NOW()");
    $stmt->execute([$hash]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        die("Link inválido ou expirado. Solicite um novo link de redefinição.");
    }
    
    // Se chegou aqui, o hash é válido - mostra o formulário
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Redefinir Senha</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            input[type="password"] { width: 100%; padding: 8px; }
            button { padding: 10px 15px; background: #0066cc; color: white; border: none; cursor: pointer; }
        </style>
    </head>
    <body>
        <h2>Redefinir Senha</h2>
        <form id="formRedefinir">
            <input type="hidden" name="hash" value="<?= htmlspecialchars($hash) ?>">
            
            <div class="form-group">
                <label>Nova Senha:</label>
                <input type="password" name="nova_senha" required minlength="6">
            </div>
            
            <div class="form-group">
                <label>Confirmar Nova Senha:</label>
                <input type="password" name="confirmar_senha" required minlength="6">
            </div>
            
            <button type="submit">Redefinir Senha</button>
        </form>
        
        <div id="mensagem" style="margin-top: 15px;"></div>
        
        <script>
            document.getElementById('formRedefinir').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const novaSenha = formData.get('nova_senha');
                const confirmarSenha = formData.get('confirmar_senha');
                
                if (novaSenha !== confirmarSenha) {
                    document.getElementById('mensagem').innerHTML = 'As senhas não coincidem';
                    return;
                }
                
                if (novaSenha.length < 6) {
                    document.getElementById('mensagem').innerHTML = 'A senha deve ter pelo menos 6 caracteres';
                    return;
                }
                
                fetch('../php/processar-redefinicao.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('mensagem').innerHTML = data.mensagem;
                    if (data.sucesso) {
                        document.getElementById('formRedefinir').reset();
                    }
                });
            });
        </script>
    </body>
    </html>
    <?php
    
} catch (PDOException $e) {
    die("Erro no servidor. Tente novamente mais tarde.");
}
?>