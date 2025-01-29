<?php

session_start();

function conectar() {
    $local_server = "PC_NASA\SQLEXPRESS"; 
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

$nome = $_POST["nome"];
$email = $_POST["email"];
$senha = $_POST["senha"];
$dataNascimento = $_POST["dataNascimento"];
$telefone = $_POST["telefone"];

$sql = $pdo->prepare("INSERT INTO Usuario (nome, email, senha, dataNascimento, telefone) VALUES (:nome, :email, :senha, :dataNascimento, :telefone);");
$sql->bindValue(":nome", $nome);
$sql->bindValue(":email", $email);
$sql->bindValue(":senha", $senha);
$sql->bindValue(":dataNascimento", $dataNascimento);
$sql->bindValue(":telefone", $telefone);

if ($sql->execute()) {
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['user_name'] = $nome;
    echo json_encode(['sucesso' => true]);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao cadastrar.']);
}

?>