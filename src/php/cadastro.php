<?php
session_start();

include("../php/conexao.php"); 

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
            // Remove espaços e aplica filtro de email
            $valor = trim($valor);
            $email_sanitizado = filter_var($valor, FILTER_SANITIZE_EMAIL);
            // Valida se é um email válido após sanitização
            if (filter_var($email_sanitizado, FILTER_VALIDATE_EMAIL)) {
                return $email_sanitizado;
            }
            return false;
            
        case 'int':
            // Sanitiza e valida inteiros
            $numero = filter_var($valor, FILTER_SANITIZE_NUMBER_INT);
            if (filter_var($numero, FILTER_VALIDATE_INT)) {
                return (int)$numero;
            }
            return false;
            
        case 'phone':
            // Remove tudo exceto números, parênteses, traços e espaços
            $telefone = preg_replace('/[^0-9()\-\s+]/', '', $valor);
            return trim($telefone);
            
        case 'date':
            // Valida formato de data
            $data = trim($valor);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
                $timestamp = strtotime($data);
                if ($timestamp !== false) {
                    return $data;
                }
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

// Função para criptografar a senha usando a procedure do banco
function criptografarSenha($pdo, $senhaTexto) {
    try {
        // Método alternativo: executar SQL diretamente para criptografar
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
        
        // Fallback: usar criptografia direta no SQL
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

// Verificação se é uma requisição POST válida
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "Método de requisição inválido!";
    exit;
}

// Verificação de CSRF (recomendado adicionar token CSRF no formulário)
if (!isset($_POST) || empty($_POST)) {
    echo "Dados não recebidos corretamente!";
    exit;
}

// SANITIZAÇÃO E VALIDAÇÃO DOS DADOS DE ENTRADA
$nome = sanitizar_input($_POST["nome"] ?? null, 'string');
$email = sanitizar_input($_POST["email"] ?? null, 'email');
$senha = isset($_POST["senha"]) ? trim($_POST["senha"]) : null;
$dataNascimento = sanitizar_input($_POST["dataNascimento"] ?? null, 'date');
$telefone = sanitizar_input($_POST["telefone"] ?? null, 'phone');
$aceitoLGPD = isset($_POST["aceitoLGPD"]) ? 1 : 0;

$idade = sanitizar_input($_POST["idade"] ?? null, 'int');
$endereco = sanitizar_input($_POST["endereco"] ?? null, 'string');
$formacao = sanitizar_input($_POST["formacao"] ?? null, 'string');
$experiencia_profissional = sanitizar_input($_POST["experiencia_profissional"] ?? null, 'string');
$interesses = sanitizar_input($_POST["interesses"] ?? null, 'string');
$projetos_especializacoes = sanitizar_input($_POST["projetos_especializacoes"] ?? null, 'string');
$habilidades = sanitizar_input($_POST["habilidades"] ?? null, 'string');

$ip_registro = get_client_ip();

// VALIDAÇÕES RIGOROSAS (proteção contra F12)
if (empty($nome) || strlen($nome) < 2 || strlen($nome) > 100) {
    echo "Nome inválido! Deve ter entre 2 e 100 caracteres.";
    exit;
}

if ($email === false || empty($email)) {
    echo "Email inválido! Verifique o formato do email.";
    exit;
}

if (empty($senha) || strlen($senha) < 6) {
    echo "Senha inválida! Deve ter pelo menos 6 caracteres.";
    exit;
}

if ($dataNascimento === false || empty($dataNascimento)) {
    echo "Data de nascimento inválida! Use o formato AAAA-MM-DD.";
    exit;
}

// Validar se a data não é futura e se a pessoa tem pelo menos 13 anos
$hoje = new DateTime();
$nascimento = new DateTime($dataNascimento);
$idade_calculada = $hoje->diff($nascimento)->y;

if ($nascimento > $hoje) {
    echo "Data de nascimento não pode ser futura!";
    exit;
}

if ($idade_calculada < 18) {
    echo "Idade mínima de 18 anos é necessária!";
    exit;
}

if (empty($telefone) || !preg_match('/^[\d()\-\s+]{10,15}$/', $telefone)) {
    echo "Telefone inválido! Deve conter entre 10 e 15 dígitos.";
    exit;
}

if (!$aceitoLGPD) {
    echo "Você deve aceitar os termos de política de privacidade e LGPD para se cadastrar!";
    exit;
}

// Validações opcionais com sanitização
if ($idade !== false && ($idade < 18 || $idade > 80)) {
    echo "Idade inválida! Deve estar entre 18 e 80 anos.";
    exit;
}

try {
    $pdo = conectar();
    
    // PREPARED STATEMENT para verificar email existente (proteção SQL Injection)
    $query = $pdo->prepare("SELECT COUNT(*) FROM Usuario WHERE email = :email");
    $query->bindValue(":email", $email, PDO::PARAM_STR);
    $query->execute();
    $count = $query->fetchColumn();

    if ($count > 0) {
        echo "Este email já está em uso!";
        exit;
    }
    
    // VALIDAÇÃO E SANITIZAÇÃO DE UPLOAD DE ARQUIVO
    $foto_perfil = null;
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        // Verificações de segurança para upload
        $mime = mime_content_type($_FILES['foto_perfil']['tmp_name']);
        $mimes_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($mime, $mimes_permitidos)) {
            echo "Formato de imagem inválido! Apenas JPEG, PNG ou GIF são permitidos.";
            exit;
        }
        
        if ($_FILES['foto_perfil']['size'] > 5 * 1024 * 1024) {
            echo "A imagem deve ter no máximo 5MB!";
            exit;
        }
        
        // Verificação adicional do nome do arquivo
        $nome_arquivo = $_FILES['foto_perfil']['name'];
        if (!preg_match('/^[a-zA-Z0-9._-]+\.(jpg|jpeg|png|gif)$/i', $nome_arquivo)) {
            echo "Nome do arquivo de imagem inválido!";
            exit;
        }
        
        $foto_temp = $_FILES['foto_perfil']['tmp_name'];
        $foto_perfil = file_get_contents($foto_temp);
    }

    // Geração de token QR (mantendo formato original)
    $token_qr = bin2hex(random_bytes(16));
    $link_qr = "https://seusite.com/perfil/" . $token_qr;
    
    include('phpqrcode/qrlib.php');
    
    $qr_dir = '../qrcodes/';
    if (!file_exists($qr_dir)) {
        mkdir($qr_dir, 0777, true);
    }
    
    $qr_file = $qr_dir . 'qr_' . $token_qr . '.png';
    QRcode::png($link_qr, $qr_file, QR_ECLEVEL_L, 10);
    $qr_code_path = 'qrcodes/qr_' . $token_qr . '.png';
    
    // TRANSAÇÃO COM PREPARED STATEMENTS
    $pdo->beginTransaction();
    
    // Criptografar a senha antes de inserir no banco
    $senhaCriptografada = criptografarSenha($pdo, $senha);
    
    // INSERT com PREPARED STATEMENT (proteção total contra SQL Injection)
    $sql = $pdo->prepare("INSERT INTO Usuario 
                (nome, email, senha, dataNascimento, telefone, qr_code, data_geracao_qr, statusLGPD, IP_registro, ultimo_acesso) 
                VALUES (:nome, :email, :senha, :dataNascimento, :telefone, :qr_code, GETDATE(), :statusLGPD, :IP_registro, GETDATE())");

    $sql->bindValue(":nome", $nome, PDO::PARAM_STR);
    $sql->bindValue(":email", $email, PDO::PARAM_STR);
    $sql->bindParam(":senha", $senhaCriptografada, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
    $sql->bindValue(":dataNascimento", $dataNascimento, PDO::PARAM_STR);
    $sql->bindValue(":telefone", $telefone, PDO::PARAM_STR);
    $sql->bindValue(":qr_code", $qr_code_path, PDO::PARAM_STR);
    $sql->bindValue(":statusLGPD", $aceitoLGPD, PDO::PARAM_INT);
    $sql->bindValue(":IP_registro", $ip_registro, PDO::PARAM_STR);
    
    $sql->execute();
    $id_usuario = $pdo->lastInsertId();

    // UPDATE da foto com PREPARED STATEMENT
    if ($foto_perfil) {
        $sql_foto = $pdo->prepare("UPDATE Usuario SET foto_perfil = CONVERT(VARBINARY(MAX), :foto) WHERE id_usuario = :id");
        $sql_foto->bindParam(":foto", $foto_perfil, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
        $sql_foto->bindValue(":id", $id_usuario, PDO::PARAM_INT);
        $sql_foto->execute();
    }

    // INSERT do perfil com PREPARED STATEMENT
    $sql_perfil = $pdo->prepare("
        INSERT INTO Perfil (id_usuario, idade, endereco, formacao, experiencia_profissional, 
                          interesses, projetos_especializacoes, habilidades)
        VALUES (:id_usuario, :idade, :endereco, :formacao, :experiencia_profissional, 
                :interesses, :projetos_especializacoes, :habilidades)
    ");
    
    $sql_perfil->bindValue(":id_usuario", $id_usuario, PDO::PARAM_INT);
    $sql_perfil->bindValue(":idade", $idade, PDO::PARAM_INT);
    $sql_perfil->bindValue(":endereco", $endereco, PDO::PARAM_STR);
    $sql_perfil->bindValue(":formacao", $formacao, PDO::PARAM_STR);
    $sql_perfil->bindValue(":experiencia_profissional", $experiencia_profissional, PDO::PARAM_STR);
    $sql_perfil->bindValue(":interesses", $interesses, PDO::PARAM_STR);
    $sql_perfil->bindValue(":projetos_especializacoes", $projetos_especializacoes, PDO::PARAM_STR);
    $sql_perfil->bindValue(":habilidades", $habilidades, PDO::PARAM_STR);
    
    $sql_perfil->execute();

    $pdo->commit();

    // Limpeza da senha da memória (boa prática de segurança)
    $senha = null;
    unset($senha);

    $_SESSION['cadastro_realizado'] = true;
    $_SESSION['qr_code'] = $qr_code_path;
    $_SESSION['link_qr'] = $link_qr;
    $_SESSION['mensagem_cadastro'] = "Cadastro realizado com sucesso! Agora faça login para acessar as vagas.";

    echo "ok";
    exit;
    
} catch (Exception $erro) {
    $pdo->rollBack();
    
    // Limpeza em caso de erro
    if (isset($qr_file) && file_exists($qr_file)) {
        unlink($qr_file);
    }
    
    // Log de erro sem expor informações sensíveis
    error_log("Erro no cadastro: " . $erro->getMessage() . "\n" . $erro->getTraceAsString());
    
    echo "Erro interno do servidor. Tente novamente mais tarde.";
    exit;
}
?>