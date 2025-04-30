<?php
session_start();
include("../php/conexao.php");

function gerarToken() {
    return bin2hex(random_bytes(32));
}

function enviarEmailRedefinicao($email, $token) {
    $assunto = "Redefinição de Senha - ProLink";
    $link = "http://seusite.com/pages/redefinir-senha.php?token=" . $token;
    
    $mensagem = "
    <html>
    <head>
        <title>Redefinição de Senha</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #3b6ebb; color: white; padding: 10px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .button { 
                display: inline-block; 
                background-color: #3b6ebb; 
                color: white; 
                padding: 10px 20px; 
                text-decoration: none; 
                border-radius: 5px; 
                margin: 20px 0;
            }
            .footer { text-align: center; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Redefinição de Senha</h2>
            </div>
            <div class='content'>
                <p>Olá,</p>
                <p>Recebemos uma solicitação para redefinir sua senha no ProLink.</p>
                <p>Clique no botão abaixo para criar uma nova senha:</p>
                <p><a href='$link' class='button'>Redefinir Senha</a></p>
                <p>Se você não solicitou esta redefinição, por favor ignore este email.</p>
                <p>Este link expirará em 1 hora.</p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " ProLink. Todos os direitos reservados.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: ProLink <noreply@seusite.com>\r\n";
    
    return mail($email, $assunto, $mensagem, $headers);
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Por favor, forneça um email válido.']);
    exit;
}

try {
    $pdo = conectar();
    
    // Verifica se o email existe
    $stmt = $pdo->prepare("SELECT id_usuario FROM Usuario WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Email não encontrado.']);
        exit;
    }
    
    // Gera token e data de expiração (1 hora)
    $token = gerarToken();
    $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Insere ou atualiza o token na tabela (assumindo que você criará esta tabela)
    $stmt = $pdo->prepare("REPLACE INTO tokens_redefinicao (id_usuario, token, expiracao) VALUES (?, ?, ?)");
    $stmt->execute([$usuario['id_usuario'], $token, $expiracao]);
    
    // Envia o email
    if (enviarEmailRedefinicao($email, $token)) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Enviamos um email com instruções para redefinir sua senha.']);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao enviar email. Tente novamente mais tarde.']);
    }
    
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao processar solicitação: ' . $e->getMessage()]);
}
?>