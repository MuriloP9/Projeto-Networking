<?php
session_start();

// Função para conectar ao banco de dados
include("../php/conexao.php"); 

// Função para limpar e normalizar strings
function limpar($valor) {
    // Remove apenas caracteres de controle (0-31) e DEL (127)
    $valor = preg_replace('/[\x00-\x1F\x7F]/u', '', $valor);
    // Mantém acentos e caracteres especiais, apenas remove tags HTML e espaços extras
    $valor = strip_tags(trim($valor));
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');


// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Dados básicos do usuário (com tratamento especial de encoding)
    $nome = isset($_POST["nome"]) ? mb_convert_encoding(limpar($_POST["nome"]), 'UTF-8', 'auto') : null;
    $email = isset($_POST["email"]) ? filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL) : null;
    $senha = isset($_POST["senha"]) ? trim($_POST["senha"]) : null;
    $dataNascimento = isset($_POST["dataNascimento"]) ? trim($_POST["dataNascimento"]) : null;
    $telefone = isset($_POST["telefone"]) ? mb_convert_encoding(limpar($_POST["telefone"]), 'ASCII', 'auto') : null;
    
    // Dados do perfil (com tratamento de encoding)
    $idade = isset($_POST["idade"]) ? filter_var($_POST["idade"], FILTER_VALIDATE_INT) : null;
    $endereco = isset($_POST["endereco"]) ? mb_convert_encoding(limpar($_POST["endereco"]), 'UTF-8', 'auto') : null;
    $formacao = isset($_POST["formacao"]) ? mb_convert_encoding(limpar($_POST["formacao"]), 'UTF-8', 'auto') : null;
    $experiencia_profissional = isset($_POST["experiencia_profissional"]) ? mb_convert_encoding(limpar($_POST["experiencia_profissional"]), 'UTF-8', 'auto') : null;
    $interesses = isset($_POST["interesses"]) ? mb_convert_encoding(limpar($_POST["interesses"]), 'UTF-8', 'auto') : null;
    $projetos_especializacoes = isset($_POST["projetos_especializacoes"]) ? mb_convert_encoding(limpar($_POST["projetos_especializacoes"]), 'UTF-8', 'auto') : null;
    $habilidades = isset($_POST["habilidades"]) ? mb_convert_encoding(limpar($_POST["habilidades"]), 'UTF-8', 'auto') : null;

    // Validação dos campos obrigatórios
    if (!$nome || !$email || !$senha || !$dataNascimento || !$telefone) {
        echo "Todos os campos obrigatórios devem ser preenchidos!";
        exit;
    }

    // Conexão com o banco de dados
    $pdo = conectar();

    // Verifica se o email já existe
    $query = $pdo->prepare("SELECT COUNT(*) FROM Usuario WHERE email = :email");
    $query->bindValue(":email", $email, PDO::PARAM_STR);
    $query->execute();
    $count = $query->fetchColumn();

    if ($count > 0) {
        echo "Este email já está em uso!";
        exit;
    }
    
    // Processamento da foto de perfil (binário)
    $foto_perfil = null;
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        // Validação da imagem
        $mime = mime_content_type($_FILES['foto_perfil']['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
            echo "Formato de imagem inválido! Apenas JPEG, PNG ou GIF são permitidos.";
            exit;
        }
        
        // Verifica tamanho máximo (5MB)
        if ($_FILES['foto_perfil']['size'] > 5 * 1024 * 1024) {
            echo "A imagem deve ter no máximo 5MB!";
            exit;
        }
        
        $foto_temp = $_FILES['foto_perfil']['tmp_name'];
        $foto_perfil = file_get_contents($foto_temp);
    }

    // Geração do QR Code
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
        
        // 1. Primeiro insere os dados básicos sem a foto
        $sql = $pdo->prepare("INSERT INTO Usuario 
                            (nome, email, senha, dataNascimento, telefone, qr_code, data_geracao_qr) 
                            VALUES (:nome, :email, :senha, :dataNascimento, :telefone, :qr_code, GETDATE())");
        
        $sql->bindValue(":nome", $nome, PDO::PARAM_STR);
        $sql->bindValue(":email", $email, PDO::PARAM_STR);
        $sql->bindValue(":senha", $senha, PDO::PARAM_STR);
        $sql->bindValue(":dataNascimento", $dataNascimento, PDO::PARAM_STR);
        $sql->bindValue(":telefone", $telefone, PDO::PARAM_STR);
        $sql->bindValue(":qr_code", $qr_code_path, PDO::PARAM_STR);
        
        $sql->execute();
        $id_usuario = $pdo->lastInsertId();

        // 2. Se há imagem, atualiza separadamente com tratamento especial
        if ($foto_perfil) {
            $sql_foto = $pdo->prepare("UPDATE Usuario SET foto_perfil = CONVERT(VARBINARY(MAX), :foto) WHERE id_usuario = :id");
            
            // Método recomendado para SQL Server
            $sql_foto->bindParam(":foto", $foto_perfil, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
            $sql_foto->bindValue(":id", $id_usuario, PDO::PARAM_INT);
            $sql_foto->execute();
        }

        // 3. Inserir dados do perfil
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

        // Configuração da sessão
        $_SESSION['cadastro_realizado'] = true;
        $_SESSION['id_usuario'] = $id_usuario;
        $_SESSION['qr_code'] = $qr_code_path;
        $_SESSION['link_qr'] = $link_qr;

        echo "ok";
        exit;
        
    } catch (Exception $erro) {
        $pdo->rollBack();
        
        // Remove arquivos temporários em caso de erro
        if (file_exists($qr_file)) {
            unlink($qr_file);
        }
        
        // Mostra o erro real para diagnóstico
        echo "Erro ao cadastrar: " . $erro->getMessage();
        error_log("Erro no cadastro: " . $erro->getMessage() . "\n" . $erro->getTraceAsString());
        exit;
    }
}
?>