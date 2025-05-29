<?php
session_start();
include("../php/conexao.php");

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: ../pages/login.html");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$mensagem = '';

// Função para limpar e normalizar strings
function limpar($valor) {
    $valor = preg_replace('/[\x00-\x1F\x7F]/u', '', $valor);
    $valor = strip_tags(trim($valor));
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

// Função para validar tipos de dados
function validarTipo($valor, $tipo) {
    switch ($tipo) {
        case 'string':
            return is_string($valor);
        case 'int':
            return is_numeric($valor) && (int)$valor == $valor;
        case 'date':
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) && strtotime($valor);
        case 'telefone':
            return preg_match('/^[\d()\-\s+]{10,15}$/', $valor);
        case 'boolean':
            return is_bool($valor) || in_array($valor, ['0', '1', 0, 1, true, false], true);
        default:
            return false;
    }
}

// BUSCAR DADOS DO USUÁRIO
$usuario = null;
$nome = '';
$foto_perfil = null;

try {
    $pdo = conectar();
    
    // Buscar dados do usuário com LEFT JOIN para incluir dados do perfil
    $sql = $pdo->prepare("
        SELECT 
            u.id_usuario,
            u.nome,
            u.email,
            u.telefone,
            u.dataNascimento,
            u.foto_perfil,
            p.idade,
            p.endereco,
            p.formacao,
            p.experiencia_profissional,
            p.interesses,
            p.projetos_especializacoes,
            p.habilidades
        FROM Usuario u
        LEFT JOIN Perfil p ON u.id_usuario = p.id_usuario
        WHERE u.id_usuario = :id_usuario
    ");
    
    $sql->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
    $sql->execute();
    
    $resultado = $sql->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        $usuario = $resultado;
        $nome = $usuario['nome'];
        
        // Processar foto de perfil
        if ($usuario['foto_perfil']) {
            // Se a foto está armazenada como BLOB no banco
            $foto_perfil = 'data:image/jpeg;base64,' . base64_encode($usuario['foto_perfil']);
        } else {
            $foto_perfil = null; // Será usado a imagem padrão no HTML
        }
        
        // Garantir que campos opcionais tenham valores padrão
        $usuario['idade'] = $usuario['idade'] ?? null;
        $usuario['endereco'] = $usuario['endereco'] ?? '';
        $usuario['formacao'] = $usuario['formacao'] ?? '';
        $usuario['experiencia_profissional'] = $usuario['experiencia_profissional'] ?? '';
        $usuario['interesses'] = $usuario['interesses'] ?? '';
        $usuario['projetos_especializacoes'] = $usuario['projetos_especializacoes'] ?? '';
        $usuario['habilidades'] = $usuario['habilidades'] ?? '';
        $usuario['telefone'] = $usuario['telefone'] ?? '';
        $usuario['dataNascimento'] = $usuario['dataNascimento'] ?? '';
        
    } else {
        // Usuário não encontrado
        $mensagem = "Erro: Usuário não encontrado!";
        $usuario = [
            'nome' => '',
            'email' => '',
            'telefone' => '',
            'dataNascimento' => '',
            'idade' => null,
            'endereco' => '',
            'formacao' => '',
            'experiencia_profissional' => '',
            'interesses' => '',
            'projetos_especializacoes' => '',
            'habilidades' => ''
        ];
        $nome = 'Usuário não encontrado';
    }
    
} catch (Exception $e) {
    $mensagem = "Erro ao carregar dados do perfil: " . $e->getMessage();
    $usuario = [
        'nome' => '',
        'email' => '',
        'telefone' => '',
        'dataNascimento' => '',
        'idade' => null,
        'endereco' => '',
        'formacao' => '',
        'experiencia_profissional' => '',
        'interesses' => '',
        'projetos_especializacoes' => '',
        'habilidades' => ''
    ];
    $nome = 'Erro ao carregar';
}

