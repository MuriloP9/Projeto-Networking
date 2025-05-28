<?php
session_start();

include("../php/conexao.php"); 

function limpar($valor) {
    $valor = preg_replace('/[\x00-\x1F\x7F]/u', '', $valor);
    $valor = strip_tags(trim($valor));
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
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
    return $ipaddress;
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = isset($_POST["nome"]) ? mb_convert_encoding(limpar($_POST["nome"]), 'UTF-8', 'auto') : null;
    $email = isset($_POST["email"]) ? filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL) : null;
    $senha = isset($_POST["senha"]) ? trim($_POST["senha"]) : null;
    $dataNascimento = isset($_POST["dataNascimento"]) ? trim($_POST["dataNascimento"]) : null;
    $telefone = isset($_POST["telefone"]) ? mb_convert_encoding(limpar($_POST["telefone"]), 'ASCII', 'auto') : null;
    $aceitoLGPD = isset($_POST["aceitoLGPD"]) ? 1 : 0;
    
    $idade = isset($_POST["idade"]) ? filter_var($_POST["idade"], FILTER_VALIDATE_INT) : null;
    $endereco = isset($_POST["endereco"]) ? mb_convert_encoding(limpar($_POST["endereco"]), 'UTF-8', 'auto') : null;
    $formacao = isset($_POST["formacao"]) ? mb_convert_encoding(limpar($_POST["formacao"]), 'UTF-8', 'auto') : null;
    $experiencia_profissional = isset($_POST["experiencia_profissional"]) ? mb_convert_encoding(limpar($_POST["experiencia_profissional"]), 'UTF-8', 'auto') : null;
    $interesses = isset($_POST["interesses"]) ? mb_convert_encoding(limpar($_POST["interesses"]), 'UTF-8', 'auto') : null;
    $projetos_especializacoes = isset($_POST["projetos_especializacoes"]) ? mb_convert_encoding(limpar($_POST["projetos_especializacoes"]), 'UTF-8', 'auto') : null;
    $habilidades = isset($_POST["habilidades"]) ? mb_convert_encoding(limpar($_POST["habilidades"]), 'UTF-8', 'auto') : null;

    $ip_registro = get_client_ip();

    if (!$nome || !$email || !$senha || !$dataNascimento || !$telefone) {
        echo "Todos os campos obrigatórios devem ser preenchidos!";
        exit;
    }

    if (!$aceitoLGPD) {
        echo "Você deve aceitar os termos de política de privacidade e LGPD para se cadastrar!";
        exit;
    }

    $pdo = conectar();

    $query = $pdo->prepare("SELECT COUNT(*) FROM Usuario WHERE email = :email");
    $query->bindValue(":email", $email, PDO::PARAM_STR);
    $query->execute();
    $count = $query->fetchColumn();

    if ($count > 0) {
        echo "Este email já está em uso!";
        exit;
    }
    
    $foto_perfil = null;
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $mime = mime_content_type($_FILES['foto_perfil']['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
            echo "Formato de imagem inválido! Apenas JPEG, PNG ou GIF são permitidos.";
            exit;
        }
        
        if ($_FILES['foto_perfil']['size'] > 5 * 1024 * 1024) {
            echo "A imagem deve ter no máximo 5MB!";
            exit;
        }
        
        $foto_temp = $_FILES['foto_perfil']['tmp_name'];
        $foto_perfil = file_get_contents($foto_temp);
    }

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
    
    try {
        $pdo->beginTransaction();
        
        // Criptografar a senha antes de inserir no banco
        $senhaCriptografada = criptografarSenha($pdo, $senha);
        
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

        if ($foto_perfil) {
            $sql_foto = $pdo->prepare("UPDATE Usuario SET foto_perfil = CONVERT(VARBINARY(MAX), :foto) WHERE id_usuario = :id");
            $sql_foto->bindParam(":foto", $foto_perfil, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
            $sql_foto->bindValue(":id", $id_usuario, PDO::PARAM_INT);
            $sql_foto->execute();
        }

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

        $_SESSION['cadastro_realizado'] = true;
        $_SESSION['qr_code'] = $qr_code_path;
        $_SESSION['link_qr'] = $link_qr;
        $_SESSION['mensagem_cadastro'] = "Cadastro realizado com sucesso! Agora faça login para acessar as vagas.";

        echo "ok";
        exit;
        
    } catch (Exception $erro) {
        $pdo->rollBack();
        
        if (file_exists($qr_file)) {
            unlink($qr_file);
        }
        
        echo "Erro ao cadastrar: " . $erro->getMessage();
        error_log("Erro no cadastro: " . $erro->getMessage() . "\n" . $erro->getTraceAsString());
        exit;
    }
}
?>