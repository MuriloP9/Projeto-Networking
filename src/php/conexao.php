<?php
function conectar() {
    $local_server = "PCNASA";
    $usuario_server = "sa";
    $senha_server = "etesp";
    $banco_de_dados = "prolink";

    try {
        $pdo = new PDO(
            "sqlsrv:Server=$local_server;Database=$banco_de_dados",
            $usuario_server,
            $senha_server,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
               // PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_BINARY
            ]
        );
        return $pdo;
    } catch (Exception $erro) {
        die("Erro na conexão: " . $erro->getMessage());
    }
}
?>