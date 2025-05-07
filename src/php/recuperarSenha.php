<?php 
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'])) {
    header("Location: ../php/index.php");
    exit();
}
date_default_timezone_set('America/Sao_Paulo');

include("../php/Email.php");

$emailSender = new Email();
$userEmail = $_POST['email'];

// Gerar token seguro
$token = bin2hex(random_bytes(16));
$token_hash = hash("sha256", $token);

// Armazenar timestamp UNIX em vez de datetime para evitar problemas de formato
$timestamp_expiracao = time() + (30 * 60); // Agora + 30 minutos em timestamp unix

// Log para depuração
error_log("Token gerado para: $userEmail");
error_log("Token hash: $token_hash");
error_log("Timestamp de expiração: $timestamp_expiracao");

try {
    // Verificar se a coluna timestamp_expiracao existe, se não existir, adicione-a
    try {
        $checkColumn = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                                    WHERE TABLE_NAME = 'Usuario' AND COLUMN_NAME = 'timestamp_expiracao'");
        
        if ($checkColumn->rowCount() == 0) {
            // A coluna não existe, crie-a
            $pdo->exec("ALTER TABLE Usuario ADD timestamp_expiracao INT");
            error_log("Coluna timestamp_expiracao adicionada à tabela Usuario");
        }
    } catch (PDOException $e) {
        error_log("Erro ao verificar/criar coluna: " . $e->getMessage());
      
    }

    // Atualizar o usuário com o token e timestamp unix
    $exec2 = $pdo->prepare("UPDATE Usuario 
                          SET token_rec_senha = :token_hash, 
                              dt_expiracao_token = GETDATE() + 0.0208333, -- Adiciona 30 minutos diretamente no SQL Server
                              timestamp_expiracao = :timestamp_expiracao 
                          WHERE email = :email");
    
    $exec2->bindValue(":token_hash", $token_hash);
    $exec2->bindValue(":timestamp_expiracao", $timestamp_expiracao, PDO::PARAM_INT);
    $exec2->bindValue(":email", $userEmail);
    
    if (!$exec2->execute()) {
        error_log("Erro ao atualizar token: " . print_r($exec2->errorInfo(), true));
    } else {
        error_log("Token atualizado com sucesso no banco");
    }

    // Verificar se o token foi realmente salvo
    $checkToken = $pdo->prepare("SELECT token_rec_senha, dt_expiracao_token, timestamp_expiracao FROM Usuario WHERE email = :email");
    $checkToken->bindValue(":email", $userEmail);
    $checkToken->execute();
    $userData = $checkToken->fetch(PDO::FETCH_ASSOC);
    
    error_log("Token verificado após inserção: " . $userData['token_rec_senha']);
    error_log("Data expiração verificada após inserção: " . $userData['dt_expiracao_token']);
    error_log("Timestamp expiração verificado após inserção: " . $userData['timestamp_expiracao']);

    // Obter nome do usuário para personalizar o email
    $exec3 = $pdo->prepare("SELECT nome FROM Usuario WHERE email = :userEmail");
    $exec3->bindValue(":userEmail", $userEmail);
    $exec3->execute();
    $nomeUser = $exec3->fetchColumn();

    $assuntoEmail = "Redefinir senha - ProLink";

    $corpoEmail = <<<END
<div style='display: flex; flex-direction: column; text-align: justify; width: fit-content; margin: auto;'>
    <div style='text-align: center;'>
        <h1>Olá, $nomeUser!</h1>
    </div>
    
    <p>Está com problemas para acessar sua conta do ProLink? A gente ajuda. Selecione o botão abaixo para redefinir sua senha.
    Este link é válido por 30 minutos.
    <br/><br/>
    
    <div style='margin: auto; text-align: center;'>
        <div style='background-color: #228B22; color: white; text-decoration: none; border-radius: 1rem; padding: 3%; width: 25%; margin: auto;'>
            <a href="http://localhost/Projeto-Networking/src/php/redefinir-senha.php?token=$token" style='color: white; text-decoration: none;'><strong>REDEFINIR SENHA</strong></a>
        </div>
        <br/><br/>
        <a href="http://localhost/Projeto-Networking/src/php/redefinir-senha.php?token=$token">http://localhost/Projeto-Networking/src/php/redefinir-senha.php?token=$token</a>
    </div>
    
    <p>Ao redefinir sua senha, você também confirma o e-mail associado à sua conta.<br>
    Se não solicitou a redefinição, ignore essa mensagem.</p>
    <br/><br/>
    <p>Equipe do ProLink</p>
    <p>Email de contato: prolink.web.contact@gmail.com</p>
</div>
END;

    $corpoAltEmail = "Acesse http://localhost/Projeto-Networking/src/php/redefinir-senha.php?token=$token para redefinir sua senha.";

    $emailSender->enviarEmail($userEmail, $assuntoEmail, $corpoEmail, $corpoAltEmail);
    
} catch (Exception $e) {
    error_log("Erro ao processar a recuperação de senha: " . $e->getMessage());
}
?>