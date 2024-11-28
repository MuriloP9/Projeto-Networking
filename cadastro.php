<?php
function conectar() {
    $local_server = "PC_NASA\SQLEXPRESS";  // Nome do servidor
    $usuario_server = "sa";                // Usuário do servidor
    $senha_server = "etesp";               // Senha do servidor
    $banco_de_dados = "cadastro";          // Nome do banco de dados

    try {
        // Estabelecendo a conexão com o banco de dados
        $pdo = new PDO("sqlsrv:server=$local_server;database=$banco_de_dados", $usuario_server, $senha_server);
        return $pdo;
    } catch (Exception $erro) {
        // Tratamento de erro na conexão
        echo "ATENÇÃO - ERRO NA CONEXÃO: " . $erro->getMessage();
        die;
    }
}

$pdo = conectar();  // Conectando ao banco de dados

// Coleta os dados do formulário
$nome = $_POST['nome'];
$email = $_POST['email'];
$senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);  // Criptografa a senha
$dataNascimento = $_POST['dataNascimento'];
$telefone = $_POST['telefone'];

$tabela = "cadastro";  // Nome da tabela onde os dados serão inseridos

try {
    // Preparando a consulta SQL para inserir os dados
    $sql = "INSERT INTO $tabela (nome, email, senha, dataNascimento, telefone) 
            VALUES (:nome, :email, :senha, :dataNascimento, :telefone)";
    
    $ponteiro = $pdo->prepare($sql);
    
    // Vincula os valores
    $ponteiro->bindValue(":nome", $nome);
    $ponteiro->bindValue(":email", $email);
    $ponteiro->bindValue(":senha", $senha);
    $ponteiro->bindValue(":dataNascimento", $dataNascimento);
    $ponteiro->bindValue(":telefone", $telefone);
    
    // Executa a inserção
    $ponteiro->execute();

    // Se o cadastro for realizado com sucesso
    $retorno = [
        "status" => "sucesso",
        "mensagem" => "Cadastro realizado com sucesso!"
    ];
    die(json_encode($retorno, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
} catch (Exception $erro) {
    // Caso ocorra um erro na execução
    $retorno = [
        "status" => "erro",
        "mensagem" => $erro->getMessage()
    ];
    die(json_encode($retorno, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
?>
//