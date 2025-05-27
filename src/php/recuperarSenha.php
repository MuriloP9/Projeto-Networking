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
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px;'>
    <!-- Container principal com fundo branco -->
    <div style='background-color: white; border-radius: 10px; padding: 40px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>
        
        <!-- Cabeçalho com logo -->
        <div style='text-align: center; margin-bottom: 30px;'>
            <h1 style='color: #333; margin: 0; font-size: 28px; font-weight: 300;'>Olá, $nomeUser!</h1>
        </div>
        
        <!-- Linha divisória -->
        <div style='height: 2px; background: linear-gradient(to right, #1e3a8a, #3b82f6); margin: 20px 0;'></div>
        
        <!-- Conteúdo principal -->
        <div style='text-align: justify; line-height: 1.6; color: #555; margin-bottom: 30px;'>
            <p style='font-size: 16px; margin-bottom: 20px;'>
                Está com problemas para acessar sua conta do <strong style='color: #1e3a8a;'>ProLink</strong>? A gente ajuda! 
            </p>
            
            <p style='font-size: 16px; margin-bottom: 25px;'>
                Clique no botão abaixo para redefinir sua senha. Este link é válido por <strong>30 minutos</strong>.
            </p>
        </div>
        
        <!-- Botão de ação -->
        <div style='text-align: center; margin: 35px 0;'>
            <a href="http://localhost/Projeto-Networking/src/php/redefinir-senha.php?token=$token" 
               style='display: inline-block; background: linear-gradient(135deg, #1e3a8a, #3b82f6); 
                      color: white; text-decoration: none; border-radius: 25px; 
                      padding: 15px 35px; font-size: 16px; font-weight: bold; 
                      text-transform: uppercase; letter-spacing: 1px;
                      box-shadow: 0 4px 15px rgba(30, 58, 138, 0.3);
                      transition: all 0.3s ease;'>
                🔐 Redefinir Senha
            </a>
        </div>
        
        <!-- Link alternativo -->
        <div style='text-align: center; margin: 25px 0;'>
            <p style='font-size: 14px; color: #777; margin-bottom: 10px;'>
                Caso o botão não funcione, copie e cole o link abaixo:
            </p>
            <div style='background-color: #f5f5f5; border: 1px dashed #ddd; border-radius: 5px; padding: 15px; word-break: break-all;'>
                <a href="http://localhost/Projeto-Networking/src/php/redefinir-senha.php?token=$token" 
                   style='color: #1e3a8a; text-decoration: none; font-size: 14px;'>
                    http://localhost/Projeto-Networking/src/php/redefinir-senha.php?token=$token
                </a>
            </div>
        </div>
        
        <!-- Informações importantes -->
        <div style='background-color: #f8f9fa; border-left: 4px solid #1e3a8a; padding: 20px; margin: 25px 0; border-radius: 0 5px 5px 0;'>
            <p style='margin: 0; font-size: 15px; color: #666;'>
                ℹ️ <strong>Importante:</strong> Ao redefinir sua senha, você também confirma o e-mail associado à sua conta.
            </p>
            <p style='margin: 10px 0 0 0; font-size: 14px; color: #666;'>
                Se não solicitou a redefinição, ignore esta mensagem.
            </p>
        </div>
        
        <!-- Rodapé -->
        <div style='border-top: 1px solid #eee; padding-top: 25px; margin-top: 35px; text-align: center;'>
            <p style='font-size: 16px; font-weight: bold; color: #1e3a8a; margin-bottom: 10px;'>
                Equipe do ProLink
            </p>
            <p style='font-size: 14px; color: #777; margin: 5px 0;'>
                📧 Email de contato: 
                <a href="mailto:prolink.web.contact@gmail.com" style='color: #1e3a8a; text-decoration: none;'>
                    prolink.web.contact@gmail.com
                </a>
            </p>
            <p style='font-size: 12px; color: #999; margin-top: 15px;'>
                © 2025 ProLink - Conectando pessoas e oportunidades
            </p>
        </div>
    </div>
    
    <!-- Rodapé externo -->
    <div style='text-align: center; margin-top: 20px;'>
        <p style='font-size: 12px; color: #999;'>
            Este email foi enviado automaticamente. Por favor, não responda a esta mensagem.
        </p>
    </div>
</div>
END;

    $corpoAltEmail = "Acesse http://localhost/Projeto-Networking/src/php/redefinir-senha.php?token=$token para redefinir sua senha.";

    $emailSender->enviarEmail($userEmail, $assuntoEmail, $corpoEmail, $corpoAltEmail);
    
} catch (Exception $e) {
    error_log("Erro ao processar a recuperação de senha: " . $e->getMessage());
}
?>