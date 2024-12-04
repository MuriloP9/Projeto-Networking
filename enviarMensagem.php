<?php
function conectar() {
    $local_server = "PC_NASA\SQLEXPRESS"; // Nome do servidor
    $usuario_server = "sa";               // Usuário do servidor
    $senha_server = "etesp";              // Senha do servidor
    $banco_de_dados = "prolink";          // Nome do banco de dados

    try {
        $pdo = new PDO("sqlsrv:server=$local_server;database=$banco_de_dados", $usuario_server, $senha_server);
        return $pdo;
    } catch (Exception $erro) {
        echo "ATENÇÃO - ERRO NA CONEXÃO: " . $erro->getMessage();
        die;
    }
}

$pdo = conectar();

try {
    // Obtém os dados enviados pelo AJAX
    $texto = $_POST["mensagem"]; // O campo 'mensagem' do AJAX corresponde à coluna 'texto'
    $data_envio = date("Y-m-d H:i:s");

    // Define a tabela onde será feita a inserção
    $tabela = "Mensagem";

    // Query de inserção com o nome correto da coluna
    $sql = $pdo->prepare("INSERT INTO $tabela (texto, data_hora) VALUES (:texto, :data_envio)");
    $sql->bindValue(":texto", $texto);
    $sql->bindValue(":data_envio", $data_envio);

    $sql->execute();

    echo "sucesso"; // Retorna 'sucesso' em caso de sucesso
    exit;
} catch (Exception $erro) {
    echo "Erro: " . $erro->getMessage(); // Retorna a mensagem de erro em caso de falha
    exit;
}
?>
