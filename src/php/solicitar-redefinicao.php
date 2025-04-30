<?php
session_start();
header('Content-Type: application/json');
require_once '../php/conexao.php';

// Validação do email
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Email inválido']);
    exit;
}

try {
    $pdo = conectar();
    
    // Verifica se o usuário existe
    $stmt = $pdo->prepare("SELECT id_usuario FROM Usuario WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Email não encontrado']);
        exit;
    }
    
    // Gera hash único e data de expiração (30 minutos)
    $hash = bin2hex(random_bytes(32));
    $expiracao = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    
    // Atualiza o usuário com o hash
    $update = $pdo->prepare("UPDATE Usuario SET reset_hash = ?, hash_expiracao = ? WHERE email = ?");
    $update->execute([$hash, $expiracao, $email]);
    
    // Envia email com link direto
    $link = "https://seusite.com/redefinir-senha?hash=" . urlencode($hash);
    
    $assunto = "Redefinição de Senha - ProLink";
    $mensagem = "Clique no link abaixo para redefinir sua senha (válido por 30 minutos):\n\n$link";
    $headers = "From: noreply@seusite.com";
    
    if (mail($email, $assunto, $mensagem, $headers)) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Link de redefinição enviado para seu email']);
    } else {
        throw new Exception('Falha ao enviar email');
    }
    
} catch (PDOException $e) {
    error_log("Erro PDO: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no servidor. Tente novamente.']);
} catch (Exception $e) {
    error_log("Erro: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}
?>