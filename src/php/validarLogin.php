<?php
session_start(); // Inicia a sessão

function conectar() {
    //$local_server = "PC_NASA\SQLEXPRESS";
    $local_server = "Book3-Marina";
    $usuario_server = "sa";               
    $senha_server = "etesp";              
    $banco_de_dados = "prolink";         

    try {
        $pdo = new PDO("sqlsrv:server=$local_server;database=$banco_de_dados", $usuario_server, $senha_server);
        return $pdo;
    } catch (Exception $erro) {
        echo "ATENÇÃO - ERRO NA CONEXÃO: " . $erro->getMessage();
        die;
    }
}

$pdo = conectar(); 

$email = $_POST['email'];
$senha = $_POST['senha'];

try {
    // Verifica se o usuário existe no banco de dados
    $sql = $pdo->prepare("SELECT * FROM Usuario WHERE email = :email AND senha = :senha");
    $sql->bindValue(":email", $email);
    $sql->bindValue(":senha", $senha);
    $sql->execute();

    $usuario = $sql->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // Define variáveis de sessão para o usuário logado
        $_SESSION['usuario_logado'] = true;
        $_SESSION['nome_usuario'] = $usuario['nome'];
        $_SESSION['id_usuario'] = $usuario['id_usuario'];

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