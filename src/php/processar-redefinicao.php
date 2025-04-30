<?php
session_start();
header('Content-Type: application/json');
require_once 'conexao.php';

$hash = filter_input(INPUT_POST, 'hash', FILTER_SANITIZE_STRING);
$nova_senha = filter_input(INPUT_POST, 'nova_senha', FILTER_SANITIZE_STRING);
$confirmar_senha = filter_input(INPUT_POST, 'confirmar_senha', FILTER_SANITIZE_STRING);

// Validações básicas
if (!$hash || !$nova_senha || !$confirmar_senha) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados incompletos']);
    exit;
}

if ($nova_senha !== $confirmar_senha) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'As senhas não coincidem']);
    exit;
}

if (strlen($nova_senha) < 6) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'A senha deve ter pelo menos 6 caracteres']);
    exit;
}

try {
    $pdo = conectar();
    
    // Verifica o hash novamente (proteção contra mudanças durante o preenchimento do formulário)
    $stmt = $pdo->prepare("SELECT id_usuario FROM Usuario WHERE reset_hash = ? AND hash_expiracao > NOW()");
    $stmt->execute([$hash]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Link inválido ou expirado. Solicite um novo.']);
        exit;
    }
    
    // Atualiza a senha e limpa o hash
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE Usuario SET senha = ?, reset_hash = NULL, hash_expiracao = NULL WHERE id_usuario = ?");
    $update->execute([$senha_hash, $usuario['id_usuario']]);
    
    echo json_encode(['sucesso' => true, 'mensagem' => 'Senha redefinida com sucesso!']);
    
} catch (PDOException $e) {
    error_log("Erro PDO: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao redefinir senha. Tente novamente.']);
}
?>