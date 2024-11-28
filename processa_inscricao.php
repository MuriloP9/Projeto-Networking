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
$tabela = "inscricoes_webinar";

try {
    $nome = $_POST["name"];
    $email = $_POST["email"];
    $telefone = $_POST["phone"];
    $notificacoes = isset($_POST["subscribe"]) ? 1 : 0;
    $consentimento = isset($_POST["lgpd-consent"]) ? 1 : 0;

    if ($consentimento !== 1) {
        throw new Exception("É necessário concordar com os termos de LGPD.");
    }

    $sql = $pdo->prepare("INSERT INTO " . $tabela . " (nome_completo, email, telefone, recebe_notificacoes, consentimento_lgpd) 
                          VALUES (:nome, :email, :telefone, :notificacoes, :consentimento)");

    $sql->bindValue(":nome", $nome);
    $sql->bindValue(":email", $email);
    $sql->bindValue(":telefone", $telefone);
    $sql->bindValue(":notificacoes", $notificacoes);
    $sql->bindValue(":consentimento", $consentimento);

    $sql->execute();

    header('Location: confirmacao_inscricao.html');
    die;
} catch (Exception $erro) {
    echo "ATENÇÃO, erro na inscrição: " . $erro->getMessage();
}
?>
//
