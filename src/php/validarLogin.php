<?php
date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'])) {
    header("Location: ../php/index.php");
    exit();
}

session_start();

include("../php/conexao.php");

$pdo = conectar();

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
        case 'email':
            // Sanitiza e valida email
            $email = filter_var(trim($valor), FILTER_SANITIZE_EMAIL);
            if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 100) {
                return $email;
            }
            return false;
            
        case 'password':
            // Remove apenas caracteres de controle, mantendo caracteres especiais para senhas
            $senha = preg_replace('/[\x00-\x1F\x7F]/u', '', $valor);
            if (strlen($senha) >= 1 && strlen($senha) <= 255) { // Ajustado para permitir senhas mais flexíveis
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

// SANITIZAÇÃO E VALIDAÇÃO DOS DADOS DE ENTRADA
$email = sanitizar_input($_POST['email'] ?? null, 'email');
$senha = sanitizar_input($_POST['senha'] ?? null, 'password');

$ip_login = get_client_ip();

// VALIDAÇÕES RIGOROSAS (proteção contra F12)
if ($email === false || empty($email)) {
    error_log("Tentativa de login com email inválido - IP: " . $ip_login);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Email inválido.']);
    exit;
}

if ($senha === false || empty($senha)) {
    error_log("Tentativa de login com senha inválida - IP: " . $ip_login);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Senha inválida.']);
    exit;
}

// Verificação adicional de formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error_log("Tentativa de login com formato de email inválido: " . $email . " - IP: " . $ip_login);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Email ou senha inválidos.']);
    exit;
}

try {
    // Log da tentativa de login
    error_log("Tentativa de login - Email: " . $email . " - IP: " . $ip_login);
    
    $sql = $pdo->prepare("EXEC sp_ValidarLogin :email, :senha");
    $sql->bindValue(":email", $email);
    $sql->bindValue(":senha", $senha);
    $sql->execute();
    
    $resultado = $sql->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado && $resultado['sucesso'] == 1) {
        // Login bem-sucedido
        $_SESSION['usuario_logado'] = true;
        $_SESSION['nome_usuario'] = $resultado['nome'];
        $_SESSION['id_usuario'] = $resultado['id_usuario'];
        $_SESSION['id_perfil'] = $resultado['id_perfil'];
        
        // Log de sucesso
        error_log("Login bem-sucedido - Email: " . $email . " - IP: " . $ip_login);
        
        // Limpeza da senha da memória (boa prática de segurança)
        $senha = null;
        unset($senha);
        
        echo json_encode([
            'sucesso' => true, 
            'mensagem' => $resultado['mensagem']
        ]);
    } else {
        // Login falhou
        error_log("Login falhou - Email: " . $email . " - IP: " . $ip_login . " - Motivo: " . ($resultado['mensagem'] ?? 'Erro desconhecido'));
        
        // Limpeza da senha da memória
        $senha = null;
        unset($senha);
        
        echo json_encode([
            'sucesso' => false, 
            'mensagem' => $resultado['mensagem'] ?? 'Email ou senha incorretos.'
        ]);
    }
    
} catch (Exception $erro) {
    error_log("Exceção durante login - Email: " . $email . " - IP: " . $ip_login . " - Erro: " . $erro->getMessage());
    
    // Limpeza em caso de erro
    $senha = null;
    unset($senha);
    
    echo json_encode([
        'sucesso' => false, 
        'mensagem' => 'Erro interno do servidor. Tente novamente mais tarde.'
    ]);
}
?>