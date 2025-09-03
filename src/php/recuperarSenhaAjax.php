<?php
// Definir cabeçalhos para retornar JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido.'
    ]);
    exit;
}

try {
    // Sanitizar e validar o email
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Por favor, insira um email válido.'
        ]);
        exit;
    }
    
    // Incluir arquivo de conexão
    include("conexao.php"); 
    $pdo = conectar();
    
    // Verificar se o email existe na tabela Usuario
    $sql = "SELECT email FROM Usuario WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":email", $email);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        // Email existe, simular o envio da recuperação
        $_POST['email'] = $email;
        
        // Aqui você pode incluir o arquivo de recuperação original
        // ou implementar a lógica diretamente
        include("recuperarSenha.php");
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Mensagem enviada, por favor verifique sua caixa de entrada.'
        ]);
    } else {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Este email não está cadastrado em nosso sistema.'
        ]);
    }
    
} catch (PDOException $e) {
    // Log do erro (em produção, nunca expor detalhes do erro)
    error_log("Erro na recuperação de senha: " . $e->getMessage());
    
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno do servidor. Tente novamente mais tarde.'
    ]);
    
} catch (Exception $e) {
    // Log do erro genérico
    error_log("Erro genérico na recuperação de senha: " . $e->getMessage());
    
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro inesperado. Tente novamente mais tarde.'
    ]);
}
?>