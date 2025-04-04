<?php
function conectar() {
    $local_server = "Book3-Marina"; 
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

    echo "sucesso"; 
    exit;
} catch (Exception $erro) {
    echo "Erro: " . $erro->getMessage(); 
    exit;
}
?>
