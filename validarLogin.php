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
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao conectar ao banco de dados.']);
        die;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Email e senha são obrigatórios.']);
        exit;
    }

    $pdo = conectar();

    try {
        $sql = "SELECT senha FROM cadastro WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $senha === $user['senha']) { // Comparação direta da senha
            echo json_encode(["sucesso" => true, "mensagem" => "Login realizado com sucesso."]);
        } else {
            echo json_encode(["sucesso" => false, "mensagem" => "Email ou senha inválidos."]);
        }
    } catch (Exception $erro) {
        echo json_encode(["sucesso" => false, "mensagem" => "Erro no servidor: " . $erro->getMessage()]);
    }
}
?>
