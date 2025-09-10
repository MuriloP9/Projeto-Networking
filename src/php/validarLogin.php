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

// Função para determinar nível de acesso
function getNivelAcessoNome($nivel) {
    switch($nivel) {
        case 0: return 'Administrador';
        case 1: return 'Gerente';
        case 2: return 'Supervisor';
        default: return 'Funcionário';
    }
}

// Função para verificar se é funcionário
function isFuncionario($email) {
    // Lista de domínios ou padrões que identificam funcionários
    $dominios_funcionarios = ['@empresa.com', '@admin.com'];
    
    foreach($dominios_funcionarios as $dominio) {
        if(strpos($email, $dominio) !== false) {
            return true;
        }
    }
    return false;
}

// SANITIZAÇÃO E VALIDAÇÃO DOS DADOS DE ENTRADA
$email = sanitizar_input($_POST['email'] ?? null, 'email');
$senha = sanitizar_input($_POST['senha'] ?? null, 'password');
$tipo_login = sanitizar_input($_POST['tipo_login'] ?? 'usuario', 'string'); // 'usuario' ou 'funcionario'

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
    error_log("Tentativa de login - Email: " . $email . " - Tipo: " . $tipo_login . " - IP: " . $ip_login);
    
    // ABORDAGEM COMPATÍVEL - Não altera sua stored procedure original
    
    // Primeiro, verifica se é funcionário
    $sql_check_funcionario = $pdo->prepare("
        SELECT COUNT(*) as eh_funcionario 
        FROM Funcionario 
        WHERE email = :email AND ativo = 1
    ");
    $sql_check_funcionario->bindValue(":email", $email);
    $sql_check_funcionario->execute();
    
    $check_result = $sql_check_funcionario->fetch(PDO::FETCH_ASSOC);
    $eh_funcionario = $check_result['eh_funcionario'] > 0;
    
    if ($eh_funcionario || $tipo_login === 'funcionario') {
        
        // ===== LOGIN DE FUNCIONÁRIO =====
        
        $sql_funcionario = $pdo->prepare("
            SELECT 
                id_funcionario,
                nome_completo,
                email,
                senha,
                nivel_acesso,
                ativo,
                ultimo_acesso
            FROM Funcionario 
            WHERE email = :email AND ativo = 1
        ");
        $sql_funcionario->bindValue(":email", $email);
        $sql_funcionario->execute();
        
        $funcionario = $sql_funcionario->fetch(PDO::FETCH_ASSOC);
        
        if ($funcionario) {
            // Verificar senha (adapte conforme seu método de hash)
            $senha_valida = false;
            
            // Opção 1: Senha simples (para testes - NÃO usar em produção)
            if ($funcionario['senha'] === $senha) {
                $senha_valida = true;
            }
            // Opção 2: Password hash do PHP
            elseif (password_verify($senha, $funcionario['senha'])) {
                $senha_valida = true;
            }
            // Opção 3: Hash customizado (adapte conforme necessário)
            elseif (hash('sha256', $senha . 'salt') === $funcionario['senha']) {
                $senha_valida = true;
            }
           if ($senha_valida) {
    // Atualizar último acesso
    $update_acesso = $pdo->prepare("
        UPDATE Funcionario 
        SET ultimo_acesso = GETDATE() 
        WHERE id_funcionario = :id
    ");
    $update_acesso->bindValue(":id", $funcionario['id_funcionario']);
    $update_acesso->execute();
    
    // Criar sessão de funcionário
    $_SESSION['usuario_logado'] = true;
    $_SESSION['tipo_usuario'] = 'funcionario';
    $_SESSION['nome_usuario'] = $funcionario['nome_completo'];
    $_SESSION['id_funcionario'] = $funcionario['id_funcionario'];
    $_SESSION['email_usuario'] = $funcionario['email'];
    $_SESSION['nivel_acesso'] = $funcionario['nivel_acesso'];
    $_SESSION['nivel_acesso_nome'] = getNivelAcessoNome($funcionario['nivel_acesso']);
    
    // Log de sucesso
    error_log("Login funcionário bem-sucedido - Email: " . $email . " - Nível: " . $funcionario['nivel_acesso'] . " - IP: " . $ip_login);
    
    // Limpeza da senha da memória
    $senha = null;
    unset($senha);
    
    echo json_encode([
        'sucesso' => true, 
        'tipo' => 'funcionario',
        'nivel_acesso' => $funcionario['nivel_acesso'],
        'nivel_nome' => getNivelAcessoNome($funcionario['nivel_acesso']),
        'mensagem' => 'Login realizado com sucesso! Bem-vindo(a), ' . $funcionario['nome_completo'],
        'redirect' => '../php/dashboard.php' // Funcionários vão para o dashboard
    ]);
            
        } else {
                error_log("Login funcionário falhou - Senha incorreta - Email: " . $email . " - IP: " . $ip_login);
                
                $senha = null;
                unset($senha);
                
                echo json_encode([
                    'sucesso' => false, 
                    'mensagem' => 'Email ou senha incorretos para funcionário.'
                ]);
            }
        } else {
            error_log("Login funcionário falhou - Funcionário não encontrado - Email: " . $email . " - IP: " . $ip_login);
            
            $senha = null;
            unset($senha);
            
            echo json_encode([
                'sucesso' => false, 
                'mensagem' => 'Funcionário não encontrado ou inativo.'
            ]);
        }
        
    } else {
        
        // ===== LOGIN DE USUÁRIO NORMAL (SUA STORED PROCEDURE ORIGINAL) =====
        
        $sql = $pdo->prepare("EXEC sp_ValidarLogin2 :email, :senha");
        $sql->bindValue(":email", $email);
        $sql->bindValue(":senha", $senha);
        $sql->execute();
        
        $resultado = $sql->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado && $resultado['sucesso'] == 1) {
    // Login de usuário normal bem-sucedido
    $_SESSION['usuario_logado'] = true;
    $_SESSION['tipo_usuario'] = 'usuario';
    $_SESSION['nome_usuario'] = $resultado['nome'];
    $_SESSION['id_usuario'] = $resultado['id_usuario'];
    
    // Adicionar id_perfil apenas se existir no resultado
    if (isset($resultado['id_perfil'])) {
        $_SESSION['id_perfil'] = $resultado['id_perfil'];
    }
    
    // Log de sucesso
    error_log("Login usuário bem-sucedido - Email: " . $email . " - IP: " . $ip_login);
    
    // Limpeza da senha da memória
    $senha = null;
    unset($senha);
    
    echo json_encode([
        'sucesso' => true,
        'tipo' => 'usuario',
        'mensagem' => $resultado['mensagem'],
        'redirect' => '../php/index.php' // Usuários comuns vão para o index
    ]);
        } else {
            // Login falhou
            error_log("Login usuário falhou - Email: " . $email . " - IP: " . $ip_login . " - Motivo: " . ($resultado['mensagem'] ?? 'Erro desconhecido'));
            
            // Limpeza da senha da memória
            $senha = null;
            unset($senha);
            
            echo json_encode([
                'sucesso' => false, 
                'mensagem' => $resultado['mensagem'] ?? 'Email ou senha incorretos.'
            ]);
        }
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