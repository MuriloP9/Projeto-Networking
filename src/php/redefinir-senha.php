<?php
date_default_timezone_set('America/Sao_Paulo');

include("../php/ErroMensagem.php"); 
$msgErro = new MensagemErro();

// Função de sanitização aprimorada
function limpar($valor) {
    // Primeira camada: remove caracteres de controle
    $valor = preg_replace('/[\x00-\x1F\x7F]/u', '', $valor);
    // Segunda camada: remove tags HTML
    $valor = strip_tags(trim($valor));
    // Terceira camada: escapa caracteres especiais
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

// Função aprimorada de sanitização com validação rigorosa
function sanitizar_input($valor, $tipo = 'string') {
    if (empty($valor)) {
        return null;
    }
    
    switch ($tipo) {
        case 'token':
            // Sanitiza token (apenas caracteres alfanuméricos)
            $token = preg_replace('/[^a-zA-Z0-9]/', '', $valor);
            if (strlen($token) >= 32 && strlen($token) <= 128) {
                return $token;
            }
            return false;
            
        case 'password':
            // Remove apenas caracteres de controle, mantendo caracteres especiais para senhas
            $senha = preg_replace('/[\x00-\x1F\x7F]/u', '', $valor);
            if (strlen($senha) >= 6 && strlen($senha) <= 15) {
                return $senha;
            }
            return false;
            
        case 'string':
        default:
            // Sanitização padrão para strings
            return mb_convert_encoding(limpar($valor), 'UTF-8', 'auto');
    }
}

function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    
    // Sanitiza o IP
    return filter_var($ipaddress, FILTER_SANITIZE_STRING);
}

// Validação e sanitização do token
if (!isset($_GET['token'])) {
    $mensagem = "Token não fornecido.";
    $msgErro->exibirMensagemErro($mensagem, "");
    exit;
}

$token_raw = sanitizar_input($_GET['token'], 'token');
if ($token_raw === false || empty($token_raw)) {
    $mensagem = "Token inválido.";
    $msgErro->exibirMensagemErro($mensagem, "");
    exit;
}

$token = $token_raw;
$token_hash = hash("sha256", $token);

include("../php/conexao.php"); 
$pdo = conectar();

function criptografarSenha($pdo, $senhaTexto) {
    try {
        
        $sql = "
        DECLARE @SenhaCriptografada VARBINARY(MAX);
        EXEC sp_CriptografarSenha :senhaTexto, @SenhaCriptografada OUTPUT;
        SELECT @SenhaCriptografada as senha_cripto;
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':senhaTexto', $senhaTexto, PDO::PARAM_STR);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado && isset($resultado['senha_cripto'])) {
            return $resultado['senha_cripto'];
        } else {
            throw new Exception("Falha ao obter senha criptografada");
        }
        
    } catch (Exception $e) {
        error_log("Erro ao criptografar senha: " . $e->getMessage());
        
        
        try {
            $sql_fallback = "
            DECLARE @GUID UNIQUEIDENTIFIER;
            DECLARE @SenhaCriptografada VARBINARY(MAX);
            
            OPEN SYMMETRIC KEY ChaveSenhaUsuario
            DECRYPTION BY CERTIFICATE CertificadoSenhaUsuario 
            WITH PASSWORD = 'SENHA@123ProLink2024!';
            
            SET @GUID = (SELECT KEY_GUID('ChaveSenhaUsuario'));
            SET @SenhaCriptografada = ENCRYPTBYKEY(@GUID, :senhaTexto);
            
            CLOSE SYMMETRIC KEY ChaveSenhaUsuario;
            
            SELECT @SenhaCriptografada as senha_cripto;
            ";
            
            $stmt_fallback = $pdo->prepare($sql_fallback);
            $stmt_fallback->bindParam(':senhaTexto', $senhaTexto, PDO::PARAM_STR);
            $stmt_fallback->execute();
            
            $resultado_fallback = $stmt_fallback->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado_fallback && isset($resultado_fallback['senha_cripto'])) {
                return $resultado_fallback['senha_cripto'];
            } else {
                throw new Exception("Falha no método alternativo de criptografia");
            }
            
        } catch (Exception $e2) {
            error_log("Erro no fallback de criptografia: " . $e2->getMessage());
            throw new Exception("Erro ao processar senha - todos os métodos falharam");
        }
    }
}

