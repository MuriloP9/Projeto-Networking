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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Perfil - ProLink</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --secondary: #64748b;
            --accent: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --background: #0f172a;
            --surface: #1e293b;
            --surface-light: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --border: #475569;
            --glass-bg: rgba(30, 41, 59, 0.8);
            --glass-border: rgba(148, 163, 184, 0.2);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--background);
            color: var(--text-primary);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Background Animated */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            z-index: -2;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(37, 99, 235, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(16, 185, 129, 0.05) 0%, transparent 50%);
            z-index: -1;
            animation: backgroundShift 20s ease-in-out infinite;
        }

        @keyframes backgroundShift {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Header Moderno */
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            z-index: 1000;
            padding: 1rem 0;
            transition: all 0.3s ease;
        }

        .navbar {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            text-decoration: none;
        }


        .nav-links {
            display: flex;
            gap: 1rem;
            list-style: none;
            align-items: center;
        }

        .nav-link {
            position: relative;
            padding: 0.5rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover {
            color: var(--text-primary);
            background: var(--glass-bg);
            transform: translateY(-1px);
        }

        .nav-link.logout {
            background: linear-gradient(135deg, var(--error), #dc2626);
            color: white;
            border: none;
            cursor: pointer;
        }

        .nav-link.logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }

        /* Mobile Menu */
        .mobile-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 0.5rem;
        }

        .mobile-toggle span {
            width: 24px;
            height: 2px;
            background: var(--text-primary);
            margin: 3px 0;
            transition: 0.3s;
            border-radius: 2px;
        }

        .mobile-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem;
        }

        .mobile-menu.active {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Main Content */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 6rem 2rem 2rem;
            min-height: 100vh;
        }

        /* Profile Header */
        .profile-header {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent), var(--success));
            border-radius: 24px 24px 0 0;
        }

        .profile-content {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .profile-avatar {
            position: relative;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            padding: 4px;
            flex-shrink: 0;
        }

        .profile-avatar::after {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            opacity: 0.3;
            animation: pulse 2s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.05); opacity: 0.1; }
        }

        .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            background: var(--surface);
        }

        .profile-info h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary-light), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .profile-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }

        .profile-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--error), #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }

        /* Grid Layout */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .profile-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--glass-border), transparent);
        }

        .profile-card:hover {
            transform: translateY(-4px);
            border-color: rgba(37, 99, 235, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Detail Items */
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: rgba(51, 65, 85, 0.3);
            border-radius: 12px;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .detail-item:hover {
            background: rgba(51, 65, 85, 0.5);
            border-color: var(--primary);
        }

        .detail-label {
            font-weight: 600;
            color: var(--primary-light);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            color: var(--text-primary);
            font-size: 1rem;
            line-height: 1.6;
        }

        /* Full Width Cards */
        .profile-card.full-width {
            grid-column: 1 / -1;
        }

        /* Special Cards */
        .skills-card {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(37, 99, 235, 0.1));
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .projects-card {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(37, 99, 235, 0.1));
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .contact-card {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(37, 99, 235, 0.1));
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .projects-content {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }

        .projects-text {
            flex: 1;
        }

        .projects-image {
            width: 200px;
            height: 200px;
            border-radius: 16px;
            object-fit: cover;
            opacity: 0.7;
            transition: all 0.3s ease;
        }

        .projects-image:hover {
            opacity: 1;
            transform: scale(1.05);
        }

        /* Modal Moderno */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            margin: 2% auto;
            padding: 2rem;
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: modalSlideUp 0.4s ease;
        }

        @keyframes modalSlideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .modal-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .modal-close {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--surface-light);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: var(--error);
            color: white;
            transform: scale(1.1);
        }

        /* Form Styling */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-input,
        .form-textarea {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1rem;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: rgba(51, 65, 85, 0.8);
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--surface);
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .file-input-wrapper:hover {
            border-color: var(--primary);
            background: rgba(51, 65, 85, 0.5);
        }

        .file-input {
            position: absolute;
            left: -9999px;
            opacity: 0;
        }

        .file-input-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 100px;
            right: 2rem;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            z-index: 1001;
            animation: notificationSlide 0.5s ease, notificationFadeOut 0.5s ease 4.5s forwards;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .notification.success {
            background: linear-gradient(135deg, var(--success), #059669);
        }

        .notification.error {
            background: linear-gradient(135deg, var(--error), #dc2626);
        }

        @keyframes notificationSlide {
            from { opacity: 0; transform: translateX(100%); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes notificationFadeOut {
            from { opacity: 1; }
            to { opacity: 0; transform: translateX(100%); }
        }

        /* Footer */
        .footer {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--glass-border);
            padding: 2rem 0;
            text-align: center;
            margin-top: 4rem;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .footer-logo {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .footer-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Templates Section */
        .templates-section {
            margin: 3rem 0;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.5rem;
        }

        .templates-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .templates-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .templates-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .template-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .template-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-color: var(--primary);
        }

        .template-preview {
            height: 200px;
            background: #fff;
            padding: 12px;
            margin: 12px;
            border-radius: 8px;
            font-size: 8px;
            color: #333;
            position: relative;
            overflow: hidden;
        }

        /* Preview Elements */
        .preview-header {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }

        .preview-name {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 4px;
            color: #2c3e50;
        }

        .preview-contact {
            font-size: 7px;
            color: #666;
        }

        .preview-section {
            margin-bottom: 12px;
        }

        .preview-title {
            font-weight: bold;
            font-size: 9px;
            margin-bottom: 6px;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .preview-line {
            height: 3px;
            background: #ddd;
            margin-bottom: 3px;
            border-radius: 2px;
        }

        .preview-line.short {
            width: 60%;
        }

        .preview-line.medium {
            width: 80%;
        }

        .preview-line.thin {
            height: 1px;
            background: #2c3e50;
            margin: 8px 0;
        }

        /* Moderno Template */
        .template-preview.moderno {
            display: flex;
            padding: 0;
        }

        .preview-sidebar {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            width: 40%;
            padding: 12px 8px;
            color: white;
        }

        .preview-avatar {
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            margin: 0 auto 8px;
        }

        .preview-title.white {
            color: white;
            font-size: 7px;
        }

        .preview-line.white {
            background: rgba(255, 255, 255, 0.7);
            height: 2px;
        }

        .preview-line.white.short {
            width: 70%;
        }

        .skill-bar {
            height: 3px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            margin-bottom: 4px;
            overflow: hidden;
        }

        .skill-fill {
            height: 100%;
            background: white;
            width: 90%;
            border-radius: 2px;
        }

        .skill-fill.medium {
            width: 75%;
        }

        .preview-main {
            flex: 1;
            padding: 12px;
        }

        .preview-name.large {
            font-size: 14px;
            margin-bottom: 2px;
        }

        .preview-subtitle {
            font-size: 8px;
            color: #666;
            margin-bottom: 12px;
        }

        /* Minimalista Template */
        .template-preview.minimalista .preview-header.minimal {
            border-bottom: none;
            text-align: center;
            margin-bottom: 16px;
        }

        .template-preview.minimalista .preview-name {
            font-size: 14px;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }

        .template-preview.minimalista .preview-contact.small {
            font-size: 6px;
        }

        .template-preview.minimalista .preview-section.minimal {
            margin-bottom: 16px;
        }

        .template-preview.minimalista .preview-title.minimal {
            font-size: 8px;
            color: #666;
            margin-bottom: 8px;
            border-bottom: 1px solid #eee;
            padding-bottom: 4px;
        }

        /* Executivo Template */
        .template-preview.executivo .preview-header.executive {
            background: #f8f9fa;
            margin: -12px -12px 12px -12px;
            padding: 12px;
            text-align: center;
        }

        .template-preview.executivo .preview-name.executive {
            font-size: 13px;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .template-preview.executivo .preview-contact.executive {
            font-size: 7px;
            color: #666;
            font-style: italic;
        }

        .preview-divider {
            height: 2px;
            background: var(--primary);
            margin: 8px 0;
            width: 40px;
            margin-left: auto;
            margin-right: auto;
        }

        .preview-columns {
            display: flex;
            gap: 12px;
        }

        .preview-col {
            flex: 1;
        }

        .template-preview.executivo .preview-title.executive {
            font-size: 8px;
            color: var(--primary);
            border-left: 3px solid var(--primary);
            padding-left: 6px;
            margin-bottom: 8px;
        }

        /* Template Info */
        .template-info {
            padding: 1.5rem;
            text-align: center;
        }

        .template-info h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .template-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .template-info .btn {
            width: 100%;
            justify-content: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .templates-grid {
                grid-template-columns: 1fr;
            }
            
            .template-preview {
                height: 180px;
            }
            
            .templates-section {
                padding: 2rem;
            }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .main-container {
                padding: 6rem 1.5rem 2rem;
            }
            
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .mobile-toggle {
                display: flex;
            }

            .profile-header {
                padding: 2rem;
            }

            .profile-content {
                flex-direction: column;
                text-align: center;
                gap: 1.5rem;
            }

            .profile-avatar {
                width: 120px;
                height: 120px;
            }

            .profile-info h1 {
                font-size: 2rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .projects-content {
                flex-direction: column;
                align-items: center;
            }

            .projects-image {
                width: 150px;
                height: 150px;
            }

            .notification {
                right: 1rem;
                left: 1rem;
                right: 1rem;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 6rem 1rem 2rem;
            }

            .profile-header {
                padding: 1.5rem;
            }

            .profile-avatar {
                width: 100px;
                height: 100px;
            }

            .profile-info h1 {
                font-size: 1.8rem;
            }

            .profile-card {
                padding: 1.5rem;
            }

            .modal-content {
                margin: 5% auto;
                padding: 1.5rem;
                border-radius: 20px;
            }

            .profile-actions {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
                width: 100%;
            }
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--surface);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Hover Effects for Interactive Elements */
        .detail-item:hover .detail-label {
            color: var(--accent);
        }

        .card-icon {
            animation: iconFloat 3s ease-in-out infinite;
        }

        @keyframes iconFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-2px); }
        }

        .mobile-nav-links {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        list-style: none;
        }

        .mobile-nav-links .nav-link {
            display: block;
            padding: 1rem;
            border-radius: 8px;
            margin: 0.25rem 0;
        }

        .mobile-nav-links .nav-link.logout {
            width: 100%;
            text-align: left;
            background: linear-gradient(135deg, var(--error), #dc2626);
        }
    </style>
</head>

<body>
   <!-- Header -->
<header class="header">
    <nav class="navbar">
        <a href="../php/index.php" class="logo">
            <div class="logo-icon">
                <img src="../assets/img/globo-mundial.png" alt="Logo da Empresa" class="footer-logo">
            </div>
            ProLink
        </a>
        
        <ul class="nav-links">
            <li><a href="../php/index.php" class="nav-link">
                <i class="fas fa-home"></i> Home
            </a></li>
            <li><button onclick="logout()" class="nav-link logout">
                <i class="fas fa-sign-out-alt"></i> Sair
            </button></li>
        </ul>

        <div class="mobile-toggle" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>

    <div class="mobile-menu" id="mobileMenu">
        <ul class="mobile-nav-links">
            <li><a href="../php/index.php" class="nav-link">
                <i class="fas fa-home"></i> Home
            </a></li>
            <li><button onclick="logout()" class="nav-link logout">
                <i class="fas fa-sign-out-alt"></i> Sair
            </button></li>
        </ul>
    </div>
</header>

    <!-- Notification -->
    <?php if (!empty($mensagem)): ?>
        <div class="notification <?php echo strpos($mensagem, 'sucesso') !== false ? 'success' : 'error'; ?>">
            <i class="fas fa-<?php echo strpos($mensagem, 'sucesso') !== false ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
    <?php endif; ?>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-content">
                <div class="profile-avatar">
                    <?php if ($foto_perfil): ?>
                        <img src="<?php echo $foto_perfil; ?>" alt="Foto de perfil" class="profile-image">
                    <?php else: ?>
                        <img src="../assets/img/userp.jpg" alt="Avatar padrão" class="profile-image">
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($nome); ?></h1>
                    <p class="profile-subtitle">Perfil Profissional</p>
                    <div class="profile-actions">
                        <button class="btn btn-primary" onclick="abrirModal()">
                            <i class="fas fa-edit"></i> Editar Perfil
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Grid -->
        <div class="profile-grid">
            <!-- Detalhes Pessoais -->
            <div class="profile-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2 class="card-title">Detalhes Pessoais</h2>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Nome</span>
                    <span class="detail-value"><?php echo htmlspecialchars($usuario['nome']); ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Idade</span>
                    <span class="detail-value"><?php echo $usuario['idade'] ?? 'Não informado'; ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Endereço</span>
                    <span class="detail-value"><?php echo htmlspecialchars($usuario['endereco']) ?: 'Não informado'; ?></span>
                </div>
            </div>

            <!-- Formação e Experiência -->
            <div class="profile-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h2 class="card-title">Formação & Experiência</h2>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Formação</span>
                    <span class="detail-value"><?php echo htmlspecialchars($usuario['formacao']) ?: 'Não informado'; ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Experiência Profissional</span>
                    <span class="detail-value"><?php echo nl2br(htmlspecialchars($usuario['experiencia_profissional'])) ?: 'Não informado'; ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Interesses</span>
                    <span class="detail-value"><?php echo nl2br(htmlspecialchars($usuario['interesses'])) ?: 'Não informado'; ?></span>
                </div>
            </div>

            <!-- Projetos e Especializações -->
            <div class="profile-card projects-card full-width">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <h2 class="card-title">Projetos & Especializações</h2>
                </div>
                
                <div class="projects-content">
                    <div class="projects-text">
                        <div class="detail-item">
                            <span class="detail-value">
                                <?php echo nl2br(htmlspecialchars($usuario['projetos_especializacoes'])) ?: 'Nenhum projeto ou especialização cadastrado ainda.'; ?>
                            </span>
                        </div>
                    </div>
                    <img src="../assets/img/organizing-projects-animate.svg" class="projects-image" alt="Projetos">
                </div>
            </div>

            <!-- Habilidades -->
            <div class="profile-card skills-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h2 class="card-title">Habilidades</h2>
                </div>
                
                <div class="detail-item">
                    <span class="detail-value">
                        <?php echo nl2br(htmlspecialchars($usuario['habilidades'])) ?: 'Nenhuma habilidade cadastrada ainda.'; ?>
                    </span>
                </div>
            </div>

            <!-- Contato -->
            <div class="profile-card contact-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h2 class="card-title">Contato</h2>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">E-mail</span>
                    <span class="detail-value"><?php echo htmlspecialchars($usuario['email']); ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Telefone</span>
                    <span class="detail-value"><?php echo htmlspecialchars($usuario['telefone']) ?: 'Não informado'; ?></span>
                </div>
            </div>
        </div>
    </div>


    <!-- Seção de Templates de Currículo -->
<div class="templates-section">
    <div class="templates-header">
        <h2>
            <i class="fas fa-file-alt"></i>
            Escolha seu Template de Currículo
        </h2>
        <p>Selecione um modelo e baixe seu currículo profissional</p>
    </div>
    
    <div class="templates-grid">
        <div class="template-card" data-template="classico">
            <div class="template-preview">
                <div class="preview-header">
                    <div class="preview-name">João Silva</div>
                    <div class="preview-contact">joao@email.com | (11) 99999-9999</div>
                </div>
                <div class="preview-section">
                    <div class="preview-title">EXPERIÊNCIA</div>
                    <div class="preview-content">
                        <div class="preview-line"></div>
                        <div class="preview-line short"></div>
                    </div>
                </div>
                <div class="preview-section">
                    <div class="preview-title">FORMAÇÃO</div>
                    <div class="preview-content">
                        <div class="preview-line"></div>
                        <div class="preview-line short"></div>
                    </div>
                </div>
            </div>
            <div class="template-info">
                <h3>Clássico</h3>
                <p>Design limpo e profissional, ideal para qualquer área</p>
                <button class="btn btn-primary" onclick="gerarPDFTemplate('classico')">
                    <i class="fas fa-download"></i> Baixar PDF
                </button>
            </div>
        </div>

        <div class="template-card" data-template="moderno">
            <div class="template-preview moderno">
                <div class="preview-sidebar">
                    <div class="preview-avatar"></div>
                    <div class="preview-section">
                        <div class="preview-title white">CONTATO</div>
                        <div class="preview-line white short"></div>
                        <div class="preview-line white"></div>
                    </div>
                    <div class="preview-section">
                        <div class="preview-title white">SKILLS</div>
                        <div class="skill-bar"><div class="skill-fill"></div></div>
                        <div class="skill-bar"><div class="skill-fill medium"></div></div>
                    </div>
                </div>
                <div class="preview-main">
                    <div class="preview-name large">João Silva</div>
                    <div class="preview-subtitle">Desenvolvedor</div>
                    <div class="preview-section">
                        <div class="preview-title">EXPERIÊNCIA</div>
                        <div class="preview-line"></div>
                        <div class="preview-line short"></div>
                    </div>
                </div>
            </div>
            <div class="template-info">
                <h3>Moderno</h3>
                <p>Layout com sidebar colorida, perfeito para áreas criativas</p>
                <button class="btn btn-primary" onclick="gerarPDFTemplate('moderno')">
                    <i class="fas fa-download"></i> Baixar PDF
                </button>
            </div>
        </div>

        <div class="template-card" data-template="minimalista">
            <div class="template-preview minimalista">
                <div class="preview-header minimal">
                    <div class="preview-name">JOÃO SILVA</div>
                    <div class="preview-line thin"></div>
                    <div class="preview-contact small">joao@email.com</div>
                </div>
                <div class="preview-section minimal">
                    <div class="preview-title minimal">EXPERIÊNCIA PROFISSIONAL</div>
                    <div class="preview-content">
                        <div class="preview-line"></div>
                        <div class="preview-line medium"></div>
                        <div class="preview-line short"></div>
                    </div>
                </div>
                <div class="preview-section minimal">
                    <div class="preview-title minimal">EDUCAÇÃO</div>
                    <div class="preview-content">
                        <div class="preview-line medium"></div>
                        <div class="preview-line short"></div>
                    </div>
                </div>
            </div>
            <div class="template-info">
                <h3>Minimalista</h3>
                <p>Design clean e elegante, foco no conteúdo</p>
                <button class="btn btn-primary" onclick="gerarPDFTemplate('minimalista')">
                    <i class="fas fa-download"></i> Baixar PDF
                </button>
            </div>
        </div>

        <div class="template-card" data-template="executivo">
            <div class="template-preview executivo">
                <div class="preview-header executive">
                    <div class="preview-name executive">JOÃO SILVA</div>
                    <div class="preview-contact executive">Chief Executive Officer</div>
                    <div class="preview-divider"></div>
                </div>
                <div class="preview-columns">
                    <div class="preview-col">
                        <div class="preview-section">
                            <div class="preview-title executive">EXPERIÊNCIA</div>
                            <div class="preview-line"></div>
                            <div class="preview-line short"></div>
                        </div>
                    </div>
                    <div class="preview-col">
                        <div class="preview-section">
                            <div class="preview-title executive">COMPETÊNCIAS</div>
                            <div class="preview-line medium"></div>
                            <div class="preview-line"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="template-info">
                <h3>Executivo</h3>
                <p>Formato corporativo para cargos de liderança</p>
                <button class="btn btn-primary" onclick="gerarPDFTemplate('executivo')">
                    <i class="fas fa-download"></i> Baixar PDF
                </button>
            </div>
        </div>
    </div>
</div>



    <!-- Modal de Edição -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-edit"></i> Editar Perfil
                </h2>
                <button class="modal-close" onclick="fecharModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form action="perfil.php" method="POST" enctype="multipart/form-data">
                <div class="form-group full-width">
                    <label for="foto_perfil" class="form-label">
                        <i class="fas fa-camera"></i> Foto de Perfil
                    </label>
                    <div class="file-input-wrapper">
                        <i class="fas fa-upload"></i>
                        <span class="file-input-text">Clique para selecionar uma imagem</span>
                        <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*" class="file-input">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nome" class="form-label">
                            <i class="fas fa-user"></i> Nome
                        </label>
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="idade" class="form-label">
                            <i class="fas fa-calendar-alt"></i> Idade
                        </label>
                        <input type="number" id="idade" name="idade" value="<?php echo htmlspecialchars($usuario['idade'] ?? ''); ?>" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="dataNascimento" class="form-label">
                            <i class="fas fa-birthday-cake"></i> Data de Nascimento
                        </label>
                        <input type="date" id="dataNascimento" name="dataNascimento" value="<?php echo htmlspecialchars($usuario['dataNascimento'] ?? ''); ?>" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone" class="form-label">
                            <i class="fas fa-phone"></i> Telefone
                        </label>
                        <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>" class="form-input">
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="endereco" class="form-label">
                        <i class="fas fa-map-marker-alt"></i> Endereço
                    </label>
                    <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($usuario['endereco']); ?>" class="form-input">
                </div>
                
                <div class="form-group full-width">
                    <label for="formacao" class="form-label">
                        <i class="fas fa-graduation-cap"></i> Formação
                    </label>
                    <input type="text" id="formacao" name="formacao" value="<?php echo htmlspecialchars($usuario['formacao']); ?>" class="form-input">
                </div>
                
                <div class="form-group full-width">
                    <label for="experiencia_profissional" class="form-label">
                        <i class="fas fa-briefcase"></i> Experiência Profissional
                    </label>
                    <textarea id="experiencia_profissional" name="experiencia_profissional" class="form-textarea"><?php echo htmlspecialchars($usuario['experiencia_profissional']); ?></textarea>
                </div>
                
                <div class="form-group full-width">
                    <label for="interesses" class="form-label">
                        <i class="fas fa-heart"></i> Interesses
                    </label>
                    <textarea id="interesses" name="interesses" class="form-textarea"><?php echo htmlspecialchars($usuario['interesses']); ?></textarea>
                </div>
                
                <div class="form-group full-width">
                    <label for="projetos_especializacoes" class="form-label">
                        <i class="fas fa-project-diagram"></i> Projetos e Especializações
                    </label>
                    <textarea id="projetos_especializacoes" name="projetos_especializacoes" class="form-textarea"><?php echo htmlspecialchars($usuario['projetos_especializacoes']); ?></textarea>
                </div>
                
                <div class="form-group full-width">
                    <label for="habilidades" class="form-label">
                        <i class="fas fa-cogs"></i> Habilidades
                    </label>
                    <textarea id="habilidades" name="habilidades" class="form-textarea"><?php echo htmlspecialchars($usuario['habilidades']); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">
                 <img src="../assets/img/globo-mundial.png" alt="Logo da Empresa" class="footer-logo">
            </div>
            <span class="footer-text">&copy; 2024 ProLink. Todos os direitos reservados.</span>
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
        if (['text'].includes(tipoEsperado) && valor.length > 500) {
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
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            z-index: 20000;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            max-width: 400px;
            text-align: center;
            animation: modalWarningShow 0.4s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.1);
        `;
        
        aviso.innerHTML = `
            <div style="font-size: 2rem; margin-bottom: 1rem;">🔒</div>
            <strong style="font-size: 1.1rem; display: block; margin-bottom: 1rem;">Alerta de Segurança</strong>
            Tentativa de manipulação detectada no campo: <strong>${campo}</strong><br>
            O formulário foi resetado por segurança.<br><br>
            <button onclick="this.parentElement.remove()" style="
                background: rgba(255, 255, 255, 0.2);
                color: white;
                border: 1px solid rgba(255, 255, 255, 0.3);
                padding: 0.75rem 1.5rem;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                margin-top: 1rem;
                transition: all 0.3s ease;
                backdrop-filter: blur(10px);
            " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">OK</button>
        `;
        
        // Adicionar CSS da animação se não existir
        if (!document.querySelector('#modal-security-warning-styles')) {
            const style = document.createElement('style');
            style.id = 'modal-security-warning-styles';
            style.textContent = `
                @keyframes modalWarningShow {
                    from { transform: translate(-50%, -50%) scale(0.8); opacity: 0; }
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

    // Funções para controlar o modal
    function abrirModal() {
        document.getElementById('modalEditar').style.display = 'block';
        // Inicializar a proteção dos inputs quando o modal for aberto
        setTimeout(protegerInputsModal, 100);
    }

    function fecharModal() {
        const modal = document.getElementById('modalEditar');
        modal.style.animation = 'modalFadeOut 0.3s ease';
        setTimeout(() => {
            modal.style.display = 'none';
            modal.style.animation = '';
        }, 300);
    }

    // Fechar o modal se clicar fora dele
    window.onclick = function(event) {
        const modal = document.getElementById('modalEditar');
        if (event.target == modal) {
            fecharModal();
        }
    }

    // Gerar PDF com loading
    function gerarPDF() {
        const btn = event.target;
        const originalText = btn.innerHTML;
        
        // Mostra loading
        btn.innerHTML = '<span class="loading"></span> Gerando PDF...';
        btn.disabled = true;
        
        // Simula delay antes de redirecionar
        setTimeout(() => {
            window.location.href = "../php/gerar_pdf.php";
            
            // Restaura o botão após um tempo
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 3000);
        }, 1000);
    }

    // Função de logout
    function logout() {
        // Criar modal de confirmação moderno
        const confirmModal = document.createElement('div');
        confirmModal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 3000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: modalFadeIn 0.3s ease;
        `;
        
        confirmModal.innerHTML = `
            <div style="
                background: var(--glass-bg);
                backdrop-filter: blur(20px);
                border: 1px solid var(--glass-border);
                border-radius: 20px;
                padding: 2rem;
                max-width: 400px;
                text-align: center;
                animation: modalSlideUp 0.4s ease;
            ">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">⚠️</div>
                <h3 style="color: var(--text-primary); margin-bottom: 1rem; font-size: 1.3rem;">Confirmar Logout</h3>
                <p style="color: var(--text-secondary); margin-bottom: 2rem;">Tem certeza que deseja sair da sua conta?</p>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <button onclick="this.closest('div').parentElement.remove()" style="
                        background: var(--surface-light);
                        color: var(--text-primary);
                        border: 1px solid var(--border);
                        padding: 0.75rem 1.5rem;
                        border-radius: 8px;
                        cursor: pointer;
                        font-weight: 600;
                        transition: all 0.3s ease;
                    ">Cancelar</button>
                    <button onclick="window.location.href='../php/logout.php'" style="
                        background: linear-gradient(135deg, var(--error), #dc2626);
                        color: white;
                        border: none;
                        padding: 0.75rem 1.5rem;
                        border-radius: 8px;
                        cursor: pointer;
                        font-weight: 600;
                        transition: all 0.3s ease;
                    ">Sair</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(confirmModal);
    }

    // Função para gerar PDF com template específico
function gerarPDFTemplate(template) {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    // Mostra loading
    btn.innerHTML = '<span class="loading"></span> Gerando PDF...';
    btn.disabled = true;
    
    // Simula delay antes de redirecionar
    setTimeout(() => {
        window.location.href = `../php/gerar_pdf.php?template=${template}`;
        
        // Restaura o botão após um tempo
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 3000);
    }, 1000);
}

// Adicionar ao escopo global
window.gerarPDFTemplate = gerarPDFTemplate;

    // Função para alternar o menu móvel
    // Função para alternar o menu móvel
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    const hamburger = document.querySelector('.mobile-toggle');
    
    mobileMenu.classList.toggle('active');
    
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

    // File input functionality
    function initFileInput() {
        const fileInput = document.getElementById('foto_perfil');
        const wrapper = fileInput?.closest('.file-input-wrapper');
        const text = wrapper?.querySelector('.file-input-text');
        
        if (fileInput && wrapper && text) {
            fileInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    const fileName = e.target.files[0].name;
                    text.innerHTML = `<i class="fas fa-check"></i> ${fileName}`;
                    wrapper.style.borderColor = 'var(--success)';
                    wrapper.style.background = 'rgba(16, 185, 129, 0.1)';
                } else {
                    text.innerHTML = '<i class="fas fa-upload"></i> Clique para selecionar uma imagem';
                    wrapper.style.borderColor = 'var(--border)';
                    wrapper.style.background = 'var(--surface)';
                }
            });
            
            wrapper.addEventListener('click', function() {
                fileInput.click();
            });
            
            // Drag and drop
            wrapper.addEventListener('dragover', function(e) {
                e.preventDefault();
                wrapper.style.borderColor = 'var(--primary)';
                wrapper.style.background = 'rgba(37, 99, 235, 0.1)';
            });
            
            wrapper.addEventListener('dragleave', function() {
                wrapper.style.borderColor = 'var(--border)';
                wrapper.style.background = 'var(--surface)';
            });
            
            wrapper.addEventListener('drop', function(e) {
                e.preventDefault();
                wrapper.style.borderColor = 'var(--border)';
                wrapper.style.background = 'var(--surface)';
                
                if (e.dataTransfer.files.length > 0) {
                    fileInput.files = e.dataTransfer.files;
                    const fileName = e.dataTransfer.files[0].name;
                    text.innerHTML = `<i class="fas fa-check"></i> ${fileName}`;
                    wrapper.style.borderColor = 'var(--success)';
                    wrapper.style.background = 'rgba(16, 185, 129, 0.1)';
                }
            });
        }
    }

    // Animação de entrada para cards
    function animateCards() {
        const cards = document.querySelectorAll('.profile-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 150);
        });
    }

    // Smooth scroll para links internos
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // Adicionar CSS para animações adicionais
    function addExtraStyles() {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes modalFadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
            
            .profile-card {
                opacity: 0;
                transform: translateY(30px);
            }
            
            /* Efeito glassmorphism aprimorado */
            .profile-header::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(45deg, 
                    rgba(37, 99, 235, 0.05) 0%, 
                    rgba(139, 92, 246, 0.05) 50%, 
                    rgba(16, 185, 129, 0.05) 100%);
                pointer-events: none;
                border-radius: 24px;
            }
            
            /* Hover effects aprimorados */
            .btn:active {
                transform: translateY(0);
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            }
            
            .form-input:hover,
            .form-textarea:hover {
                border-color: rgba(37, 99, 235, 0.5);
            }
            
            /* Micro interações */
            .nav-link::after {
                content: '';
                position: absolute;
                bottom: -2px;
                left: 50%;
                width: 0;
                height: 2px;
                background: linear-gradient(90deg, var(--primary), var(--accent));
                transition: all 0.3s ease;
                transform: translateX(-50%);
            }
            
            .nav-link:hover::after {
                width: 80%;
            }
            
            /* Particles effect para o background */
            .particles {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: -1;
            }
            
            .particle {
                position: absolute;
                background: rgba(37, 99, 235, 0.1);
                border-radius: 50%;
                animation: float 6s ease-in-out infinite;
            }
            
            @keyframes float {
                0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.1; }
                50% { transform: translateY(-20px) rotate(180deg); opacity: 0.3; }
            }
        `;
        document.head.appendChild(style);
    }

    // Criar efeito de partículas
    function createParticles() {
        const particles = document.createElement('div');
        particles.className = 'particles';
        
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.width = Math.random() * 4 + 2 + 'px';
            particle.style.height = particle.style.width;
            particle.style.animationDelay = Math.random() * 6 + 's';
            particle.style.animationDuration = (Math.random() * 4 + 4) + 's';
            particles.appendChild(particle);
        }
        
        document.body.appendChild(particles);
    }

    // Inicialização quando o DOM estiver carregado
    document.addEventListener('DOMContentLoaded', function() {
        // Adicionar estilos extras
        addExtraStyles();
        
        // Criar efeito de partículas
        createParticles();
        
        // Animar cards
        setTimeout(animateCards, 100);
        
        // Inicializar file input
        initFileInput();
        
        // Inicializar smooth scroll
        initSmoothScroll();
        
        // Fechar o menu ao clicar em um link
        document.querySelectorAll('.mobile-menu a, .mobile-menu button').forEach(link => {
            link.addEventListener('click', () => {
                toggleMobileMenu();
            });
        });
        
        // Fechar o menu ao clicar fora dele
        document.addEventListener('click', (event) => {
            const mobileMenu = document.querySelector('.mobile-menu');
            const hamburger = document.querySelector('.mobile-toggle');
            
            if (mobileMenu.classList.contains('active') &&
                !mobileMenu.contains(event.target) &&
                !hamburger.contains(event.target)) {
                toggleMobileMenu();
            }
        });
        
        // Inicializar proteção dos inputs do modal
        const modal = document.getElementById('modalEditar');
        if (modal) {
            setTimeout(protegerInputsModal, 500);
        }
        
        // Adicionar efeito de typing para o título
        const titulo = document.querySelector('.profile-info h1');
        if (titulo) {
            const texto = titulo.textContent;
            titulo.textContent = '';
            titulo.style.borderRight = '2px solid var(--primary)';
            
            let i = 0;
            const typeWriter = () => {
                if (i < texto.length) {
                    titulo.textContent += texto.charAt(i);
                    i++;
                    setTimeout(typeWriter, 100);
                } else {
                    // Remove cursor após terminar
                    setTimeout(() => {
                        titulo.style.borderRight = 'none';
                    }, 1000);
                }
            };
            
            setTimeout(typeWriter, 1000);
        }
        
        // Adicionar ripple effect aos botões
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const ripple = document.createElement('div');
                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.3);
                    pointer-events: none;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                `;
                
                const rect = btn.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = e.clientX - rect.left - size / 2 + 'px';
                ripple.style.top = e.clientY - rect.top - size / 2 + 'px';
                
                btn.style.position = 'relative';
                btn.style.overflow = 'hidden';
                btn.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        });
        
        // Adicionar CSS para ripple effect
        const rippleStyle = document.createElement('style');
        rippleStyle.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(rippleStyle);
    });

    // Adicionar CSS para animação de fade out personalizada
    const fadeStyle = document.createElement('style');
    fadeStyle.textContent = `
        @keyframes modalFadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    `;
    document.head.appendChild(fadeStyle);
</script>
</body>
</html>