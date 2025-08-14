<?php
function conectar() {
    $host = "localhost"; // ou o IP do servidor MySQL
    $usuario = "root"; // usuário padrão do MySQL, altere conforme necessário
    $senha = "etesp"; // senha do MySQL (vazia por padrão em instalações locais)
    $banco_de_dados = "prolink01";
    $porta = 3306; // porta padrão do MySQL

    try {
        $pdo = new PDO(
            "mysql:host=$host;port=$porta;dbname=$banco_de_dados;charset=utf8mb4",
            $usuario,
            $senha,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
        return $pdo;
    } catch (PDOException $erro) {
        die("Erro na conexão: " . $erro->getMessage());
    }
}
?>