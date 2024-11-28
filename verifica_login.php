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
        die(json_encode(["sucesso" => false, "mensagem" => "Erro ao conectar ao banco de dados."]));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($usuario) || empty($senha)) {
        die(json_encode(["sucesso" => false, "mensagem" => "Usuário ou senha não podem estar vazios."]));
    }

    $pdo = conectar();
    $tabela = "usuario";

    try {
        $sql = "SELECT senha FROM $tabela WHERE login = :usuario";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":usuario", $usuario);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado && password_verify($senha, $resultado['senha'])) {
            die(json_encode(["sucesso" => true]));
        } else {
            die(json_encode(["sucesso" => false, "mensagem" => "Usuário ou senha incorretos."]));
        }
    } catch (Exception $erro) {
        die(json_encode(["sucesso" => false, "mensagem" => "Erro ao validar login."]));
    }
}
?>
//
