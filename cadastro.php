<?php
session_start(); // Verifica se a sessão está sendo iniciada corretamente

// Função para conectar ao banco de dados
function conectar() {
    $local_server = "PC_NASA\SQLEXPRESS";
    $usuario_server = "sa";
    $senha_server = "etesp";
    $banco_de_dados = "prolink";

    try {
        $pdo = new PDO("sqlsrv:server=$local_server;database=$banco_de_dados", $usuario_server, $senha_server);
        return $pdo;
    } catch (Exception $erro) {
        echo "Erro na conexão: " . $erro->getMessage();
        die;
    }
}

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = isset($_POST["nome"]) ? $_POST["nome"] : null;
    $email = isset($_POST["email"]) ? $_POST["email"] : null;
    $senha = isset($_POST["senha"]) ? $_POST["senha"] : null;
    $dataNascimento = isset($_POST["dataNascimento"]) ? $_POST["dataNascimento"] : null;
    $telefone = isset($_POST["telefone"]) ? $_POST["telefone"] : null;

    // Valida campos obrigatórios
    if (!$nome || !$email || !$senha || !$dataNascimento || !$telefone) {
        echo "Todos os campos são obrigatórios!";
        exit;
    }

    // Conectar ao banco de dados
    $pdo = conectar();

    // Prepara a query para inserir no banco de dados
    try {
        $sql = $pdo->prepare("INSERT INTO Usuario (nome, email, senha, dataNascimento, telefone) VALUES (:nome, :email, :senha, :dataNascimento, :telefone)");
        $sql->bindValue(":nome", $nome);
        $sql->bindValue(":email", $email);
        $sql->bindValue(":senha", password_hash($senha, PASSWORD_DEFAULT)); // Hash a senha
        $sql->bindValue(":dataNascimento", $dataNascimento);
        $sql->bindValue(":telefone", $telefone);
        $sql->execute();

        $_SESSION['cadastro_realizado'] = true;
        header('Location: inclusaoCadastro.html'); // Redireciona para a página de sucesso
        exit;
    } catch (Exception $erro) {
        echo "Erro ao cadastrar: " . $erro->getMessage();
        exit;
    }
}
?>
