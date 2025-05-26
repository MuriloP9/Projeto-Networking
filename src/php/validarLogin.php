<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'])) {
    header("Location: ../php/index.php");
    exit();
}

session_start();

include("../php/conexao.php");

$pdo = conectar();

function limpar($valor) {
    $valor = trim($valor);
    $valor = filter_var($valor, FILTER_SANITIZE_EMAIL);
    return $valor;
}

$email = isset($_POST['email']) ? limpar($_POST['email']) : null;
$senha = isset($_POST['senha']) ? trim($_POST['senha']) : null;

if (!$email || !$senha || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Email ou senha inválidos.']);
    exit;
}

try {
    // Executar a stored procedure
    $sql = $pdo->prepare("EXEC sp_ValidarLogin :email, :senha");
    $sql->bindValue(":email", $email);
    $sql->bindValue(":senha", $senha);
    $sql->execute();
    
    $resultado = $sql->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado && $resultado['sucesso'] == 1) {
       
        $_SESSION['usuario_logado'] = true;
        $_SESSION['nome_usuario'] = $resultado['nome'];
        $_SESSION['id_usuario'] = $resultado['id_usuario'];
        $_SESSION['id_perfil'] = $resultado['id_perfil'];
        
        echo json_encode([
            'sucesso' => true, 
            'mensagem' => $resultado['mensagem']
        ]);
    } else {
        // Login falhou
        echo json_encode([
            'sucesso' => false, 
            'mensagem' => $resultado['mensagem'] ?? 'Erro desconhecido.'
        ]);
    }
    
} catch (Exception $erro) {
    echo json_encode([
        'sucesso' => false, 
        'mensagem' => 'Erro ao validar login: ' . $erro->getMessage()
    ]);
}
?>