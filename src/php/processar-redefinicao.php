<?php
require_once 'conexao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);
    $novaSenha = filter_input(INPUT_POST, 'nova_senha', FILTER_SANITIZE_STRING);
    $confirmarSenha = filter_input(INPUT_POST, 'confirmar_senha', FILTER_SANITIZE_STRING);
    
    // Validações básicas
    if (!$token || !$novaSenha || !$confirmarSenha) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Dados incompletos']);
        exit;
    }
    
    if ($novaSenha !== $confirmarSenha) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'As senhas não coincidem']);
        exit;
    }
    
    if (strlen($novaSenha) < 6) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'A senha deve ter pelo menos 6 caracteres']);
        exit;
    }
    
    try {
        $pdo = conectar();
        
        // Verifica o token na tabela tokens_redefinicao
        $stmt = $pdo->prepare("
            SELECT t.id_usuario
            FROM tokens_redefinicao t
            WHERE t.token = ? AND t.expiracao > GETDATE()
        ");
        $stmt->execute([$token]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$dados) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Token inválido ou expirado']);
            exit;
        }
        
        // Atualiza a senha (sem hash) - ATENÇÃO: não recomendado para produção
        $stmt = $pdo->prepare("UPDATE Usuario SET senha = ? WHERE id = ?");
        $stmt->execute([$novaSenha, $dados['id_usuario']]);
        
        // Remove o token usado
        $stmt = $pdo->prepare("DELETE FROM tokens_redefinicao WHERE usuario_id = ?");
        $stmt->execute([$dados['id_usuario']]);
        
        echo json_encode([
            'sucesso' => true, 
            'mensagem' => 'Senha redefinida com sucesso! Redirecionando para login...'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no servidor: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido']);
}
?>