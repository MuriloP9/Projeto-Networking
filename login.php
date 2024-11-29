<?php
function conectar() {
    $local_server = "PC_NASA\SQLEXPRESS"; // Nome do servidor
    $usuario_server = "sa";               // Usuário do servidor
    $senha_server = "etesp";              // Senha do servidor
    $banco_de_dados = "prolink";          // Nome do banco de dados

    try {
        // Conexão com o banco de dados usando PDO
        $pdo = new PDO("sqlsrv:server=$local_server;database=$banco_de_dados", $usuario_server, $senha_server);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  // Habilita a exibição de erros
        return $pdo;
    } catch (Exception $erro) {
        echo "ATENÇÃO - ERRO NA CONEXÃO: " . $erro->getMessage();
        die;
    }
}

$pdo = conectar(); // Estabelece a conexão com o banco de dados

// Obtém os dados do POST
$email = $_POST['email'];
$senha = $_POST['senha'];

// Depuração para garantir que os dados estão sendo passados corretamente
echo "Email: $email <br>"; 
echo "Senha: $senha <br>";

// Consulta para verificar o email
$sql = "SELECT * FROM cadastro WHERE email = :email";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':email', $email);
$stmt->execute();

// Verifica se o usuário foi encontrado
if ($stmt->rowCount() > 0) {
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verifica se a senha corresponde
    if (trim($usuario['senha']) === trim($senha)) {  // Remove espaços extras antes e depois
        echo 'sucesso'; // Senha correta
    } else {
        echo 'falha'; // Senha incorreta
    }
} else {
    echo 'falha'; // Email não encontrado
}
?>