// Três abordagens de verificação para garantir consistência
$sql = "SELECT 
          token_rec_senha, 
          dt_expiracao_token, 
          timestamp_expiracao, 
          CASE
            WHEN dt_expiracao_token > GETDATE() THEN 'VALID_BY_DATETIME'
            ELSE 'EXPIRED_BY_DATETIME'
          END as datetime_status,
          CASE
            WHEN timestamp_expiracao > :current_timestamp THEN 'VALID_BY_TIMESTAMP'
            ELSE 'EXPIRED_BY_TIMESTAMP'
          END as timestamp_status
        FROM Usuario 
        WHERE token_rec_senha = :token_hash";

$exec4 = $pdo->prepare($sql);
$current_timestamp = time();
$exec4->bindValue(":token_hash", $token_hash, PDO::PARAM_STR);
$exec4->bindValue(":current_timestamp", $current_timestamp, PDO::PARAM_INT);
$exec4->execute();
$user = $exec4->fetch(PDO::FETCH_ASSOC);

// Log detalhado para diagnóstico
error_log("Token hash recebido: " . $token_hash);
error_log("Timestamp atual: " . $current_timestamp);
error_log("Resultado da consulta: " . print_r($user, true));

// Se não encontrou o token
if (empty($user)) {
    $mensagem = "Token inválido ou não encontrado.";
    $msgErro->exibirMensagemErro($mensagem, "");
    exit;
}

// Verificar a validade através de múltiplos métodos
$valid_by_datetime = ($user['datetime_status'] === 'VALID_BY_DATETIME');
$valid_by_timestamp = ($user['timestamp_status'] === 'VALID_BY_TIMESTAMP');

error_log("Válido por datetime: " . ($valid_by_datetime ? "SIM" : "NÃO"));
error_log("Válido por timestamp: " . ($valid_by_timestamp ? "SIM" : "NÃO"));

// Se pelo menos um método confirma que o token é válido
if ($valid_by_timestamp || $valid_by_datetime) {
    error_log("Token considerado VÁLIDO através de pelo menos um método");
} else {
    error_log("Token EXPIRADO por ambos os métodos");
    $mensagem = "Esse link expirou. Por favor, solicite um novo link de redefinição de senha.";
    $msgErro->exibirMensagemErro($mensagem, "");
    exit;
}

// Debug extra para analisar formato de data no SQL Server
try {
    $debug = $pdo->query("SELECT GETDATE() AS current_server_time")->fetch(PDO::FETCH_ASSOC);
    error_log("Data atual no servidor SQL: " . $debug['current_server_time']);
    
    if (isset($user['dt_expiracao_token'])) {
        error_log("Formato da data de expiração: " . gettype($user['dt_expiracao_token']) . " - Valor: " . $user['dt_expiracao_token']);
    }
} catch (Exception $e) {
    error_log("Erro ao obter debug SQL: " . $e->getMessage());
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
        }
        
        .success {
            background-color: #4CAF50;
            color: white;
            display: block;
        }
        
        .error {
            background-color: #f44336;
            color: white;
            display: block;
        }
    </style>
