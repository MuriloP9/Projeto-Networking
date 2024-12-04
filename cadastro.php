<?php
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

    echo 'sucesso'; 
    exit;
} catch (Exception $erro) {
    echo "Erro: " . $erro->getMessage(); 
    exit;
}
?>