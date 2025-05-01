<?php
require_once 'conexao.php';

header('Content-Type: application/json');

function gerarToken() {
    return bin2hex(random_bytes(32));
}

$response = ['sucesso' => false, 'mensagem' => ''];

try {
    $pdo = conectar();
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (!$email) {
        throw new Exception('Email inválido.');
    }

    // Verifica se o email existe
    $stmt = $pdo->prepare("SELECT id_usuario, nome FROM Usuario WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        throw new Exception('Email não encontrado em nosso sistema.');
    }

    // Gera token
    $token = gerarToken();
    $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Armazena no banco (corrigido o nome da tabela)
    $stmt = $pdo->prepare("INSERT INTO tokens_redefinicao (id_usuario, token, expiracao) VALUES (?, ?, ?)");
    $stmt->execute([$usuario['id_usuario'], $token, $expiracao]);

    // Versão para desenvolvimento (simulada)
    $resetLink = "http://localhost/prolink/redefinir-senha.php?token=$token";
    $mensagemEmail = "Para: $email\nAssunto: Redefinição de Senha\n\nClique no link para redefinir sua senha: $resetLink\n\n";
    file_put_contents('email_simulado.txt', $mensagemEmail, FILE_APPEND);
    
    $response = [
        'sucesso' => true,
        'mensagem' => 'Um link de redefinição foi enviado para seu email (simulado). Verifique o arquivo email_simulado.txt no servidor.',
        'dev_link' => $resetLink // Apenas para desenvolvimento
    ];

} catch (Exception $e) {
    // Log do erro para debug
    error_log('Erro em solicitar-redefinicao.php: ' . $e->getMessage());
    $response['mensagem'] = 'Erro ao processar sua solicitação. Detalhes: ' . $e->getMessage();
}

echo json_encode($response);
?>