<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'])) {
    header("Location: ../php/index.php");
    exit();
}

session_start(); // Inicia a sessão

include("../php/conexao.php"); 

$pdo = conectar(); 

function limpar($valor) {
    $valor = trim($valor);
    $valor = filter_var($valor, FILTER_SANITIZE_EMAIL);
    return $valor;
}

// Recebe e sanitiza os dados
$email = isset($_POST['email']) ? limpar($_POST['email']) : null;
$senha = isset($_POST['senha']) ? trim($_POST['senha']) : null;

if (!$email || !$senha || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Email ou senha inválidos.']);
    exit;
}

$pdo = conectar();

try {
    // Verifica se o usuário existe no banco de dados
    $sql = $pdo->prepare("SELECT u.*, p.id_perfil FROM Usuario u 
    LEFT JOIN Perfil p ON u.id_usuario = p.id_usuario
    WHERE u.email = :email AND u.senha = :senha");
    $sql->bindValue(":email", $email);
    $sql->bindValue(":senha", $senha);
    $sql->execute();

    $usuario = $sql->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // Define variáveis de sessão para o usuário logado
        $_SESSION['usuario_logado'] = true;
        $_SESSION['nome_usuario'] = $usuario['nome'];
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['id_perfil'] = $usuario['id_perfil']; 


        // Retorna uma resposta JSON de sucesso
        echo json_encode(['sucesso' => true, 'mensagem' => 'Login realizado com sucesso!']);
    } else {
        // Retorna uma resposta JSON de erro
        echo json_encode(['sucesso' => false, 'mensagem' => 'Email ou senha incorretos.']);
    }
} catch (Exception $erro) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao validar login: ' . $erro->getMessage()]);
}

?>