</head>
<body>
    <div class="password-reset-container">
        <h1>Redefinir Senha</h1>
        
        <div id="message" class="message" style="display: none;"></div>
        
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
            
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn-submit">REDEFINIR SENHA</button>
            <a href="../php/index.php" class="btn-return">VOLTAR</a>
        </form>
    </div>

    <script>
        // Proteção contra alteração de campos pelo F12
        const originalFormHTML = document.getElementById('formRecupSenha').innerHTML;
        
        // Verificar se campos obrigatórios foram alterados
        function verificarIntegridade() {
            const senha = document.getElementById('senha');
            const confirmaSenha = document.getElementById('confirmaSenha');
            
            // Verificar se os tipos dos inputs foram alterados
            if (senha.type !== 'password' || confirmaSenha.type !== 'password') {
                alert('Tentativa de alteração detectada! A página será recarregada.');
                location.reload();
                return false;
            }
            
            // Verificar se maxlength foi alterado
            if (senha.maxLength !== 15 || confirmaSenha.maxLength !== 15) {
                alert('Tentativa de alteração detectada! A página será recarregada.');
                location.reload();
                return false;
            }
            
            // Verificar se required foi removido
            if (!senha.required || !confirmaSenha.required) {
                alert('Tentativa de alteração detectada! A página será recarregada.');
                location.reload();
                return false;
            }
            
            return true;
        }
        
        // Verificar integridade antes do envio
        document.getElementById('formRecupSenha').addEventListener('submit', function(e) {
            if (!verificarIntegridade()) {
                e.preventDefault();
                return false;
            }
        });
        
        // Monitorar mudanças no DOM
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes') {
                    const target = mutation.target;
                    if (target.id === 'senha' || target.id === 'confirmaSenha') {
                        if (!verificarIntegridade()) {
                            return;
                        }
                    }
                }
            });
        });
        
        // Observar mudanças nos campos
        observer.observe(document.getElementById('senha'), { attributes: true });
        observer.observe(document.getElementById('confirmaSenha'), { attributes: true });
        
        // Verificar periodicamente
        setInterval(verificarIntegridade, 2000);
    </script>

    <?php
    // Verificação se é uma requisição POST válida
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Verificação de dados POST
        if (!isset($_POST) || empty($_POST)) {
            echo "<script>
                document.getElementById('message').innerHTML = 'Dados não recebidos corretamente!';
                document.getElementById('message').className = 'message error';
                document.getElementById('message').style.display = 'block';
            </script>";
            exit;
        }
        
        // SANITIZAÇÃO E VALIDAÇÃO DOS DADOS DE ENTRADA
        $senha = sanitizar_input($_POST['senha'] ?? null, 'password');
        $confirmaSenha = sanitizar_input($_POST['confirmaSenha'] ?? null, 'password');
        $token_post = sanitizar_input($_POST['token'] ?? null, 'token');
        
        $ip_reset = get_client_ip();
        
        // VALIDAÇÕES RIGOROSAS (proteção contra F12)
        if ($senha === false || empty($senha)) {
            echo "<script>
                document.getElementById('message').innerHTML = 'Senha inválida! Deve ter entre 6 e 15 caracteres.';
                document.getElementById('message').className = 'message error';
                document.getElementById('message').style.display = 'block';
            </script>";
            exit;
        }
        
        if ($confirmaSenha === false || empty($confirmaSenha)) {
            echo "<script>
                document.getElementById('message').innerHTML = 'Confirmação de senha inválida! Deve ter entre 6 e 15 caracteres.';
                document.getElementById('message').className = 'message error';
                document.getElementById('message').style.display = 'block';
            </script>";
            exit;
        }
        
        if ($token_post === false || empty($token_post)) {
            echo "<script>
                document.getElementById('message').innerHTML = 'Token inválido!';
                document.getElementById('message').className = 'message error';
                document.getElementById('message').style.display = 'block';
            </script>";
            exit;
        }
        
        // Verificar se os tokens coincidem (proteção adicional)
        if ($token_post !== $token) {
            error_log("Tentativa de ataque: Token POST diferente do GET - IP: " . $ip_reset);
            echo "<script>
                document.getElementById('message').innerHTML = 'Token inválido!';
                document.getElementById('message').className = 'message error';
                document.getElementById('message').style.display = 'block';
            </script>";
            exit;
        }

        if ($senha !== $confirmaSenha) {
            echo "<script>
                document.getElementById('message').innerHTML = 'As senhas não coincidem!';
                document.getElementById('message').className = 'message error';
                document.getElementById('message').style.display = 'block';
            </script>";
            exit;
        }
        
        try {
            // Log da tentativa de redefinição
            error_log("Tentativa de redefinição de senha - Token: " . $token_hash . " - IP: " . $ip_reset);
            
            // Verificar novamente se o token ainda é válido (proteção contra ataques de timing)
            $verify_sql = "SELECT COUNT(*) FROM Usuario 
                          WHERE token_rec_senha = :token_hash 
                          AND (dt_expiracao_token > GETDATE() OR timestamp_expiracao > :current_timestamp)";
            $verify_stmt = $pdo->prepare($verify_sql);
            $verify_stmt->bindValue(":token_hash", $token_hash, PDO::PARAM_STR);
            $verify_stmt->bindValue(":current_timestamp", time(), PDO::PARAM_INT);
            $verify_stmt->execute();
            
            if ($verify_stmt->fetchColumn() == 0) {
                error_log("Token expirado durante processamento - Token: " . $token_hash);
                echo "<script>
                    document.getElementById('message').innerHTML = 'Token expirado. Solicite um novo link.';
                    document.getElementById('message').className = 'message error';
                    document.getElementById('message').style.display = 'block';
                </script>";
                exit;
            }
            
            // Criptografar a nova senha usando a mesma função do cadastro
            $senhaCriptografada = criptografarSenha($pdo, $senha);
            
            // Atualizar a tabela Usuario - limpar todos os campos relacionados ao token
            $exec5 = $pdo->prepare("UPDATE Usuario 
                                   SET senha = :senha, 
                                       token_rec_senha = NULL, 
                                       dt_expiracao_token = NULL,
                                       timestamp_expiracao = NULL,
                                       ultimo_acesso = GETDATE()
                                   WHERE token_rec_senha = :token_hash");
            $exec5->bindValue(":token_hash", $token_hash, PDO::PARAM_STR);
            $exec5->bindParam(":senha", $senhaCriptografada, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
            
            if ($exec5->execute()) {
                // Verificar se a atualização afetou alguma linha
                if ($exec5->rowCount() > 0) {
                    error_log("Senha redefinida com sucesso para token: " . $token_hash . " - IP: " . $ip_reset);
                    
                    // Limpeza da senha da memória (boa prática de segurança)
                    $senha = null;
                    $confirmaSenha = null;
                    $senhaCriptografada = null;
                    unset($senha, $confirmaSenha, $senhaCriptografada);
                    
                    echo "<script>
                        document.getElementById('message').innerHTML = 'Senha redefinida com sucesso! Redirecionando...';
                        document.getElementById('message').className = 'message success';
                        document.getElementById('message').style.display = 'block';
                        setTimeout(function() {
                            window.location.href = '../php/index.php';
                        }, 3000);
                    </script>";
                } else {
                    error_log("Nenhuma linha afetada ao tentar redefinir senha para token: " . $token_hash);
                    echo "<script>
                        document.getElementById('message').innerHTML = 'Não foi possível atualizar a senha. Por favor, tente novamente.';
                        document.getElementById('message').className = 'message error';
                        document.getElementById('message').style.display = 'block';
                    </script>";
                }
            } else {
                error_log("Erro ao executar atualização: " . print_r($exec5->errorInfo(), true));
                echo "<script>
                    document.getElementById('message').innerHTML = 'Erro ao atualizar senha. Por favor, tente novamente.';
                    document.getElementById('message').className = 'message error';
                    document.getElementById('message').style.display = 'block';
                </script>";
            }
        } catch (Exception $e) {
            error_log("Exceção ao atualizar senha: " . $e->getMessage() . " - IP: " . $ip_reset);
            
            // Limpeza em caso de erro
            $senha = null;
            $confirmaSenha = null;
            unset($senha, $confirmaSenha);
            
            echo "<script>
                document.getElementById('message').innerHTML = 'Erro interno do servidor. Tente novamente mais tarde.';
                document.getElementById('message').className = 'message error';
                document.getElementById('message').style.display = 'block';
            </script>";
        }
    }
    ?>
</body>
</html>