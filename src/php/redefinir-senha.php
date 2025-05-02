<?php
date_default_timezone_set('America/Sao_Paulo');

include("../php/ErroMensagem.php"); 
$msgErro = new MensagemErro();

if (!isset($_GET['token'])) {
    $mensagem = "Token não fornecido.";
    $msgErro->exibirMensagemErro($mensagem, "");
    exit;
}

$token = $_GET['token'];
$token_hash = hash("sha256", $token);

include("../php/conexao.php"); 
$pdo = conectar();


$sql = "SELECT TOP 1 dt_expiracao_token FROM Usuario WHERE token_rec_senha = :token_hash";
$exec4 = $pdo->prepare($sql);
$exec4->bindValue(":token_hash", $token_hash);
$exec4->execute();
$user = $exec4->fetch(PDO::FETCH_ASSOC);


echo "Timestamp atual: " . time() . "<br>";

if (empty($user)) {
    $mensagem = "Token inválido ou não encontrado.";
    $msgErro->exibirMensagemErro($mensagem, "");
    exit;
}

try {
    $agora = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    
    // Converter a data do SQL Server para DateTime
    $dataBanco = $user['dt_expiracao_token'];
    
    // Debug dos valores brutos
    error_log("Tipo de dt_expiracao_token: " . gettype($dataBanco));
    error_log("Valor bruto de dt_expiracao_token: " . print_r($dataBanco, true));
    
    if ($dataBanco instanceof DateTime) {
        $validadeUsuario = $dataBanco;
    } else {
        // Remover milissegundos se existirem
        $dataBanco = preg_replace('/\.\d+/', '', $dataBanco);
        $validadeUsuario = new DateTime($dataBanco, new DateTimeZone('America/Sao_Paulo'));
    }
    
    // Debug
    error_log("Agora: " . $agora->format('Y-m-d H:i:s.u'));
    error_log("Validade: " . $validadeUsuario->format('Y-m-d H:i:s.u'));
    
    if ($agora > $validadeUsuario) {
        $mensagem = "Esse link expirou. Por favor, solicite um novo.";
        $msgErro->exibirMensagemErro($mensagem, "");
        exit;
    }
    
    // Token válido
    error_log("Token válido - continuando processo");
    // Restante do código...
    
} catch (Exception $e) {
    error_log("Erro ao validar token: " . $e->getMessage());
    $mensagem = "Erro ao validar o token: " . $e->getMessage();
    $msgErro->exibirMensagemErro($mensagem, "");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - ProLink</title>
    <link rel="icon" type="image/x-icon" href="src/imgs/icons/logo-ico.ico">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;400;900&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            background: #201b2c;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .password-reset-container {
            width: 90%;
            max-width: 500px;
            background: #2f2841;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0px 10px 40px #00000056;
            text-align: center;
        }
        
        .password-reset-container h1 {
            color: hsl(187, 76%, 53%);
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        
        .textfield {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .textfield label {
            display: block;
            color: #f0ffffde;
            margin-bottom: 10px;
            text-align: left;
            font-size: 1rem;
        }
        
        .textfield input {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 15px;
            background: #514869;
            color: #f0ffffde;
            font-size: 1rem;
            outline: none;
            box-shadow: 0px 10px 40px #00000056;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px 0px;
            margin-top: 20px;
            border: none;
            border-radius: 8px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 2px;
            color: #2b124b;
            background: hsl(187, 76%, 53%);
            cursor: pointer;
            box-shadow: 0px 10px 40px -12px #17d1d452;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0px 12px 45px -10px #17d1d452;
        }
        
        .btn-return {
            display: inline-block;
            width: 100%;
            padding: 16px 0px;
            margin-top: 10px;
            border: none;
            border-radius: 8px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 2px;
            color: #2b124b;
            background: #6c757d;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-return:hover {
            transform: translateY(-2px);
            box-shadow: 0px 5px 15px rgba(108, 117, 125, 0.4);
        }
        
        .info-text {
            margin-top: 10px;
            color: #f0ffff94;
            font-size: 0.9rem;
            text-align: left;
        }
        
        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
            display: none;
        }
        
        .error {
            background-color: #f44336;
            color: white;
        }
    </style>
</head>
<body>
    <div class="password-reset-container">
        <h1>Redefinir Senha</h1>
        
        <form method="post" id="formRecupSenha">
            <div class="textfield">
                <label for="senha"><strong>Nova Senha:</strong></label>
                <input type="password" id="senha" name="senha" maxlength="15" placeholder="Máximo de 15 caracteres" required>
                <p class="info-text">Senhas fortes incluem números, letras e sinais de pontuação.</p>
            </div>
            
            <div class="textfield">
                <label for="confirmaSenha"><strong>Confirmar Nova Senha:</strong></label>
                <input type="password" id="confirmaSenha" name="confirmaSenha" maxlength="15" placeholder="Confirme sua senha" required>
            </div>
            
            <button type="submit" class="btn-submit">REDEFINIR SENHA</button>
            <a href="../php/index.php" class="btn-return">VOLTAR</a>
        </form>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $senha = $_POST['senha'];
        $confirmaSenha = $_POST['confirmaSenha'];

        if (!empty($senha) && !empty($confirmaSenha)) {
            if ($senha == $confirmaSenha) {
                // Atualizar a tabela Usuario
                $exec5 = $pdo->prepare("UPDATE Usuario SET senha = :senha, token_rec_senha = null, dt_expiracao_token = null WHERE token_rec_senha = :token_hash");
                $exec5->bindValue(":token_hash", $token_hash);
                $exec5->bindValue(":senha", password_hash($senha, PASSWORD_DEFAULT));
                $exec5->execute();

                echo "<script>
                    alert('Senha redefinida com sucesso!');
                    window.location.href = 'index.php';
                </script>";
                exit();
            } else {
                echo "<script>
                    alert('As senhas não coincidem!');
                </script>";
            }
        } else {
            echo "<script>
                alert('Por favor, preencha todos os campos!');
            </script>";
        }
    }
    ?>
</body>
</html>