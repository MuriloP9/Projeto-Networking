<?php
// PRIMEIRA LINHA DO ARQUIVO - antes de qualquer saída
session_start();
include("../php/conexao.php"); 

// Funções de busca (sanitizadas)
function buscarPorNome($pdo, $searchQuery) {
    // Sanitizar entrada
    $searchQuery = filter_var($searchQuery, FILTER_SANITIZE_SPECIAL_CHARS);
    
    $query = "SELECT U.nome, P.formacao, P.experiencia_profissional, U.email, P.habilidades, U.foto_perfil, 
                     P.idade, P.endereco, P.interesses, P.projetos_especializacoes, U.qr_code
              FROM Perfil P
              INNER JOIN Usuario U ON P.id_usuario = U.id_usuario
              WHERE U.nome LIKE :searchPattern";
    
    $sql = $pdo->prepare($query);
    $searchPattern = '%' . $searchQuery . '%';
    $sql->bindValue(":searchPattern", $searchPattern, PDO::PARAM_STR);
    $sql->execute();
    
    return $sql->fetchAll(PDO::FETCH_ASSOC);
}

function buscarPorFormacao($pdo, $searchQuery) {
    // Sanitizar entrada
    $searchQuery = filter_var($searchQuery, FILTER_SANITIZE_SPECIAL_CHARS);
    
    $query = "SELECT U.nome, P.formacao, P.experiencia_profissional, U.email, P.habilidades, U.foto_perfil,
                     P.idade, P.endereco, P.interesses, P.projetos_especializacoes, U.qr_code
              FROM Perfil P
              INNER JOIN Usuario U ON P.id_usuario = U.id_usuario
              WHERE P.formacao LIKE :searchPattern";
    
    $sql = $pdo->prepare($query);
    $searchPattern = '%' . $searchQuery . '%';
    $sql->bindValue(":searchPattern", $searchPattern, PDO::PARAM_STR);
    $sql->execute();
    
    return $sql->fetchAll(PDO::FETCH_ASSOC);
}

function buscarPorHabilidades($pdo, $searchQuery) {
    // Sanitizar entrada
    $searchQuery = filter_var($searchQuery, FILTER_SANITIZE_SPECIAL_CHARS);
    
    $query = "SELECT U.nome, P.formacao, P.experiencia_profissional, U.email, P.habilidades, U.foto_perfil,
                     P.idade, P.endereco, P.interesses, P.projetos_especializacoes, U.qr_code
              FROM Perfil P
              INNER JOIN Usuario U ON P.id_usuario = U.id_usuario
              WHERE P.habilidades LIKE :searchPattern";
    
    $sql = $pdo->prepare($query);
    $searchPattern = '%' . $searchQuery . '%';
    $sql->bindValue(":searchPattern", $searchPattern, PDO::PARAM_STR);
    $sql->execute();
    
    return $sql->fetchAll(PDO::FETCH_ASSOC);
}

function buscarPorInteresses($pdo, $searchQuery) {
    // Sanitizar entrada
    $searchQuery = filter_var($searchQuery, FILTER_SANITIZE_SPECIAL_CHARS);
    
    $query = "SELECT U.nome, P.formacao, P.experiencia_profissional, U.email, P.habilidades, U.foto_perfil,
                     P.idade, P.endereco, P.interesses, P.projetos_especializacoes, U.qr_code
              FROM Perfil P
              INNER JOIN Usuario U ON P.id_usuario = U.id_usuario
              WHERE P.interesses LIKE :searchPattern";
    
    $sql = $pdo->prepare($query);
    $searchPattern = '%' . $searchQuery . '%';
    $sql->bindValue(":searchPattern", $searchPattern, PDO::PARAM_STR);
    $sql->execute();
    
    return $sql->fetchAll(PDO::FETCH_ASSOC);
}

// Processar a pesquisa com sanitização
$searchQuery = '';
$filtro = 'todos'; // Filtro padrão

if (isset($_GET['search'])) {
    $searchQuery = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS);
    $searchQuery = trim($searchQuery);
    
    // Validar se não está vazio após sanitização
    if (empty($searchQuery)) {
        $searchQuery = '';
    }
}

// Verificar se foi selecionado um filtro específico
if (isset($_GET['filtro'])) {
    $filtro = filter_input(INPUT_GET, 'filtro', FILTER_SANITIZE_SPECIAL_CHARS);
}

// Inicializar variáveis para armazenar os resultados
$resultadosHTML = '';
$modalHTML = '';

