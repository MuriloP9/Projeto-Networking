<?php
// Função para estabelecer conexão com o banco de dados
function conectar() {
    // Dados para conexão com o banco SQL Server
    $local_server = "PC_NASA\SQLEXPRESS"; // Nome do servidor e instância do SQL Server
    $usuario_server = "sa";               // Nome de usuário para autenticação
    $senha_server = "etesp";              // Senha do usuário
    $banco_de_dados = "prolink";          // Nome do banco de dados

    try {
        // Cria uma nova conexão PDO com o banco usando o driver 'sqlsrv'
        $pdo = new PDO("sqlsrv:server=$local_server;database=$banco_de_dados", $usuario_server, $senha_server);
        return $pdo; // Retorna o objeto PDO se a conexão for bem-sucedida
    } catch (Exception $erro) {
        // Retorna uma mensagem de erro caso a conexão falhe
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao conectar ao banco de dados.']);
        die; // Finaliza o script
    }
}

// Verifica se o método da requisição HTTP é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém os valores enviados no formulário (email e senha)
    $email = $_POST['email'] ?? ''; // Define como vazio caso o campo não esteja preenchido
    $senha = $_POST['senha'] ?? ''; // Define como vazio caso o campo não esteja preenchido

    // Verifica se os campos email ou senha estão vazios
    if (empty($email) || empty($senha)) {
        // Retorna uma mensagem de erro em formato JSON e finaliza a execução
        echo json_encode(['sucesso' => false, 'mensagem' => 'Email e senha são obrigatórios.']);
        exit;
    }

    // Estabelece a conexão com o banco de dados
    $pdo = conectar();

    try {
        // Declara a query SQL para buscar a senha associada ao email informado
        $sql = "SELECT senha FROM cadastro WHERE email = :email";
        $stmt = $pdo->prepare($sql); // Prepara a consulta SQL
        $stmt->bindParam(':email', $email, PDO::PARAM_STR); // Substitui o parâmetro :email pela variável $email
        $stmt->execute(); // Executa a consulta no banco de dados

        // Obtém o resultado da consulta como um array associativo
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se o usuário foi encontrado e compara a senha informada com a armazenada no banco
        if ($user && $senha === $user['senha']) { // Comparação direta da senha sem uso de hash
            // Retorna uma mensagem de sucesso em formato JSON
            echo json_encode(["sucesso" => true, "mensagem" => "Login realizado com sucesso."]);
        } else {
            // Retorna uma mensagem de erro caso o email ou senha sejam inválidos
            echo json_encode(["sucesso" => false, "mensagem" => "Email ou senha inválidos."]);
        }
    } catch (Exception $erro) {
        // Captura e retorna qualquer erro ocorrido durante a execução da query
        echo json_encode(["sucesso" => false, "mensagem" => "Erro no servidor: " . $erro->getMessage()]);
    }
}
?>
