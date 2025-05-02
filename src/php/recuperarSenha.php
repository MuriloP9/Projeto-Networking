<?php
date_default_timezone_set('America/Sao_Paulo');

include("../php/Email.php"); 
$emailSender = new Email();

$userEmail = $_POST['email'];

// Gerar token seguro
$token = bin2hex(random_bytes(16));
$token_hash = hash("sha256", $token);

// Formato CORRETO para SQL Server (Y-m-d H:i:s)
$expiracao = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))
    ->add(new DateInterval('PT30M'))
    ->format('Y-m-d H:i:s.v'); // Adicionando milissegundos para SQL Server

// Debug mais detalhado
error_log("Token gerado para: $userEmail");
error_log("Token hash: $token_hash");
error_log("Data de expiração formatada: $expiracao");

// Atualizar o usuário com o token
$exec2 = $pdo->prepare("UPDATE Usuario 
                       SET token_rec_senha = :token_hash, 
                           dt_expiracao_token = :expiracao 
                       WHERE email = :email");
$exec2->bindValue(":token_hash", $token_hash);
$exec2->bindParam(":expiracao", $expiracao, PDO::PARAM_STR);
$exec2->bindValue(":email", $userEmail);
$exec2->execute();

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
?>