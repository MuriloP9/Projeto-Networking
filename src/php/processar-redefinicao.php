<?php
session_start();
include("../php/conexao.php");

$token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);
$nova_senha = filter_input(INPUT_POST, 'nova_senha', FILTER_SANITIZE_STRING);
$confirmar_senha = filter_input(INPUT_POST, 'confirmar_senha', FILTER_SANITIZE_STRING);

if (!$token) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Token inválido.']);
    exit;
}

if (!$nova_senha || !$confirmar_senha) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Por favor, preencha todos os campos.']);
    exit;
}

if ($nova_senha !== $confirmar_senha) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'As senhas não coincidem.']);
    exit;
}

if (strlen($nova_senha) < 6) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'A senha deve ter pelo menos 6 caracteres.']);
    exit;
}

try {
    $pdo = conectar();
    
    // Verifica se o token é válido e não expirou
    $stmt = $pdo->prepare("
        SELECT t.id_usuario, t.token 
        FROM tokens_redefinicao t
        JOIN Usuario u ON t.id_usuario = u.id_usuario
        WHERE t.token = ? AND t.expiracao > NOW()
    ");
    $stmt->execute([$token]);
    $token_valido = $stmt->fetch();
    
    if (!$token_valido) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Token inválido ou expirado. Solicite um novo link.']);
        exit;
    }
    
    // Atualiza a senha do usuário
    $stmt = $pdo->prepare("UPDATE Usuario SET senha = ? WHERE id_usuario = ?");
    $stmt->execute([$nova_senha, $token_valido['id_usuario']]);
    
    // Remove o token usado
    $stmt = $pdo->prepare("DELETE FROM tokens_redefinicao WHERE token = ?");
    $stmt->execute([$token]);
    
    echo json_encode(['sucesso' => true, 'mensagem' => 'Senha redefinida com sucesso!']);
    
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao redefinir senha: ' . $e->getMessage()]);
}
?>