if ($searchQuery !== '') {
    try {
        $pdo = conectar();
        
        // Buscar resultados baseado no filtro selecionado
        if ($filtro === 'nome') {
            $resultados = buscarPorNome($pdo, $searchQuery);
        } elseif ($filtro === 'formacao') {
            $resultados = buscarPorFormacao($pdo, $searchQuery);
        } elseif ($filtro === 'habilidades') {
            $resultados = buscarPorHabilidades($pdo, $searchQuery);
        } elseif ($filtro === 'interesses') {
            $resultados = buscarPorInteresses($pdo, $searchQuery);
        } else {
            // Buscar em todos os campos (comportamento padrão)
            $resultadosNome = buscarPorNome($pdo, $searchQuery);
            $resultadosFormacao = buscarPorFormacao($pdo, $searchQuery);
            $resultadosHabilidades = buscarPorHabilidades($pdo, $searchQuery);
            $resultadosInteresses = buscarPorInteresses($pdo, $searchQuery);
            $resultados = array_merge($resultadosNome, $resultadosFormacao, $resultadosHabilidades, $resultadosInteresses);
        }
        
        // Remover duplicatas
        $profissionaisUnicos = array();
        foreach ($resultados as $profissional) {
            $email = filter_var($profissional['email'], FILTER_SANITIZE_EMAIL);
            if (!isset($profissionaisUnicos[$email]) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $profissionaisUnicos[$email] = $profissional;
            }
        }
        
        // Verificar se o usuário está logado
        $usuarioLogado = isset($_SESSION['id_usuario']);
        
        // Construir HTML dos resultados
        if (empty($profissionaisUnicos)) {
            $searchQueryEscaped = htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8');
            $resultadosHTML = "<div class='professional-list'><p>Nenhum profissional encontrado para '$searchQueryEscaped'.</p></div>";
        } else {
            $resultadosHTML = "<div class='professional-list'>";
            foreach ($profissionaisUnicos as $profissional) {
                // Sanitizar dados antes de exibir
                $nome = htmlspecialchars($profissional['nome'], ENT_QUOTES, 'UTF-8');
                $formacao = htmlspecialchars($profissional['formacao'], ENT_QUOTES, 'UTF-8');
                $habilidades = htmlspecialchars($profissional['habilidades'], ENT_QUOTES, 'UTF-8');
                $experiencia = htmlspecialchars($profissional['experiencia_profissional'], ENT_QUOTES, 'UTF-8');
                $idade = htmlspecialchars($profissional['idade'], ENT_QUOTES, 'UTF-8');
                $endereco = htmlspecialchars($profissional['endereco'], ENT_QUOTES, 'UTF-8');
                $interesses = htmlspecialchars($profissional['interesses'], ENT_QUOTES, 'UTF-8');
                $projetos = htmlspecialchars($profissional['projetos_especializacoes'], ENT_QUOTES, 'UTF-8');
                $email = filter_var($profissional['email'], FILTER_SANITIZE_EMAIL);
                $qrCodePath = !empty($profissional['qr_code']) ? 
                    'get_qrcode.php?file=' . basename(htmlspecialchars($profissional['qr_code'], ENT_QUOTES, 'UTF-8')) : '';
                
                // Validar email
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue; // Pular este profissional se email inválido
                }
                
                $fotoPerfil = !empty($profissional['foto_perfil']) 
                    ? "data:image/jpeg;base64," . base64_encode($profissional['foto_perfil'])
                    : "../assets/img/userp.jpg";
                
                // Obter o valor do qr_code da tabela Usuario para este email
                $stmt = $pdo->prepare("SELECT qr_code FROM Usuario WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                $qrCodeValue = $stmt->fetchColumn();
                $qrCodeValue = htmlspecialchars($qrCodeValue, ENT_QUOTES, 'UTF-8');
                
                $resultadosHTML .= "<div class='professional-card'>";
                
                // Header do cartão com foto e nome
                $resultadosHTML .= "<div class='card-header'>";
                $resultadosHTML .= "<div class='profile-pic' style='background-image: url($fotoPerfil);'></div>";
                $resultadosHTML .= "<div class='professional-name-container'>";
                $resultadosHTML .= "<h3 class='professional-name'>" . $nome . "</h3>";
                if (!empty($idade)) {
                    $resultadosHTML .= "<span class='professional-age'>" . $idade . " anos</span>";
                }
                $resultadosHTML .= "</div>";
                $resultadosHTML .= "</div>";
                
                // Conteúdo principal do cartão
                $resultadosHTML .= "<div class='card-content'>";
                
                // Informações básicas em grid
                $resultadosHTML .= "<div class='info-grid'>";
                
                if (!empty($formacao)) {
                    $resultadosHTML .= "<div class='info-item'>";
                    $resultadosHTML .= "<div class='info-icon'>🎓</div>";
                    $resultadosHTML .= "<div class='info-content'>";
                    $resultadosHTML .= "<strong>Formação</strong>";
                    $resultadosHTML .= "<p>" . $formacao . "</p>";
                    $resultadosHTML .= "</div></div>";
                }
                
                if (!empty($endereco)) {
                    $resultadosHTML .= "<div class='info-item'>";
                    $resultadosHTML .= "<div class='info-icon'>📍</div>";
                    $resultadosHTML .= "<div class='info-content'>";
                    $resultadosHTML .= "<strong>Localização</strong>";
                    $resultadosHTML .= "<p>" . $endereco . "</p>";
                    $resultadosHTML .= "</div></div>";
                }
                
                $resultadosHTML .= "</div>";
                
                // Seções expandidas
                if (!empty($experiencia)) {
                    $resultadosHTML .= "<div class='expandable-section'>";
                    $resultadosHTML .= "<div class='section-title'>💼 Experiência Profissional</div>";
                    $resultadosHTML .= "<div class='section-content'>" . nl2br($experiencia) . "</div>";
                    $resultadosHTML .= "</div>";
                }
                
                if (!empty($habilidades)) {
                    $resultadosHTML .= "<div class='expandable-section'>";
                    $resultadosHTML .= "<div class='section-title'>⚡ Habilidades</div>";
                    $resultadosHTML .= "<div class='section-content'>";
                    // Transformar habilidades em tags
                    $habilidadesArray = explode(',', $habilidades);
                    foreach ($habilidadesArray as $habilidade) {
                        $habilidade = trim($habilidade);
                        if (!empty($habilidade)) {
                            $resultadosHTML .= "<span class='skill-tag'>" . $habilidade . "</span>";
                        }
                    }
                    $resultadosHTML .= "</div></div>";
                }
                
                if (!empty($interesses)) {
                    $resultadosHTML .= "<div class='expandable-section'>";
                    $resultadosHTML .= "<div class='section-title'>💡 Interesses</div>";
                    $resultadosHTML .= "<div class='section-content'>";
                    // Transformar interesses em tags
                    $interessesArray = explode(',', $interesses);
                    foreach ($interessesArray as $interesse) {
                        $interesse = trim($interesse);
                        if (!empty($interesse)) {
                            $resultadosHTML .= "<span class='interest-tag'>" . $interesse . "</span>";
                        }
                    }
                    $resultadosHTML .= "</div></div>";
                }
                
                if (!empty($projetos)) {
                    $resultadosHTML .= "<div class='expandable-section'>";
                    $resultadosHTML .= "<div class='section-title'>🚀 Projetos e Especializações</div>";
                    $resultadosHTML .= "<div class='section-content'>" . nl2br($projetos) . "</div>";
                    $resultadosHTML .= "</div>";
                }
                
                $resultadosHTML .= "</div>";
                
                // Footer do cartão com botão de ação
                $resultadosHTML .= "<div class='card-footer'>";
                
                if (!empty($qrCodePath)) {
                    if ($usuarioLogado) {
                        $emailEscaped = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
                        $resultadosHTML .= "<button class='action-btn copy-link-btn' data-qrcode-path='$qrCodePath' data-email='" . $emailEscaped . "' data-qrcode='" . $qrCodeValue . "'>";
                        $resultadosHTML .= "<span>Copiar Link de Contato</span>";
                        $resultadosHTML .= "<div class='btn-icon'>🔗</div>";
                        $resultadosHTML .= "</button>";
                    } else {
                        $resultadosHTML .= "<button class='action-btn login-redirect' onclick=\"window.location.href='../php/index.php?openLoginModal=true';\">";
                        $resultadosHTML .= "<span>Copiar Link de Contato</span>";
                        $resultadosHTML .= "<div class='btn-icon'>🔒</div>";
                        $resultadosHTML .= "</button>";
                    }
                } else {
                    $resultadosHTML .= "<button class='action-btn' disabled title='Link não disponível'>";
                    $resultadosHTML .= "<span>Link Indisponível</span>";
                    $resultadosHTML .= "<div class='btn-icon'>❌</div>";
                    $resultadosHTML .= "</button>";
                }
                
                $resultadosHTML .= "</div>";
                $resultadosHTML .= "</div>";
            }
            $resultadosHTML .= "</div>";
        }
    } catch (Exception $erro) {
        $erroMessage = htmlspecialchars($erro->getMessage(), ENT_QUOTES, 'UTF-8');
        $resultadosHTML = "<div class='professional-list'><p>Erro ao buscar profissionais: " . $erroMessage . "</p></div>";
    }
} else {
    $resultadosHTML = "<div class='professional-list'><p>Por favor, forneça um termo de pesquisa.</p></div>";
}

