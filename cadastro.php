<?php
function conectar() {
    $local_server = "PC_NASA\SQLEXPRESS"; // Nome do servidor
    $usuario_server = "sa";               // Usuário do servidor
    $senha_server = "etesp";              // Senha do servidor
    $banco_de_dados = "prolink";         // Nome do banco de dados

    try {
        // Conexão com o banco de dados usando PDO
        $pdo = new PDO("sqlsrv:server=$local_server;database=$banco_de_dados", $usuario_server, $senha_server);
        return $pdo;
    } catch (Exception $erro) {
        // Tratamento de erro na conexão
        echo "ATENÇÃO - ERRO NA CONEXÃO: " . $erro->getMessage();
        die;
    }
}

$pdo = conectar(); // Estabelece a conexão com o banco de dados

$tabela = "Usuario"; // Nome da tabela

// Inclusão de dados
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

    echo 'sucesso'; // Retorna 'sucesso' em caso de sucesso
    exit;
} catch (Exception $erro) {
    echo "Erro: " . $erro->getMessage(); // Retorna a mensagem de erro
    exit;
}
?>