// Processamento do formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = null;
    $transacao_iniciada = false;
    
    try {
        $pdo = conectar();
        
        // Validação rigorosa dos tipos de dados
        if (isset($_POST["nome"]) && !validarTipo($_POST["nome"], 'string')) {
            throw new Exception("Tipo inválido para o campo nome!");
        }
        $nome_novo = isset($_POST["nome"]) ? mb_convert_encoding(limpar($_POST["nome"]), 'UTF-8', 'auto') : null;

        if (isset($_POST["telefone"]) && !validarTipo($_POST["telefone"], 'telefone')) {
            throw new Exception("Tipo inválido para o campo telefone!");
        }
        $telefone_novo = isset($_POST["telefone"]) ? mb_convert_encoding(limpar($_POST["telefone"]), 'ASCII', 'auto') : null;

        if (isset($_POST["dataNascimento"]) && !validarTipo($_POST["dataNascimento"], 'date')) {
            throw new Exception("Tipo inválido para o campo data de nascimento!");
        }
        $dataNascimento_novo = isset($_POST["dataNascimento"]) ? trim($_POST["dataNascimento"]) : null;

        // Validações adicionais para os campos
        if (empty($nome_novo) || strlen($nome_novo) < 2 || strlen($nome_novo) > 100) {
            throw new Exception("Nome inválido! Deve ter entre 2 e 100 caracteres.");
        }

        if (empty($telefone_novo)) {
            throw new Exception("Telefone é obrigatório!");
        }

        if (empty($dataNascimento_novo)) {
            throw new Exception("Data de nascimento é obrigatória!");
        }

        // Validação da data
        $hoje = new DateTime();
        $nascimento = new DateTime($dataNascimento_novo);
        $idade_calculada = $hoje->diff($nascimento)->y;

        if ($nascimento > $hoje) {
            throw new Exception("Data de nascimento não pode ser futura!");
        }

        if ($idade_calculada < 18) {
            throw new Exception("Idade mínima de 18 anos é necessária!");
        }

        // Validação dos campos do perfil
        if (isset($_POST["idade"]) && !validarTipo($_POST["idade"], 'int')) {
            throw new Exception("Tipo inválido para o campo idade!");
        }
        $idade_nova = isset($_POST["idade"]) ? filter_var($_POST["idade"], FILTER_VALIDATE_INT) : null;

        if (isset($_POST["endereco"]) && !validarTipo($_POST["endereco"], 'string')) {
            throw new Exception("Tipo inválido para o campo endereço!");
        }
        $endereco_novo = isset($_POST["endereco"]) ? mb_convert_encoding(limpar($_POST["endereco"]), 'UTF-8', 'auto') : null;

        // Validação opcional da idade
        if ($idade_nova !== false && ($idade_nova < 18 || $idade_nova > 80)) {
            throw new Exception("Idade inválida! Deve estar entre 18 e 80 anos.");
        }

        // Validação dos demais campos do perfil
        $camposPerfil = [
            'formacao' => 'string',
            'experiencia_profissional' => 'string',
            'interesses' => 'string',
            'projetos_especializacoes' => 'string',
            'habilidades' => 'string'
        ];

        foreach ($camposPerfil as $campo => $tipo) {
            if (isset($_POST[$campo]) && !validarTipo($_POST[$campo], $tipo)) {
                throw new Exception("Tipo inválido para o campo {$campo}!");
            }
        }

        $formacao_nova = isset($_POST["formacao"]) ? mb_convert_encoding(limpar($_POST["formacao"]), 'UTF-8', 'auto') : null;
        $experiencia_profissional_nova = isset($_POST["experiencia_profissional"]) ? mb_convert_encoding(limpar($_POST["experiencia_profissional"]), 'UTF-8', 'auto') : null;
        $interesses_novos = isset($_POST["interesses"]) ? mb_convert_encoding(limpar($_POST["interesses"]), 'UTF-8', 'auto') : null;
        $projetos_especializacoes_novos = isset($_POST["projetos_especializacoes"]) ? mb_convert_encoding(limpar($_POST["projetos_especializacoes"]), 'UTF-8', 'auto') : null;
        $habilidades_novas = isset($_POST["habilidades"]) ? mb_convert_encoding(limpar($_POST["habilidades"]), 'UTF-8', 'auto') : null;

        // Validação do upload da foto
        $foto_perfil_nova = null;
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            // Verifica se é realmente um arquivo enviado
            if (!is_uploaded_file($_FILES['foto_perfil']['tmp_name'])) {
                throw new Exception("Possível ataque de upload de arquivo!");
            }

            // Validação do tipo MIME
            $mime = mime_content_type($_FILES['foto_perfil']['tmp_name']);
            $tiposPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            
            if (!array_key_exists($mime, $tiposPermitidos)) {
                throw new Exception("Formato de imagem inválido! Apenas JPEG, PNG ou GIF são permitidos.");
            }

            // Verifica tamanho máximo (5MB)
            if ($_FILES['foto_perfil']['size'] > 5 * 1024 * 1024) {
                throw new Exception("A imagem deve ter no máximo 5MB!");
            }

            // Verificação adicional da imagem
            $info = getimagesize($_FILES['foto_perfil']['tmp_name']);
            if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF])) {
                throw new Exception("Arquivo não é uma imagem válida!");
            }

            $foto_temp = $_FILES['foto_perfil']['tmp_name'];
            $foto_perfil_nova = file_get_contents($foto_temp);
        }

        // AGORA iniciar a transação após todas as validações
        $pdo->beginTransaction();
        $transacao_iniciada = true;
        
        // Atualiza os dados básicos na tabela Usuario
        $sql = $pdo->prepare("UPDATE Usuario SET nome = :nome, telefone = :telefone, dataNascimento = :dataNascimento WHERE id_usuario = :id_usuario");
        $sql->bindValue(":nome", $nome_novo, PDO::PARAM_STR);
        $sql->bindValue(":telefone", $telefone_novo, PDO::PARAM_STR);
        $sql->bindValue(":dataNascimento", $dataNascimento_novo, PDO::PARAM_STR);
        $sql->bindValue(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $sql->execute();
        
        // Processamento da foto de perfil
        if (isset($foto_perfil_nova)) {
            $sql_foto = $pdo->prepare("UPDATE Usuario SET foto_perfil = CONVERT(VARBINARY(MAX), :foto) WHERE id_usuario = :id");
            $sql_foto->bindParam(":foto", $foto_perfil_nova, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
            $sql_foto->bindValue(":id", $id_usuario, PDO::PARAM_INT);
            $sql_foto->execute();
        }
        
        // Verifica se já existe um perfil para o usuário
        $sql = $pdo->prepare("SELECT COUNT(*) FROM Perfil WHERE id_usuario = :id_usuario");
        $sql->bindValue(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $sql->execute();
        $existe_perfil = $sql->fetchColumn();
        
        if ($existe_perfil > 0) {
            $sql = $pdo->prepare("
                UPDATE Perfil SET 
                    idade = :idade,
                    endereco = :endereco,
                    formacao = :formacao,
                    experiencia_profissional = :experiencia_profissional,
                    interesses = :interesses,
                    projetos_especializacoes = :projetos_especializacoes,
                    habilidades = :habilidades
                WHERE id_usuario = :id_usuario
            ");
        } else {
            $sql = $pdo->prepare("
                INSERT INTO Perfil (
                    id_usuario, idade, endereco, formacao, 
                    experiencia_profissional, interesses, 
                    projetos_especializacoes, habilidades
                ) VALUES (
                    :id_usuario, :idade, :endereco, :formacao, 
                    :experiencia_profissional, :interesses, 
                    :projetos_especializacoes, :habilidades
                )
            ");
        }
        
        // Vincula os parâmetros do perfil
        $sql->bindValue(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $sql->bindValue(":idade", $idade_nova, PDO::PARAM_INT);
        $sql->bindValue(":endereco", $endereco_novo, PDO::PARAM_STR);
        $sql->bindValue(":formacao", $formacao_nova, PDO::PARAM_STR);
        $sql->bindValue(":experiencia_profissional", $experiencia_profissional_nova, PDO::PARAM_STR);
        $sql->bindValue(":interesses", $interesses_novos, PDO::PARAM_STR);
        $sql->bindValue(":projetos_especializacoes", $projetos_especializacoes_novos, PDO::PARAM_STR);
        $sql->bindValue(":habilidades", $habilidades_novas, PDO::PARAM_STR);
        $sql->execute();
        
        $pdo->commit();
        $transacao_iniciada = false; // Transação foi finalizada com sucesso
        $mensagem = "Perfil atualizado com sucesso!";
        
        // Recarregar os dados do usuário após a atualização
        $usuario['nome'] = $nome_novo;
        $usuario['telefone'] = $telefone_novo;
        $usuario['dataNascimento'] = $dataNascimento_novo;
        $usuario['idade'] = $idade_nova;
        $usuario['endereco'] = $endereco_novo;
        $usuario['formacao'] = $formacao_nova;
        $usuario['experiencia_profissional'] = $experiencia_profissional_nova;
        $usuario['interesses'] = $interesses_novos;
        $usuario['projetos_especializacoes'] = $projetos_especializacoes_novos;
        $usuario['habilidades'] = $habilidades_novas;
        
        $nome = $nome_novo; // Atualizar variável global
        
        if (isset($foto_perfil_nova)) {
            $foto_perfil = 'data:image/jpeg;base64,' . base64_encode($foto_perfil_nova);
        }
        
    } catch (Exception $erro) {
        // Só faz rollback se a transação foi realmente iniciada
        if ($pdo && $transacao_iniciada) {
            try {
                $pdo->rollBack();
            } catch (PDOException $rollback_erro) {
                // Se houver erro no rollback, adiciona à mensagem
                $mensagem = "Erro ao atualizar perfil: " . $erro->getMessage() . " (Erro adicional no rollback: " . $rollback_erro->getMessage() . ")";
            }
        }
        
        if (empty($mensagem)) {
            $mensagem = "Erro ao atualizar perfil: " . $erro->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <title>Perfil - ProLink</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f4f7fb;
            color: #333;
            padding-top: 80px;
            line-height: 1.6;
            font-size: 16px;
        }

        header {
            background-color: #3b6ebb;
            color: white;
            padding: 1em 2em;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 0.4em 1em rgba(0, 0, 0, 0.1);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo-icon {
            width: 50px;
            height: auto;
            margin-right: 10px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
        }

        .menu {
            display: flex;
            gap: 1.5em;
        }

        .menu li {
            list-style: none;
        }

        .menu a {
            text-decoration: none;
            color: #0a0a0a;
            background-color: white;
            padding: 8px 16px;
            border-radius: 5px;
            display: inline-block;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .menu a:hover {
            background-color: #2e5ca8;
            color: white;
        }

        /* Estilos para o menu móvel */
        .mobile-menu {
            display: none;
            position: absolute;
            top: 80px;
            left: 0;
            width: 100%;
            background-color: #3b6ebb;
            padding: 15px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 999;
        }

        .mobile-menu.active {
            display: block;
        }

        .mobile-menu ul {
            list-style: none;
            padding: 0;
        }

        .mobile-menu li {
            margin: 10px 0;
        }

        .mobile-menu a {
            display: block;
            text-decoration: none;
            color: #0a0a0a;
            background-color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
        }

        .mobile-menu a:hover {
            background-color: #2e5ca8;
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .cabecalho {
            display: flex;
            align-items: center;
            background-color: #3b6ebb;
            color: white;
            border-radius: 1em;
            padding: 1.5em;
            margin: 2em auto;
            max-width: 960px;
            box-shadow: 0 0.4em 0.8em rgba(0, 0, 0, 0.1);
        }

        .box-imagem {
            background-color: #3b6ebb;
            border-radius: 50%;
            padding: 10px;
            margin-right: 1.5em;
            width: 110px;
            height: 110px;
            min-width: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .perfil-imagem {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid white;
        }

        .info-usuario {
            flex-grow: 1;
        }

        .info-usuario h1 {
            font-size: 1.8em;
            margin-bottom: 0.3em;
        }

        .info-usuario p {
            margin-bottom: 0.8em;
            font-size: 1.1em;
        }

        .btn-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 0.8em;
        }

        .detalhes,
        .projetos,
        .caixa-central {
            background-color: #fff;
            margin: 1.5em auto;
            padding: 1.8em;
            border-radius: 0.8em;
            max-width: 960px;
            box-shadow: 0 0.4em 1em rgba(0, 0, 0, 0.1);
        }

        .detalhes h2,
        .projetos h2,
        .caixa-central h2 {
            font-size: 1.6em;
            margin-bottom: 1em;
            color: #3b6ebb;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5em;
        }

        .detalhes div {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5em;
            margin-bottom: 1em;
            border-bottom: 1px solid #f5f5f5;
            padding-bottom: 0.8em;
        }

        .detalhes strong {
            min-width: 200px;
            font-weight: 600;
        }

        .detalhes p {
            flex: 1;
            margin: 0;
        }

        .projetos .conteudo {
            display: flex;
            align-items: flex-start;
            gap: 2em;
        }

        .projetos .texto-conteudo {
            flex: 1;
        }

        .imagem-projeto-perfil {
            max-width: 180px;
            width: 25%;
            height: auto;
            margin-left: auto;
        }

        .projetos ul, .caixa-central ul {
            list-style-type: none;
            padding-left: 0;
            margin-bottom: 1em;
        }
        
        .projetos li, .caixa-central li {
            margin-bottom: 0.8em;
            line-height: 1.6;
        }

        .footer-section {
            background-color: #2e2e2e;
            color: white;
            padding: 1.2em 0;
            text-align: center;
            margin-top: 2em;
        }

        .footer-content {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-logo {
            width: 40px;
            height: 40px;
        }

        /* Estilos para o modal de edição */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 25px;
            border-radius: 8px;
            width: 90%;
            max-width: 700px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            max-height: 90vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
        }

        .close:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #3b6ebb;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #3b6ebb;
            outline: none;
            box-shadow: 0 0 5px rgba(59, 110, 187, 0.3);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            background-color: #3b6ebb;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
            display: inline-block;
        }

        .btn:hover {
            background-color: #2e5ca8;
        }

        .btn-editar {
            background-color: #3b6ebb;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-editar:hover {
            background-color: #2e5ca8;
        }

        .btn-gerar-pdf {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-gerar-pdf:hover {
            background-color: #c0392b;
        }

        .mensagem {
            padding: 15px;
            margin: 20px auto;
            border-radius: 4px;
            text-align: center;
            max-width: 960px;
            animation: fadeOut 5s forwards;
            position: relative;
        }

        @keyframes fadeOut {
            0% { opacity: 1; }
            70% { opacity: 1; }
            100% { opacity: 0; }
        }

        .sucesso {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .erro {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .hamburger-menu {
            display: none;
            flex-direction: column;
            cursor: pointer;
            margin-left: auto;
        }

        .hamburger-menu span {
            display: block;
            width: 25px;
            height: 3px;
            background-color: white;
            margin: 3px 0;
            transition: 0.3s;
        }

        /* Responsividade */
        @media (max-width: 1200px) {
            .cabecalho, 
            .detalhes,
            .projetos,
            .caixa-central,
            .mensagem {
                width: 90%;
                max-width: 90%;
            }

            .info-usuario h1 {
                font-size: 1.6em;
            }
        }

        @media (max-width: 992px) {
            body {
                font-size: 15px;
            }
            
            .detalhes strong {
                min-width: 180px;
            }
            
            .imagem-projeto-perfil {
                max-width: 150px;
            }
        }

        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }
            
            header {
                padding: 0.8em 1.5em;
            }
            
            .navbar {
                padding: 0;
            }
            
            .menu {
                display: none;
            }
            
            .hamburger-menu {
                display: flex;
            }
            
            .mobile-menu {
                top: 70px; /* Ajustado para altura menor do header em mobile */
            }
            
            .cabecalho {
                flex-direction: column;
                text-align: center;
                padding: 1.2em;
            }
            
            .box-imagem {
                margin-right: 0;
                margin-bottom: 1em;
            }
            
            .info-usuario h1 {
                font-size: 1.5em;
            }
            
            .btn-container {
                justify-content: center;
            }
            
            .detalhes, 
            .projetos, 
            .caixa-central {
                padding: 1.2em;
            }
            
            .detalhes div {
                flex-direction: column;
                gap: 0.3em;
            }
            
            .detalhes strong {
                min-width: unset;
            }
            
            .projetos .conteudo {
                flex-direction: column;
            }
            
            .imagem-projeto-perfil {
                max-width: 200px;
                width: 80%;
                margin: 1em auto;
                display: block;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto 5% auto;
                padding: 20px;
            }
        }

        @media (max-width: 576px) {
            body {
                font-size: 14px;
            }
            
            .logo-icon {
                width: 40px;
            }
            
            .logo {
                font-size: 20px;
            }
            
            .cabecalho {
                margin: 1em auto;
            }
            
            .box-imagem {
                width: 90px;
                height: 90px;
                min-width: 90px;
            }
            
            .info-usuario h1 {
                font-size: 1.3em;
            }
            
            .detalhes h2,
            .projetos h2,
            .caixa-central h2 {
                font-size: 1.3em;
            }
            
            .btn-editar,
            .btn-gerar-pdf {
                padding: 7px 14px;
                font-size: 13px;
            }
            
            .form-group label {
                font-size: 14px;
            }
            
            .form-group input,
            .form-group textarea {
                padding: 8px 10px;
                font-size: 13px;
            }
            
            .btn {
                padding: 10px 15px;
                font-size: 14px;
            }
        }

        @media (max-width: 400px) {
            .box-imagem {
                width: 80px;
                height: 80px;
                min-width: 80px;
            }
            
            .info-usuario h1 {
                font-size: 1.2em;
            }
            
            .btn-container {
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }
            
            .btn-editar,
            .btn-gerar-pdf {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>
     <header>
        <nav class="navbar">
            <div class="logo-container">
                <img src="../assets/img/globo-mundial.png" alt="Logo" class="logo-icon">
                <div class="logo">ProLink</div>
            </div>
            <div class="hamburger-menu" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class="menu">
                <li><a href="../php/index.php">Home</a></li>
                <li><a href="#" class="btn-logout" onclick="logout(); return false;">Sair</a></li>
            </ul>
        </nav>
    </header>
    
    <!-- Menu Mobile - Adicionado aqui -->
    <div class="mobile-menu">
        <ul>
            <li><a href="../php/index.php">Home</a></li>
            <li><a href="#" class="btn-logout" onclick="logout(); return false;">Sair</a></li>
        </ul>
    </div>

    <?php if (!empty($mensagem)): ?>
        <div class="mensagem <?php echo strpos($mensagem, 'sucesso') !== false ? 'sucesso' : 'erro'; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
    <?php endif; ?>

    <div class="cabecalho">
        <div class="box-imagem">
            <?php if ($foto_perfil): ?>
                <img src="<?php echo $foto_perfil; ?>" alt="Foto de perfil" class="perfil-imagem">
            <?php else: ?>
                <img src="../assets/img/userp.jpg" alt="Avatar padrão" class="perfil-imagem">
            <?php endif; ?>
        </div>
        <div class="info-usuario">
            <h1>Perfil</h1>
            <p><?php echo htmlspecialchars($nome); ?></p>
            <button class="btn-editar" onclick="abrirModal()">Editar Perfil</button>
            <button class="btn-gerar-pdf" onclick="gerarPDF()">Gerar PDF</button>
        </div>
    </div>

    <div class="detalhes">
        <h2>Detalhes</h2>
        <div><strong>Nome:</strong>
            <p><?php echo htmlspecialchars($usuario['nome']); ?></p>
        </div>
        <div><strong>Idade:</strong>
            <p><?php echo $usuario['idade'] ?? 'Não informado'; ?></p>
        </div>
        <div><strong>Endereço:</strong>
            <p><?php echo htmlspecialchars($usuario['endereco']); ?></p>
        </div>
        <div><strong>Formação:</strong>
            <p><?php echo htmlspecialchars($usuario['formacao']); ?></p>
        </div>
        <div><strong>Experiência Profissional:</strong>
            <p><?php echo nl2br(htmlspecialchars($usuario['experiencia_profissional'])); ?></p>
        </div>
        <div><strong>Interesses:</strong>
            <p><?php echo nl2br(htmlspecialchars($usuario['interesses'])); ?></p>
        </div>
    </div>

    <div class="projetos">
        <h2>Projetos e Especializações</h2>
        <div class="conteudo">
            <ul>
                <li><?php echo nl2br(htmlspecialchars($usuario['projetos_especializacoes'])); ?></li>
            </ul>
            <img src="../assets/img/organizing-projects-animate.svg" class="imagem-projeto-perfil" alt="Projetos">
        </div>
    </div>

    <div class="caixa-central">
        <h2>Habilidades</h2>
        <ul>
            <li><?php echo nl2br(htmlspecialchars($usuario['habilidades'])); ?></li>
        </ul>
    </div>

    <div class="caixa-central">
        <h2>Contato</h2>
        <p><strong>E-mail:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
    </div>

    <!-- Modal de Edição -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModal()">&times;</span>
            <h2>Editar Perfil</h2>
            <form action="perfil.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="foto_perfil">Foto de Perfil:</label>
                    <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="idade">Idade:</label>
                    <input type="number" id="idade" name="idade" value="<?php echo htmlspecialchars($usuario['idade'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="dataNascimento">Data de Nascimento:</label>
                    <input type="date" id="dataNascimento" name="dataNascimento" value="<?php echo htmlspecialchars($usuario['dataNascimento'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="endereco">Endereço:</label>
                    <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($usuario['endereco']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="formacao">Formação:</label>
                    <input type="text" id="formacao" name="formacao" value="<?php echo htmlspecialchars($usuario['formacao']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="experiencia_profissional">Experiência Profissional:</label>
                    <textarea id="experiencia_profissional" name="experiencia_profissional"><?php echo htmlspecialchars($usuario['experiencia_profissional']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="interesses">Interesses:</label>
                    <textarea id="interesses" name="interesses"><?php echo htmlspecialchars($usuario['interesses']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="projetos_especializacoes">Projetos e Especializações:</label>
                    <textarea id="projetos_especializacoes" name="projetos_especializacoes"><?php echo htmlspecialchars($usuario['projetos_especializacoes']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="habilidades">Habilidades:</label>
                    <textarea id="habilidades" name="habilidades"><?php echo htmlspecialchars($usuario['habilidades']); ?></textarea>
                </div>
                
                <button type="submit" class="btn">Salvar Alterações</button>
            </form>
        </div>
    </div>

    <footer class="footer-section">
        <div class="footer-content">
            <img src="../assets/img/globo-mundial.png" alt="Logo da Empresa" class="footer-logo">
            <p>&copy; 2024 ProLink. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
    
    function protegerInputsModal() {
        
        const inputsModal = {
            'nome': 'text',
            'telefone': 'text',
            'dataNascimento': 'date',
            'idade': 'number',
            'endereco': 'text',
            'formacao': 'text',
            'experiencia_profissional': 'text',
            'interesses': 'text',
            'projetos_especializacoes': 'text',
            'habilidades': 'text',
            'foto_perfil': 'file'
        };
        
        // Função para proteger um input específico
        function protegerInput(inputElement, tipoOriginal, nomeInput) {
            if (!inputElement) return;
            
            // Armazenar atributos originais
            const attributosOriginais = {
                type: tipoOriginal,
                name: inputElement.name,
                id: inputElement.id,
                required: inputElement.required,
                maxLength: inputElement.maxLength
            };
            
            // Monitorar mudanças nos atributos usando MutationObserver
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes') {
                        const attrName = mutation.attributeName;
                        
                        // Verificar se atributos críticos foram alterados
                        if (['type', 'name', 'id'].includes(attrName)) {
                            const valorAtual = inputElement.getAttribute(attrName);
                            const valorOriginal = attributosOriginais[attrName];
                            
                            if (valorAtual !== valorOriginal?.toString()) {
                                console.warn(`Tentativa de manipulação detectada no campo ${nomeInput}, atributo:`, attrName);
                                inputElement.setAttribute(attrName, valorOriginal);
                                
                                // Limpar o valor se houve tentativa de manipulação
                                if (tipoOriginal !== 'file') {
                                    inputElement.value = '';
                                }
                                
                                // Mostrar aviso visual
                                mostrarAvisoSegurancaModal(nomeInput);
                            }
                        }
                    }
                });
            });
            
            // Observar mudanças nos atributos críticos
            observer.observe(inputElement, {
                attributes: true,
                attributeFilter: ['type', 'name', 'id', 'required', 'maxlength']
            });
            
            // Proteção contra alteração via JavaScript console
            try {
                Object.defineProperty(inputElement, 'type', {
                    get: function() { return tipoOriginal; },
                    set: function(value) {
                        if (value !== tipoOriginal) {
                            console.warn(`Tentativa de alteração de tipo bloqueada no campo ${nomeInput}`);
                            mostrarAvisoSegurancaModal(nomeInput);
                            return tipoOriginal;
                        }
                        return tipoOriginal;
                    },
                    configurable: false
                });
            } catch (e) {
                // Fallback se não conseguir definir a propriedade
                console.warn('Não foi possível proteger a propriedade type via Object.defineProperty');
            }
            
            // Validação adicional no evento de input
            inputElement.addEventListener('input', function(e) {
                // Verificar se o tipo foi alterado
                if (this.type !== tipoOriginal) {
                    this.type = tipoOriginal;
                    if (tipoOriginal !== 'file') {
                        this.value = '';
                    }
                    mostrarAvisoSegurancaModal(nomeInput);
                    e.preventDefault();
                    return false;
                }
                
                // Validação do conteúdo baseado no tipo
                validarConteudoPorTipoModal(this, tipoOriginal);
            });
            
            // Verificação periódica adicional (backup)
            setInterval(function() {
                if (inputElement.type !== tipoOriginal) {
                    inputElement.type = tipoOriginal;
                    if (tipoOriginal !== 'file') {
                        inputElement.value = '';
                    }
                    mostrarAvisoSegurancaModal(nomeInput);
                }
            }, 2000);
        }
        
        // Aplicar proteção a todos os inputs do modal
        Object.keys(inputsModal).forEach(nomeInput => {
            const inputElement = document.querySelector(`#modalEditar input[name="${nomeInput}"], #modalEditar textarea[name="${nomeInput}"]`);
            if (inputElement) {
                protegerInput(inputElement, inputsModal[nomeInput], nomeInput);
            }
        });
        
        // Proteção adicional no formulário de edição
        const formModal = document.querySelector('#modalEditar form');
        if (formModal) {
            formModal.addEventListener('submit', function(e) {
                let manipulacaoDetectada = false;
                
                Object.keys(inputsModal).forEach(nomeInput => {
                    const inputElement = document.querySelector(`#modalEditar input[name="${nomeInput}"], #modalEditar textarea[name="${nomeInput}"]`);
                    if (inputElement && inputElement.type !== inputsModal[nomeInput]) {
                        console.warn(`Tipo de input manipulado detectado no envio: ${nomeInput}`);
                        inputElement.type = inputsModal[nomeInput];
                        if (inputsModal[nomeInput] !== 'file') {
                            inputElement.value = '';
                        }
                        manipulacaoDetectada = true;
                    }
                });
                
                if (manipulacaoDetectada) {
                    e.preventDefault();
                    mostrarAvisoSegurancaModal('formulário');
                    return false;
                }
            });
        }
    }
    
    // Função para validar conteúdo baseado no tipo esperado do modal
    function validarConteudoPorTipoModal(input, tipoEsperado) {
        const valor = input.value;
        
        switch (tipoEsperado) {
            case 'text':
                // Para campos de texto, permitir caracteres seguros
                const regexTexto = /^[\w\sáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\-.,;:!?@#%&*()+=\/\\]*$/;
                if (!regexTexto.test(valor)) {
                    input.value = valor.replace(/[^\w\sáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\-.,;:!?@#%&*()+=\/\\]/g, '');
                }
                break;
                
            case 'text':
                // Para telefone, permitir apenas números, parênteses, hífen, espaço e +
                const regexTel = /^[\d()\-\s+]*$/;
                if (!regexTel.test(valor)) {
                    input.value = valor.replace(/[^\d()\-\s+]/g, '');
                }
                break;
                
            case 'number':
                // Para idade, permitir apenas números
                if (!/^\d*$/.test(valor)) {
                    input.value = valor.replace(/[^\d]/g, '');
                }
                break;
                
            case 'date':
                // Para data, o navegador já faz a validação básica
                break;
                
            case 'file':
                // Para arquivo, não há validação de conteúdo do valor
                break;
        }
        
        // Limitar tamanho máximo para campos de texto
        if (['text', 'text'].includes(tipoEsperado) && valor.length > 500) {
            input.value = valor.substring(0, 500);
        }
    }
    
    // Função para mostrar aviso de segurança específico do modal
    function mostrarAvisoSegurancaModal(campo) {
        // Remove avisos anteriores
        const avisoAnterior = document.querySelector('.security-warning-modal');
        if (avisoAnterior) {
            avisoAnterior.remove();
        }
        
        // Criar elemento de aviso
        const aviso = document.createElement('div');
        aviso.className = 'security-warning-modal';
        aviso.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #ff4444;
            color: white;
            padding: 20px 25px;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            z-index: 20000;
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            max-width: 400px;
            text-align: center;
            animation: modalWarningShow 0.3s ease-out;
        `;
        
        aviso.innerHTML = `
            <strong>🔒 Alerta de Segurança</strong><br><br>
            Tentativa de manipulação detectada no campo: <strong>${campo}</strong><br>
            O formulário foi resetado por segurança.<br><br>
            <button onclick="this.parentElement.remove()" style="
                background: white;
                color: #ff4444;
                border: none;
                padding: 8px 16px;
                border-radius: 4px;
                cursor: pointer;
                font-weight: bold;
                margin-top: 10px;
            ">OK</button>
        `;
        
        // Adicionar CSS da animação se não existir
        if (!document.querySelector('#modal-security-warning-styles')) {
            const style = document.createElement('style');
            style.id = 'modal-security-warning-styles';
            style.textContent = `
                @keyframes modalWarningShow {
                    from { transform: translate(-50%, -50%) scale(0.7); opacity: 0; }
                    to { transform: translate(-50%, -50%) scale(1); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(aviso);
        
        // Remover aviso automaticamente após 8 segundos
        setTimeout(() => {
            if (aviso.parentNode) {
                aviso.style.animation = 'modalWarningShow 0.3s ease-out reverse';
                setTimeout(() => aviso.remove(), 300);
            }
        }, 8000);
    }

    // ===== FUNÇÕES ORIGINAIS DO MODAL =====
    // Funções para controlar o modal
    function abrirModal() {
        document.getElementById('modalEditar').style.display = 'block';
        // Inicializar a proteção dos inputs quando o modal for aberto
        setTimeout(protegerInputsModal, 100);
    }

    function fecharModal() {
        document.getElementById('modalEditar').style.display = 'none';
    }

    // Fechar o modal se clicar fora dele
    window.onclick = function(event) {
        const modal = document.getElementById('modalEditar');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // Gerar PDF
    function gerarPDF() {
        // Mostra um alerta enquanto processa
        alert("Gerando PDF... Isso pode levar alguns instantes.");
        
        // Envia uma requisição para o servidor gerar o PDF
        window.location.href = "../php/gerar_pdf.php";
    }

    // Função de logout
    function logout() {
        if(confirm('Tem certeza que deseja sair?')) {
            window.location.href = '../php/logout.php';
        }
    }

    // Função para alternar o menu móvel
    function toggleMobileMenu() {
        const mobileMenu = document.querySelector('.mobile-menu');
        const hamburger = document.querySelector('.hamburger-menu');
        
        // Alterna a classe 'active' no menu mobile
        mobileMenu.classList.toggle('active');
        
        // Transforma o ícone do hambúrguer em X quando o menu está aberto
        if (mobileMenu.classList.contains('active')) {
            hamburger.children[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
            hamburger.children[1].style.opacity = '0';
            hamburger.children[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
        } else {
            hamburger.children[0].style.transform = 'rotate(0) translate(0)';
            hamburger.children[1].style.opacity = '1';
            hamburger.children[2].style.transform = 'rotate(0) translate(0)';
        }
    }

    // Inicialização quando o DOM estiver carregado
    document.addEventListener('DOMContentLoaded', function() {
        // Fechar o menu ao clicar em um link
        document.querySelectorAll('.mobile-menu a').forEach(link => {
            link.addEventListener('click', (e) => {
                // Se não for o link de logout, fecha o menu automaticamente
                if (!link.classList.contains('btn-logout')) {
                    toggleMobileMenu();
                }
            });
        });
        
        // Fechar o menu ao clicar fora dele
        document.addEventListener('click', (event) => {
            const mobileMenu = document.querySelector('.mobile-menu');
            const hamburger = document.querySelector('.hamburger-menu');
            
            if (mobileMenu.classList.contains('active') &&
                !mobileMenu.contains(event.target) &&
                !hamburger.contains(event.target)) {
                toggleMobileMenu();
            }
        });
        
        // Inicializar proteção dos inputs do modal se o modal já estiver presente
        const modal = document.getElementById('modalEditar');
        if (modal) {
            // Aguardar um pouco para garantir que todos os elementos foram carregados
            setTimeout(protegerInputsModal, 500);
        }
    });
</script>
</body>
</html>