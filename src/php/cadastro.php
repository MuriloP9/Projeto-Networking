<?php
session_start();

// Função para conectar ao banco de dados
include("../php/conexao.php"); 
function limpar($valor) {
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Dados básicos do usuário
    $nome = isset($_POST["nome"]) ? limpar($_POST["nome"]) : null;
    $email = isset($_POST["email"]) ? filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL) : null;
    $senha = isset($_POST["senha"]) ? trim($_POST["senha"]) : null;
    $dataNascimento = isset($_POST["dataNascimento"]) ? trim($_POST["dataNascimento"]) : null;
    $telefone = isset($_POST["telefone"]) ? limpar($_POST["telefone"]) : null;
    
    // Dados do perfil
    $idade = isset($_POST["idade"]) ? filter_var($_POST["idade"], FILTER_VALIDATE_INT) : null;
    $endereco = isset($_POST["endereco"]) ? limpar($_POST["endereco"]) : null;
    $formacao = isset($_POST["formacao"]) ? limpar($_POST["formacao"]) : null;
    $experiencia_profissional = isset($_POST["experiencia_profissional"]) ? limpar($_POST["experiencia_profissional"]) : null;
    $interesses = isset($_POST["interesses"]) ? limpar($_POST["interesses"]) : null;
    $projetos_especializacoes = isset($_POST["projetos_especializacoes"]) ? limpar($_POST["projetos_especializacoes"]) : null;
    $habilidades = isset($_POST["habilidades"]) ? limpar($_POST["habilidades"]) : null;

    // Validação dos campos obrigatórios
    if (!$nome || !$email || !$senha || !$dataNascimento || !$telefone) {
        echo "Todos os campos obrigatórios devem ser preenchidos!";
        exit;
    }

    // Conexão com o banco de dados
    $pdo = conectar();

    // Verifica se o email já existe
    $query = $pdo->prepare("SELECT COUNT(*) FROM Usuario WHERE email = :email");
    $query->bindValue(":email", $email);
    $query->execute();
    $count = $query->fetchColumn();

    if ($count > 0) {
        echo "Este email já está em uso!";
        exit;
    }
    
    // Processamento da foto de perfil (se enviada)
    $foto_perfil = null;
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $foto_temp = $_FILES['foto_perfil']['tmp_name'];
        $foto_perfil = file_get_contents($foto_temp);
    }

    // Hash da senha para segurança
    //$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    
    // Geração do QR Code (vamos criar um link único para o perfil do usuário)
    $token_qr = bin2hex(random_bytes(16)); // Token único para o QR Code
    $link_qr = "https://seusite.com/perfil/" . $token_qr;
    
    // Você pode usar uma biblioteca como phpqrcode para gerar o QR code de fato
    // Inclua a biblioteca QRCode (baixe em: http://phpqrcode.sourceforge.net/)
    include('phpqrcode/qrlib.php');
    
    // Diretório para salvar os QR codes
    $qr_dir = '../qrcodes/';
    if (!file_exists($qr_dir)) {
        mkdir($qr_dir, 0777, true);
    }
    
    // Nome do arquivo QR code
    $qr_file = $qr_dir . 'qr_' . $token_qr . '.png';
    
    // Gerar QR code
    QRcode::png($link_qr, $qr_file, QR_ECLEVEL_L, 10);
    
    // Caminho relativo para salvar no banco
    $qr_code_path = 'qrcodes/qr_' . $token_qr . '.png';
    
    try {
        // Inserir dados na tabela Usuario
        $sql = $pdo->prepare("INSERT INTO Usuario 
                            (nome, email, senha, dataNascimento, telefone, qr_code, data_geracao_qr, foto_perfil) 
                            VALUES (:nome, :email, :senha, :dataNascimento, :telefone, :qr_code, GETDATE(), CONVERT(VARBINARY(MAX), :foto_perfil))");
        
        $sql->bindValue(":nome", $nome);
        $sql->bindValue(":email", $email);
        $sql->bindValue(":senha", $senha);
        $sql->bindValue(":dataNascimento", $dataNascimento);
        $sql->bindValue(":telefone", $telefone);
        $sql->bindValue(":qr_code", $qr_code_path);
        
        // Tratamento especial para a foto
        if ($foto_perfil) {
            $sql->bindValue(":foto_perfil", $foto_perfil, PDO::PARAM_LOB);
        } else {
            $sql->bindValue(":foto_perfil", null, PDO::PARAM_NULL);
        }
        
        $sql->execute();

        // Recupera o ID do usuário recém-cadastrado
        $id_usuario = $pdo->lastInsertId();

        // Define as variáveis de sessão
        $_SESSION['cadastro_realizado'] = true;
        $_SESSION['id_usuario'] = $id_usuario;
        $_SESSION['qr_code'] = $qr_code_path;
        $_SESSION['link_qr'] = $link_qr;

        // Inserir dados na tabela Perfil
        $sql_perfil = $pdo->prepare("
            INSERT INTO Perfil (id_usuario, idade, endereco, formacao, experiencia_profissional, 
                              interesses, projetos_especializacoes, habilidades)
            VALUES (:id_usuario, :idade, :endereco, :formacao, :experiencia_profissional, 
                    :interesses, :projetos_especializacoes, :habilidades)
        ");
        
        $sql_perfil->bindValue(":id_usuario", $id_usuario);
        $sql_perfil->bindValue(":idade", $idade);
        $sql_perfil->bindValue(":endereco", $endereco);
        $sql_perfil->bindValue(":formacao", $formacao);
        $sql_perfil->bindValue(":experiencia_profissional", $experiencia_profissional);
        $sql_perfil->bindValue(":interesses", $interesses);
        $sql_perfil->bindValue(":projetos_especializacoes", $projetos_especializacoes);
        $sql_perfil->bindValue(":habilidades", $habilidades);
        
        $sql_perfil->execute();

        echo "ok";
        exit;
        
    } catch (Exception $erro) {
        // Remove o arquivo QR code se houve erro
        if (file_exists($qr_file)) {
            unlink($qr_file);
        }
        
        echo "Erro ao cadastrar: " . $erro->getMessage();
        exit;
    }
}
?>