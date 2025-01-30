<?php
session_start(); // Inicia a sessão

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

$tabela = "Usuario"; 

try {
    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $senha = $_POST["senha"];
    $dataNascimento = $_POST["dataNascimento"];
    $telefone = $_POST["telefone"];

    $sql = $pdo->prepare("INSERT INTO $tabela 
        (nome, email, senha, dataNascimento, telefone) 
        VALUES (:nome, :email, :senha, :dataNascimento, :telefone);");

    $sql->bindValue(":nome", $nome);
    $sql->bindValue(":email", $email);
    $sql->bindValue(":senha", $senha);
    $sql->bindValue(":dataNascimento", $dataNascimento);
    $sql->bindValue(":telefone", $telefone);

    $sql->execute();

    // Define uma variável de sessão para indicar que o cadastro foi realizado
    $_SESSION['cadastro_realizado'] = true;

    // Redireciona para a página de sucesso
    header('Location: inclusaoCadastro.html');
    exit;
} catch (Exception $erro) {
    // Exibe uma mensagem de erro
    echo "Erro ao cadastrar: " . $erro->getMessage();
    exit;
}
?>