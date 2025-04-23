<?php
function conectar() {
    $local_server = "PCNASA";
    //$local_server = "Book3-Marina";
    $usuario_server = "sa";
    $senha_server = "etesp";
    $banco_de_dados = "prolink";

    try {
        $pdo = new PDO("sqlsrv:server=$local_server;database=$banco_de_dados", $usuario_server, $senha_server);
        // Configura para lançar exceções em caso de erros
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (Exception $erro) {
        echo "Erro na conexão: " . $erro->getMessage();
        die;
    }
}
?>