// Sanitizar valor de busca para exibição no input
$searchQueryDisplay = htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProLink - Profissionais</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <!-- Estilo responsivo melhorado -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(to bottom, #050a37, #0e1768);
            color: #fff;
        }

        /* Estilos para o menu responsivo */
        .menu-toggle {
            display: none;
            cursor: pointer;
            padding: 10px;
            background: transparent;
            border: none;
            z-index: 1100;
        }

        .menu-icon {
            width: 24px;
            height: 24px;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .menu-close-item {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px;
            background-color: rgba(14, 23, 104, 0.8);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 1200;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .menu-close-item .menu-icon {
            width: 24px;
            height: 24px;
            transform: rotate(45deg);
        }

        /* Área de busca melhorada */
        .search-container {
            display: flex;
            align-items: center;
            margin: 30px auto;
            max-width: 800px;
            padding: 0 20px;
            gap: 15px;
            position: relative;
        }

        .search-bar {
            flex-grow: 2;
            padding: 16px 20px;
            font-size: 16px;
            border-radius: 25px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            font-family: 'Montserrat', sans-serif;
        }

        .search-bar::placeholder {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 300;
        }

        .search-bar:focus {
            outline: none;
            border-color: rgb(21, 118, 228);
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(21, 118, 228, 0.3);
        }

        .search-btn {
            padding: 16px 28px;
            font-size: 16px;
            background: linear-gradient(135deg, rgb(21, 118, 228), rgb(116, 154, 224));
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 32px rgba(21, 118, 228, 0.3);
            position: relative;
            overflow: hidden;
        }

        .search-btn:hover {
            background: linear-gradient(135deg, rgb(116, 154, 224), rgb(21, 118, 228));
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(21, 118, 228, 0.4);
        }

        .search-btn:active {
            transform: translateY(0);
            box-shadow: 0 4px 16px rgba(21, 118, 228, 0.3);
        }

        .search-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .search-btn:hover::before {
            left: 100%;
        }

        /* Filtros de pesquisa melhorados */
        .filter-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 12px;
            flex-wrap: wrap;
            padding: 0 20px;
        }

        .filter-btn {
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .filter-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, rgba(21, 118, 228, 0.3) 0%, transparent 70%);
            transition: all 0.3s ease;
            transform: translate(-50%, -50%);
        }

        .filter-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(21, 118, 228, 0.2);
        }

        .filter-btn:hover::before {
            width: 200px;
            height: 200px;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, rgb(21, 118, 228), rgb(116, 154, 224));
            border-color: rgb(21, 118, 228);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(21, 118, 228, 0.4);
        }

        /* Cartões de Profissionais Completamente Redesenhados */
        .professional-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .professional-card {
            background: linear-gradient(145deg, 
                rgba(255, 255, 255, 0.1) 0%, 
                rgba(255, 255, 255, 0.05) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .professional-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, 
                rgb(21, 118, 228), 
                rgb(116, 154, 224), 
                rgb(21, 118, 228));
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .professional-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.4),
                0 8px 20px rgba(21, 118, 228, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            background: linear-gradient(145deg, 
                rgba(255, 255, 255, 0.15) 0%, 
                rgba(255, 255, 255, 0.08) 100%);
        }

        .professional-card:hover::before {
            opacity: 1;
        }

        /* Header do Cartão */
        .card-header {
            display: flex;
            align-items: center;
            padding: 24px;
            background: linear-gradient(135deg, 
                rgba(21, 118, 228, 0.1) 0%, 
                rgba(116, 154, 224, 0.1) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .profile-pic {
            width: 80px;
            height: 80px;
            min-width: 80px;
            border-radius: 20px;
            background-size: cover;
            background-position: center;
            border: 3px solid rgba(21, 118, 228, 0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 
                0 8px 25px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .profile-pic::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, transparent 30%, rgba(21, 118, 228, 0.1) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .professional-card:hover .profile-pic {
            border-color: rgb(21, 118, 228);
            transform: scale(1.1) rotate(2deg);
            box-shadow: 
                0 12px 35px rgba(21, 118, 228, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .professional-card:hover .profile-pic::after {
            opacity: 1;
        }

        .professional-name-container {
            margin-left: 20px;
            flex: 1;
        }

        .professional-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 6px;
            background: linear-gradient(135deg, #ffffff 0%, #e0e0e0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            line-height: 1.2;
        }

        .professional-age {
            display: inline-block;
            padding: 6px 12px;
            background: rgba(21, 118, 228, 0.2);
            color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid rgba(21, 118, 228, 0.3);
        }

        /* Conteúdo do Cartão */
        .card-content {
            padding: 24px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            padding: 16px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .info-icon {
            font-size: 24px;
            margin-right: 12px;
            opacity: 0.8;
            transition: transform 0.3s ease;
        }

        .info-item:hover .info-icon {
            transform: scale(1.1);
        }

        .info-content strong {
            display: block;
            color: rgb(21, 118, 228);
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .info-content p {
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
            font-size: 13px;
            line-height: 1.4;
        }

        /* Seções Expandíveis */
        .expandable-section {
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .expandable-section:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(21, 118, 228, 0.2);
        }

        .section-title {
            padding: 16px 20px;
            font-weight: 600;
            color: rgb(21, 118, 228);
            background: rgba(21, 118, 228, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-content {
            padding: 20px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            font-size: 14px;
        }

        /* Tags de Habilidades e Interesses */
        .skill-tag, .interest-tag {
            display: inline-block;
            padding: 8px 16px;
            margin: 4px 8px 4px 0;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: default;
        }

        .skill-tag {
            background: linear-gradient(135deg, rgba(21, 118, 228, 0.2), rgba(116, 154, 224, 0.2));
            color: rgb(21, 118, 228);
            border: 1px solid rgba(21, 118, 228, 0.3);
        }

        .skill-tag:hover {
            background: linear-gradient(135deg, rgba(21, 118, 228, 0.3), rgba(116, 154, 224, 0.3));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(21, 118, 228, 0.3);
        }

        .interest-tag {
            background: linear-gradient(135deg, rgba(156, 39, 176, 0.2), rgba(233, 30, 99, 0.2));
            color: rgb(233, 30, 99);
            border: 1px solid rgba(233, 30, 99, 0.3);
        }

        .interest-tag:hover {
            background: linear-gradient(135deg, rgba(156, 39, 176, 0.3), rgba(233, 30, 99, 0.3));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(233, 30, 99, 0.3);
        }

        /* Footer do Cartão */
        .card-footer {
            padding: 20px 24px;
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.03) 0%, 
                rgba(255, 255, 255, 0.01) 100%);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .action-btn {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, rgb(21, 118, 228), rgb(116, 154, 224));
            color: white;
            border: none;
            border-radius: 16px;
            cursor: pointer;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(21, 118, 228, 0.3);
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.2), 
                transparent);
            transition: left 0.6s;
        }

        .action-btn:hover {
            background: linear-gradient(135deg, rgb(116, 154, 224), rgb(21, 118, 228));
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(21, 118, 228, 0.4);
        }

        .action-btn:hover::before {
            left: 100%;
        }

        .action-btn:active {
            transform: translateY(-1px);
        }

        .btn-icon {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .action-btn:hover .btn-icon {
            transform: scale(1.2) rotate(10deg);
        }

        /* Estados especiais dos botões */
        .action-btn:disabled {
            background: linear-gradient(135deg, #666, #555);
            cursor: not-allowed;
            transform: none !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .action-btn:disabled:hover {
            transform: none !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .action-btn:disabled .btn-icon {
            transform: none !important;
        }

        .login-redirect {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e) !important;
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
        }

        .login-redirect:hover {
            background: linear-gradient(135deg, #ff8e8e, #ff6b6b) !important;
            box-shadow: 0 12px 40px rgba(255, 107, 107, 0.4);
        }

        /* Footer */
        .footer-section {
            background-color: #2e2e2e;
            color: white;
            padding: 20px;
            text-align: center;
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-content {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }

        .footer-logo {
            width: 40px;
            height: 40px;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .footer-logo:hover {
            opacity: 1;
        }

        /* Animações adicionais */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .professional-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .professional-card:nth-child(even) {
            animation-delay: 0.1s;
        }

        .professional-card:nth-child(odd) {
            animation-delay: 0.2s;
        }

        /* Efeito de fade-in nos botões do menu */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .menu.active li {
            animation: fadeIn 0.5s ease forwards;
        }

        .menu.active li:nth-child(1) { animation-delay: 0.1s; }
        .menu.active li:nth-child(2) { animation-delay: 0.2s; }
        .menu.active li:nth-child(3) { animation-delay: 0.3s; }
        .menu.active li:nth-child(4) { animation-delay: 0.4s; }

        /* Media Queries */
        @media (max-width: 1200px) {
            .professional-list {
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
                gap: 25px;
            }
        }

        @media (max-width: 991px) {
            .navbar {
                padding: 15px 20px;
            }
            
            .logo {
                font-size: 20px;
            }
            
            .logo-icon {
                width: 30px;
                height: 30px;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .menu {
                display: none;
                flex-direction: column;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100vh;
                background-color: #0e1768;
                padding: 60px 20px 20px;
                z-index: 1000;
                justify-content: flex-start;
                overflow-y: auto;
            }
            
            .menu.active {
                display: flex;
            }
            
            .menu li {
                width: 100%;
                margin: 10px 0;
            }
            
            .menu li a {
                width: 100%;
                text-align: center;
                padding: 12px;
            }

            .professional-list {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 20px 15px;
            }

            .filter-container {
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }
            
            .filter-btn {
                width: 100%;
                text-align: center;
                max-width: 300px;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 10px 15px;
            }
            
            .logo {
                font-size: 18px;
            }
            
            .logo-icon {
                width: 25px;
                height: 25px;
                margin-right: 5px;
            }
            
            .search-container {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
            
            .search-bar, 
            .search-btn {
                width: 100%;
            }

            .professional-list {
                grid-template-columns: 1fr;
                padding: 15px 10px;
            }

            .professional-card {
                margin: 0;
            }

            .card-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 20px;
            }

            .profile-pic {
                margin-bottom: 15px;
                width: 70px;
                height: 70px;
                min-width: 70px;
            }

            .professional-name-container {
                margin-left: 0;
            }

            .professional-name {
                font-size: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .card-content {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .professional-card {
                border-radius: 16px;
            }

            .card-header {
                padding: 16px;
            }

            .profile-pic {
                width: 60px;
                height: 60px;
                min-width: 60px;
                border-radius: 15px;
            }
            
            .professional-name {
                font-size: 18px;
            }
            
            .card-content {
                padding: 16px;
            }

            .section-title {
                font-size: 14px;
                padding: 14px 16px;
            }

            .section-content {
                padding: 16px;
                font-size: 13px;
            }

            .skill-tag, .interest-tag {
                font-size: 11px;
                padding: 6px 12px;
            }
        }

        /* Estados de carregamento e vazio */
        .loading-state {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.7);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.8);
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: rgb(21, 118, 228);
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
            <ul class="menu" id="menu">
                <li><a href="../php/index.php">Home</a></li>
                <li><a href="../php/paginaEmprego.php">Oportunidades de Trabalho</a></li>
                <li><a href="../php/pagina_webinar.php">Webinars</a></li>
            </ul>
            <!-- Botão do menu mobile será inserido via JavaScript -->
        </nav>
    </header>

    <!-- Botão de fechamento separado do menu (fora da lista) -->
    <div id="close-menu" class="menu-close-item" style="display: none;">
        <img src="../assets/img/icons8-menu-48.png" alt="Fechar" class="menu-icon">
    </div>

    <!-- Área de busca -->
    <form method="GET" action="">
        <div class="search-container">
            <input type="text" name="search" id="searchInput" class="search-bar"
                placeholder="Pesquisar por nome, formação, habilidades ou interesses..."
                value="<?= $searchQueryDisplay ?>">
            <button type="submit" class="search-btn">Procurar</button>
        </div>
        
        <!-- Filtros de pesquisa -->
        <div class="filter-container">
            <button type="button" class="filter-btn <?= $filtro === 'todos' ? 'active' : '' ?>" data-filter="todos">Todos</button>
            <button type="button" class="filter-btn <?= $filtro === 'nome' ? 'active' : '' ?>" data-filter="nome">Nome</button>
            <button type="button" class="filter-btn <?= $filtro === 'formacao' ? 'active' : '' ?>" data-filter="formacao">Formação</button>
            <button type="button" class="filter-btn <?= $filtro === 'habilidades' ? 'active' : '' ?>" data-filter="habilidades">Habilidades</button>
            <button type="button" class="filter-btn <?= $filtro === 'interesses' ? 'active' : '' ?>" data-filter="interesses">Interesses</button>
            <input type="hidden" name="filtro" id="filtroInput" value="<?= $filtro ?>">
        </div>
    </form>

    <?php echo $resultadosHTML; ?>

    <footer class="footer-section">
        <div class="footer-content">
            <img src="../assets/img/globo-mundial.png" alt="Logo da Empresa" class="footer-logo">
            <p>&copy; 2024 ProLink. Todos os direitos reservados.</p>
        </div>
    </footer> 

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/script.js"></script>

    <script>
    $(document).ready(function() {
        // ===== PROTEÇÃO CONTRA MANIPULAÇÃO DE INPUTS =====
        function protegerInputs() {
            const searchInput = document.getElementById('searchInput');
            
            if (!searchInput) return;
            
            // Armazenar o tipo original
            const tipoOriginal = searchInput.type;
            const attributosOriginais = {
                type: searchInput.type,
                name: searchInput.name,
                id: searchInput.id,
                required: searchInput.required,
                maxLength: searchInput.maxLength || 100
            };
            
            // Monitorar mudanças nos atributos usando MutationObserver
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes') {
                        const attrName = mutation.attributeName;
                        
                        // Verificar se atributos críticos foram alterados
                        if (['type', 'name', 'id'].includes(attrName)) {
                            const valorAtual = searchInput.getAttribute(attrName);
                            const valorOriginal = attributosOriginais[attrName];
                            
                            if (valorAtual !== valorOriginal.toString()) {
                                console.warn('Tentativa de manipulação detectada no atributo:', attrName);
                                searchInput.setAttribute(attrName, valorOriginal);
                                
                                // Limpar o valor se houve tentativa de manipulação
                                searchInput.value = '';
                                
                                // Mostrar aviso visual
                                mostrarAvisoSeguranca();
                            }
                        }
                    }
                });
            });
            
            // Observar mudanças nos atributos
            observer.observe(searchInput, {
                attributes: true,
                attributeFilter: ['type', 'name', 'id', 'required', 'maxlength']
            });
            
            // Verificação periódica adicional (backup)
            setInterval(function() {
                if (searchInput.type !== tipoOriginal) {
                    searchInput.type = tipoOriginal;
                    searchInput.value = '';
                    mostrarAvisoSeguranca();
                }
            }, 1000);
            
            // Proteção contra alteração via JavaScript console
            Object.defineProperty(searchInput, 'type', {
                get: function() { return tipoOriginal; },
                set: function(value) {
                    if (value !== tipoOriginal) {
                        console.warn('Tentativa de alteração de tipo bloqueada');
                        mostrarAvisoSeguranca();
                        return tipoOriginal;
                    }
                    return tipoOriginal;
                },
                configurable: false
            });
            
            // Validação adicional no evento de input
            searchInput.addEventListener('input', function(e) {
                // Verificar se o tipo foi alterado
                if (this.type !== tipoOriginal) {
                    this.type = tipoOriginal;
                    this.value = '';
                    mostrarAvisoSeguranca();
                    e.preventDefault();
                    return false;
                }
                
                // Validação do conteúdo
                validarConteudoPorTipo(this, tipoOriginal);
            });
            
            // Validação no submit
            searchInput.closest('form').addEventListener('submit', function(e) {
                if (searchInput.type !== tipoOriginal) {
                    e.preventDefault();
                    searchInput.type = tipoOriginal;
                    searchInput.value = '';
                    mostrarAvisoSeguranca();
                    return false;
                }
            });
        }
        
        // Função para validar conteúdo baseado no tipo esperado
        function validarConteudoPorTipo(input, tipoEsperado) {
            const valor = input.value;
            
            // Para campo de busca, permitir apenas caracteres seguros
            const regexTexto = /^[\w\sáàâãéèêíïóôõöúçñÁÀÂãÉÈÊÍÏÓÔÕÖÚÇÑ\-.,;:!?@#%&*()+=]*$/;
            if (!regexTexto.test(valor)) {
                input.value = valor.replace(/[^\w\sáàâãéèêíïóôõöúçñÁÀÂãÉÈÊÍÏÓÔÕÖÚÇÑ\-.,;:!?@#%&*()+=]/g, '');
            }
            
            // Limitar tamanho máximo
            if (valor.length > 100) {
                input.value = valor.substring(0, 100);
            }
        }
        
        // Função para mostrar aviso de segurança
        function mostrarAvisoSeguranca() {
            // Remove avisos anteriores
            const avisoAnterior = document.querySelector('.security-warning');
            if (avisoAnterior) {
                avisoAnterior.remove();
            }
            
            // Criar elemento de aviso
            const aviso = document.createElement('div');
            aviso.className = 'security-warning';
            aviso.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background-color: #ff4444;
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 10000;
                font-family: 'Montserrat', sans-serif;
                font-size: 14px;
                max-width: 300px;
                animation: slideIn 0.3s ease-out;
            `;
            
            aviso.innerHTML = `
                <strong>⚠️ Aviso de Segurança</strong><br>
                Tentativa de manipulação detectada. O formulário foi resetado por segurança.
            `;
            
            // Adicionar CSS da animação se não existir
            if (!document.querySelector('#security-warning-styles')) {
                const style = document.createElement('style');
                style.id = 'security-warning-styles';
                style.textContent = `
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(style);
            }
            
            document.body.appendChild(aviso);
            
            // Remover aviso após 5 segundos
            setTimeout(() => {
                if (aviso.parentNode) {
                    aviso.style.animation = 'slideIn 0.3s ease-out reverse';
                    setTimeout(() => aviso.remove(), 300);
                }
            }, 5000);
        }
        
        // Inicializar proteções
        protegerInputs();
        
        // ===== FILTROS DE PESQUISA =====
        $('.filter-btn').click(function() {
            // Remover a classe active de todos os botões
            $('.filter-btn').removeClass('active');
            
            // Adicionar a classe active ao botão clicado
            $(this).addClass('active');
            
            // Atualizar o valor do filtro no input hidden
            const filtro = $(this).data('filter');
            $('#filtroInput').val(filtro);
            
            // Enviar o formulário
            $(this).closest('form').submit();
        });
        
        // ===== COPIAR LINK DO QR CODE =====
        $('.copy-link-btn').click(function() {
            const qrCodeValue = $(this).data('qrcode');
            
            if (qrCodeValue) {
                // Criar um elemento de input temporário
                const tempInput = document.createElement('input');
                tempInput.value = qrCodeValue;
                document.body.appendChild(tempInput);
                
                // Selecionar e copiar o texto
                tempInput.select();
                document.execCommand('copy');
                
                // Remover o elemento temporário
                document.body.removeChild(tempInput);
                
                // Feedback visual melhorado
                const originalHTML = $(this).html();
                $(this).html('<span>Link Copiado!</span><div class="btn-icon">✅</div>');
                $(this).css('background', 'linear-gradient(135deg, #4caf50, #66bb6a)');
                
                // Restaurar o conteúdo original após 3 segundos
                setTimeout(() => {
                    $(this).html(originalHTML);
                    $(this).css('background', '');
                }, 3000);
            }
        });
        
        // Adicionar evento de tecla para buscar ao pressionar Enter
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('form').submit();
            }
        });
        
        // Script para menu responsivo
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar botão do menu mobile se não existir
            const navbar = document.querySelector('.navbar');
            const closeMenuBtn = document.getElementById('close-menu');
            
            if (!document.getElementById('mobile-menu')) {
                const menuToggle = document.createElement('button');
                menuToggle.id = 'mobile-menu';
                menuToggle.className = 'menu-toggle';
                menuToggle.innerHTML = '<img src="../assets/img/icons8-menu-48.png" alt="Menu" class="menu-icon">';
                navbar.appendChild(menuToggle);
            }
            
            // Controle do menu mobile
            const mobileMenu = document.getElementById('mobile-menu');
            const menu = document.getElementById('menu');
            
            if (mobileMenu) {
                mobileMenu.addEventListener('click', function() {
                    menu.classList.add('active');
                    this.style.display = 'none';
                    closeMenuBtn.style.display = 'flex';
                });
            }
            
            if (closeMenuBtn) {
                closeMenuBtn.addEventListener('click', function() {
                    menu.classList.remove('active');
                    if (mobileMenu) mobileMenu.style.display = 'block';
                    this.style.display = 'none';
                });
            }
            
            // Fechar o menu ao clicar em um link
            const menuLinks = menu.querySelectorAll('a');
            menuLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        menu.classList.remove('active');
                        if (mobileMenu) mobileMenu.style.display = 'block';
                        closeMenuBtn.style.display = 'none';
                    }
                });
            });
            
            // Ajustar visualização em redimensionamento
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) {
                    menu.classList.remove('active');
                    if (mobileMenu) mobileMenu.style.display = 'none';
                    closeMenuBtn.style.display = 'none';
                } else {
                    if (mobileMenu && !menu.classList.contains('active')) {
                        mobileMenu.style.display = 'block';
                    }
                }
            });
            
            // Inicialização - esconder botão mobile em telas grandes
            if (window.innerWidth >= 992 && mobileMenu) {
                mobileMenu.style.display = 'none';
            }
        });

        // ===== ANIMAÇÕES DE ENTRADA DOS CARTÕES =====
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observar todos os cartões de profissionais
        document.querySelectorAll('.professional-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    });
    </script>
</body>
</html>