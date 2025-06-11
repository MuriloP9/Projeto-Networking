
<?php 
session_start();  
include("../php/conexao.php");  
$pdo = conectar();      

// Processar candidatura via AJAX     
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'candidatura') {         
    header('Content-Type: application/json');          
    
    if (!isset($_POST['id_vaga'])) {             
        echo json_encode(['success' => false, 'message' => 'Vaga não especificada.']);             
        exit;         
    }          
    
    // Sanitizar e validar id_vaga
    $id_vaga = filter_var($_POST['id_vaga'], FILTER_VALIDATE_INT);
    if ($id_vaga === false || $id_vaga <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID da vaga inválido.']);
        exit;
    }
    
    $id_usuario = $_SESSION['id_usuario'];          
    
    try {             
        // Verificar se o usuário tem perfil             
        $stmt = $pdo->prepare("SELECT id_perfil FROM Perfil WHERE id_usuario = ?");             
        $stmt->execute([$id_usuario]);             
        $perfil = $stmt->fetch(PDO::FETCH_ASSOC);              
        
        if (!$perfil) {                 
            echo json_encode(['success' => false, 'message' => 'Você precisa completar seu perfil antes de se candidatar.']);                 
            exit;             
        }              
        
        $id_perfil = $perfil['id_perfil'];              
        
        // Verificar se já existe candidatura ATIVA             
       $stmt = $pdo->prepare("SELECT * FROM Candidatura WHERE id_vaga = ? AND id_perfil = ?");             
       $stmt->execute([$id_vaga, $id_perfil]);              
            
        if ($stmt->rowCount() > 0) {                 
    // Se já existe uma candidatura (mesmo que inativa), atualiza para ativa
    $stmt = $pdo->prepare("UPDATE Candidatura SET ativo = 1, status = 'Pendente', data_candidatura = GETDATE() WHERE id_vaga = ? AND id_perfil = ?");
    $stmt->execute([$id_vaga, $id_perfil]);
    
    echo json_encode(['success' => true, 'message' => 'Candidatura reativada com sucesso!']);                 
    exit;             
}        
        
        // Inserir nova candidatura com ativo = 1             
        $stmt = $pdo->prepare("INSERT INTO Candidatura (id_vaga, id_perfil, data_candidatura, status, data_atualizacao_status, ativo) VALUES (?, ?, GETDATE(), 'Pendente', NULL, 1)");             
        $stmt->execute([$id_vaga, $id_perfil]);              
        
        echo json_encode(['success' => true, 'message' => 'Candidatura realizada com sucesso!']);             
        exit;         
    } catch (PDOException $e) {             
        echo json_encode(['success' => false, 'message' => 'Erro ao processar candidatura.']);             
        exit;         
    }     
}  

// Processar busca de detalhes da vaga via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === 'buscar_vaga') {
    header('Content-Type: application/json');
    
    if (!isset($_GET['id_vaga'])) {
        echo json_encode(['success' => false, 'message' => 'Vaga não especificada.']);
        exit;
    }
    
    // Sanitizar e validar id_vaga
    $id_vaga = filter_var($_GET['id_vaga'], FILTER_VALIDATE_INT);
    if ($id_vaga === false || $id_vaga <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID da vaga inválido.']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT v.*, a.nome_area 
            FROM Vagas v
            LEFT JOIN AreaAtuacao a ON v.id_area = a.id_area
            WHERE v.id_vaga = ? AND v.ativa = 1
        ");
        $stmt->execute([$id_vaga]);
        $vaga = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($vaga) {
            // Sanitizar dados da vaga antes de retornar
            $vaga['titulo_vaga'] = htmlspecialchars($vaga['titulo_vaga'], ENT_QUOTES, 'UTF-8');
            $vaga['empresa'] = htmlspecialchars($vaga['empresa'], ENT_QUOTES, 'UTF-8');
            $vaga['descricao'] = htmlspecialchars($vaga['descricao'], ENT_QUOTES, 'UTF-8');
            $vaga['requisitos'] = htmlspecialchars($vaga['requisitos'], ENT_QUOTES, 'UTF-8');
            $vaga['beneficios'] = htmlspecialchars($vaga['beneficios'], ENT_QUOTES, 'UTF-8');
            $vaga['localizacao'] = htmlspecialchars($vaga['localizacao'], ENT_QUOTES, 'UTF-8');
            $vaga['tipo_emprego'] = htmlspecialchars($vaga['tipo_emprego'], ENT_QUOTES, 'UTF-8');
            $vaga['nome_area'] = htmlspecialchars($vaga['nome_area'], ENT_QUOTES, 'UTF-8');
            
            // Formatar o salário se existir
            if ($vaga['salario']) {
                $vaga['salario_formatado'] = 'R$ ' . number_format($vaga['salario'], 2, ',', '.');
            }
            
            // Formatar a data de encerramento se existir
            if ($vaga['data_encerramento']) {
                $vaga['data_encerramento_formatada'] = date('d/m/Y', strtotime($vaga['data_encerramento']));
            }
            
            echo json_encode(['success' => true, 'vaga' => $vaga]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Vaga não encontrada.']);
        }
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar vaga.']);
        exit;
    }
}

// Handler AJAX para verificar atualizações de status das candidaturas
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === 'verificar_status_atualizacoes') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['id_usuario'])) {
        echo json_encode(['success' => false, 'message' => 'Usuário não logado.']);
        exit;
    }
    
    try {
        // Buscar candidaturas que mudaram de status recentemente (últimos 5 segundos) E estão ativas
        $stmt = $pdo->prepare("
            SELECT c.id_candidatura, c.status, v.titulo_vaga, c.data_atualizacao_status
            FROM Candidatura c
            JOIN Perfil p ON c.id_perfil = p.id_perfil
            JOIN Vagas v ON c.id_vaga = v.id_vaga
            WHERE p.id_usuario = ?
            AND c.ativo = 1
            AND c.status IN ('Aprovado', 'Reprovado')
            AND c.data_atualizacao_status IS NOT NULL
            AND DATEDIFF(second, c.data_atualizacao_status, GETDATE()) <= 5
            ORDER BY c.data_atualizacao_status DESC
        ");
        
        $stmt->execute([$_SESSION['id_usuario']]);
        $atualizacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Sanitizar dados das atualizações
        foreach ($atualizacoes as &$atualizacao) {
            $atualizacao['status'] = htmlspecialchars($atualizacao['status'], ENT_QUOTES, 'UTF-8');
            $atualizacao['titulo_vaga'] = htmlspecialchars($atualizacao['titulo_vaga'], ENT_QUOTES, 'UTF-8');
        }
        
        echo json_encode([
            'success' => true,
            'atualizacoes' => $atualizacoes
        ]);
        exit;
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao verificar atualizações.']);
        exit;
    }
}
// Processar inativação de candidatura via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'inativar_candidatura') {
    header('Content-Type: application/json');
    
    if (!isset($_POST['id_candidatura'])) {
        echo json_encode(['success' => false, 'message' => 'Candidatura não especificada.']);
        exit;
    }
    
    // Sanitizar e validar id_candidatura
    $id_candidatura = filter_var($_POST['id_candidatura'], FILTER_VALIDATE_INT);
    if ($id_candidatura === false || $id_candidatura <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID da candidatura inválido.']);
        exit;
    }
    
    try {
        // Verificar se a candidatura pertence ao usuário logado
        $stmt = $pdo->prepare("
            SELECT c.id_candidatura 
            FROM Candidatura c
            JOIN Perfil p ON c.id_perfil = p.id_perfil
            WHERE c.id_candidatura = ? AND p.id_usuario = ?
        ");
        $stmt->execute([$id_candidatura, $_SESSION['id_usuario']]);
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Candidatura não encontrada ou não pertence ao usuário.']);
            exit;
        }
        
        // Atualizar candidatura para inativa (ativo = 0)
        $stmt = $pdo->prepare("UPDATE Candidatura SET ativo = 0 WHERE id_candidatura = ?");
        $stmt->execute([$id_candidatura]);
        
        echo json_encode(['success' => true, 'message' => 'Candidatura cancelada com sucesso.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao inativar candidatura.']);
        exit;
    }
}

// Buscar vagas com base no termo de pesquisa 
$termoBusca = '';
if (isset($_GET['search'])) {
    $termoBusca = filter_var($_GET['search'], FILTER_SANITIZE_SPECIAL_CHARS);
    $termoBusca = trim($termoBusca);
    
    // Validar se não está vazio após sanitização
    if (empty($termoBusca)) {
        $termoBusca = '';
    }
}

$vagas = [];  

try {     
    if (!empty($termoBusca)) {         
        $stmt = $pdo->prepare("SELECT v.*, a.nome_area                                
                               FROM Vagas v                               
                               LEFT JOIN AreaAtuacao a ON v.id_area = a.id_area                               
                               WHERE (v.titulo_vaga LIKE ? OR a.nome_area LIKE ?) AND v.ativa = 1                               
                               ORDER BY v.id_vaga DESC");         
        $termoLike = "%$termoBusca%";         
        $stmt->execute([$termoLike, $termoLike]);     
    } else {         
        $stmt = $pdo->query("SELECT v.*, a.nome_area                              
                             FROM Vagas v                             
                             LEFT JOIN AreaAtuacao a ON v.id_area = a.id_area    
                             WHERE v.ativa = 1                         
                             ORDER BY v.id_vaga DESC");     
    }      
    
    $vagas = $stmt->fetchAll(PDO::FETCH_ASSOC); 
} catch (PDOException $e) {     
    echo "<script>alert('Erro ao buscar vagas.');</script>"; 
}  

// Buscar candidaturas ATIVAS do usuário logado 
$candidaturas_usuario = []; 
if (isset($_SESSION['id_usuario'])) {     
    try {         
    $stmt = $pdo->prepare("             
        SELECT c.id_vaga              
        FROM Candidatura c             
        JOIN Perfil p ON c.id_perfil = p.id_perfil             
        WHERE p.id_usuario = ?         
    ");         
    $stmt->execute([$_SESSION['id_usuario']]);         
    $candidaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);          
    
    foreach ($candidaturas as $candidatura) {             
        $candidaturas_usuario[] = $candidatura['id_vaga'];         
    }     
} catch (PDOException $e) {         
    // Tratar erro se necessário
}
}

// Sanitizar termo de busca para exibição
$termoBuscaDisplay = htmlspecialchars($termoBusca, ENT_QUOTES, 'UTF-8');
?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProLink - Oportunidades</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(to bottom, #050a37, #0e1768);
            color: #fff;
        }

        /* Section - Oportunidades de Emprego */
        .job-opportunities {
            padding: 40px;
            background-color: #f9f9f9;
        }

        .job-opportunities h2 {
            font-size: 2em;
            margin-bottom: 20px;
        }

        .search-filter-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 20px;
            /* Adiciona espaçamento entre os elementos */
        }

        .search-filter-container {
            display: flex;
            align-items: center;
            /* Isso alinha os itens verticalmente */
            margin-bottom: 20px;
            gap: 10px;
            /* Espaçamento entre os elementos */
        }

        .search-bar {
            flex-grow: 2;
            padding: 10px;
            font-size: 1em;
            border-radius: 5px;
            border: 1px solid #ccc;
            height: 40px;
            /* Altura fixa para alinhar com o botão */
            box-sizing: border-box;
            /* Garante que padding não afete a altura total */
        }

        .search-btn {
            padding: 0 20px;
            /* Reduzi o padding vertical para melhor controle */
            font-size: 1em;
            background-color: #0e1768;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            height: 40px;
            /* Mesma altura da barra de pesquisa */
            white-space: nowrap;
            /* Evita quebra de texto */
        }

        /* Manter o hover effect */
        .search-btn:hover {
            background-color: #3b6ebb;
        }

        /* Job Listings */
        .job-listings {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .job-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            align-items: center;
        }

        .job-card h3 {
            margin: 0;
            color: #333;
            font-size: 1.2em;
        }

        .job-card p {
            color: #333;
            font-size: 0.9em;
            margin: 5px 0;
        }

        .job-card .job-link {
            display: inline-block;
            margin-top: 10px;
            color: #333;
            text-decoration: none;
            transition: color 0.3s;
        }

        .job-card .job-link:hover {
            color: #0e1768;
            text-decoration: underline;
        }

        .more-info-btn,
        .apply-btn {
            padding: 8px 16px;
            background-color: #0e1768;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s;
        }

        .more-info-btn:hover,
        .apply-btn:hover {
            background-color: #3b6ebb;
        }

        .already-applied {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            margin-top: 10px;
            cursor: default;
        }

        /* Saved Jobs Section */
        .saved-jobs-section {
            padding: 40px;
            background-color: #f9f9f9;
            color: #333;
        }

        .saved-jobs-container {
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }

        .saved-jobs-title {
            font-size: 1.5em;
            margin-bottom: 20px;
            color: #333;
        }

        .saved-jobs-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .saved-job-card {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #0e1768;
        }

        .saved-job-card h4 {
            margin: 0;
            color: #333;
            font-size: 1.1em;
        }

        .saved-job-card p {
            color: #555;
            font-size: 0.9em;
            margin: 5px 0;
        }

        .saved-job-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            margin-top: 5px;
        }

        .status-pendente {
            background-color: #FFF3CD;
            color: #856404;
        }

        .status-aprovada {
            background-color: #D4EDDA;
            color: #155724;
        }

        .status-recusada {
            background-color: #F8D7DA;
            color: #721C24;
        }

        .modal-section {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
        }

        .modal-section h4 {
            color: #000000;
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 10px 0;
            padding: 0;
        }

        .modal-description,
        .modal-text {
            color: #000000;
            font-size: 14px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        /* Estilos específicos para cada seção */
        #modal-descricao {
            color: #000000;
        }

        #modal-requisitos {
            color: #000000;
        }

        #modal-beneficios {
            color: #000000;
        }

        /* Garantir que elementos filhos também sejam pretos */
        .modal-section * {
            color: #000000 !important;
        }

        /* Alternativa mais específica se necessário */
        .modal-section h4,
        .modal-section .modal-description,
        .modal-section .modal-text,
        #modal-descricao,
        #modal-requisitos,
        #modal-beneficios {
            color: #000000 !important;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s linear 0.25s, opacity 0.25s 0s;
        }

        .modal-overlay.active {
            visibility: visible;
            opacity: 1;
            transition-delay: 0s;
        }

        .modal-content {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #777;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: #333;
        }

        .modal-title {
            color: #333;
            font-size: 1.5em;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .modal-description {
            color: #444;
            margin-bottom: 20px;
        }

        .modal-info {
            margin-bottom: 5px;
            color: #555;
        }

        .modal-info strong {
            color: #333;
        }

        .modal-actions {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .modal-btn-cancel {
            padding: 8px 16px;
            background-color: #e0e0e0;
            color: #333;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .modal-btn-cancel:hover {
            background-color: #d0d0d0;
        }

        /* Confirmation dialog styles */
        .confirm-dialog {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .confirm-title {
            color: #333;
            font-size: 1.3em;
            margin-bottom: 15px;
        }

        .confirm-message {
            margin-bottom: 20px;
            color: #555;
        }

        .confirm-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .confirm-yes {
            padding: 8px 20px;
            background-color: #0e1768;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .confirm-no {
            padding: 8px 20px;
            background-color: #e0e0e0;
            color: #333;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        /* Toast message style */
        .toast-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 2000;
            display: none;
        }

        /* Contact Section */
        .contact-section {
            padding: 40px;
            background-color: #ffffff;
            text-align: center;
        }

        .contact-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 40px;
        }

        .contact-info p {
            margin: 0;
        }

        .small-hr {
            width: 80px;
            border: none;
            border-top: 2px solid #ccc;
            margin: 10px auto;
        }

        .map-container {
            border-radius: 15px;
        }

        /* Estilos para menu responsivo */
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

        /* Estilo modificado para o botão de fechar */
        .menu-close-item {
            display: none;
            /* Inicialmente oculto */
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px;
            background-color: rgba(14, 23, 104, 0.8);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 1500;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .menu-close-item .menu-icon {
            width: 24px;
            height: 24px;
            transform: rotate(45deg);
            /* Rotacionar para formar um X */
        }

        .cancel-application-btn {
        padding: 6px 12px;
        background-color: #dc3545;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin-top: 10px;
        font-size: 0.9em;
        transition: background-color 0.3s;
        }

        .saved-job-card p strong {
            color: #333;
            font-weight: 600;
        }

        .saved-job-card p[data-date] {
            font-size: 0.85em;
            color: #666;
            margin-top: 8px;
        }

        .cancel-application-btn:hover {
        background-color: #c82333;
        }

        @media (max-width: 991px) {
            body {
                font-size: 14px;
            }

            .job-opportunities,
            .saved-jobs-section {
                padding: 20px;
            }

            .job-listings,
            .saved-jobs-list {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .job-card,
            .saved-job-card {
                padding: 15px;
                margin-bottom: 15px;
            }

            .search-filter-container {
                flex-direction: column;
            }

            .search-bar,
            .search-btn {
                width: 100%;
                margin-bottom: 10px;
            }
             .profile-icon{
                display: none;
            }

            .modal-content {
                width: 95%;
                padding: 15px;
            }
        }

        @media (max-width: 576px) {
            body {
                font-size: 13px;
            }

            .job-opportunities h2,
            .saved-jobs-title {
                font-size: 1.3rem;
                margin-bottom: 15px;
            }

            .job-card h3,
            .saved-job-card h4 {
                font-size: 1.1rem;
            }

            .more-info-btn,
            .apply-btn,
            .already-applied {
                padding: 8px 12px;
                font-size: 0.9rem;
            }

            .contact-container {
                flex-direction: column;
            }

            .map-container iframe {
                width: 100%;
                height: 200px;
            }
             .profile-icon{
                display: none;
            }
        }

        /* Adicione estas regras para corrigir problemas específicos */
        .saved-jobs-container {
            width: 100%;
            box-sizing: border-box;
        }

        .saved-jobs-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .saved-job-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .job-card p,
        .saved-job-card p {
            margin: 5px 0;
            line-height: 1.4;
        }

        /* Corrige o texto "Você ainda não se candidatou..." */
        .saved-jobs-container>p {
            text-align: center;
            padding: 20px;
            color: #666;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }

        /* Garante que os botões tenham tamanho consistente */
        .more-info-btn,
        .apply-btn,
        .already-applied {
            min-width: 120px;
            text-align: center;
            display: inline-block;
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

        .menu.active li:nth-child(1) {
            animation-delay: 0.1s;
        }

        .menu.active li:nth-child(2) {
            animation-delay: 0.2s;
        }

        .menu.active li:nth-child(3) {
            animation-delay: 0.3s;
        }

        .menu.active li:nth-child(4) {
            animation-delay: 0.4s;
        }
        .status-pendente {
            background-color: #FFF3CD;
            color: #856404;
            border-left: 4px solid #FFC107;
        }

        .status-aprovado {
            background-color: #D4EDDA;
            color: #155724;
            border-left: 4px solid #28A745;
        }

        .status-reprovado {
            background-color: #F8D7DA;
            color: #721C24;
            border-left: 4px solid #DC3545;
        }

        /* Animação de fade-out para candidaturas expiradas */
        .candidatura-expirando {
            opacity: 0.7;
            position: relative;
            overflow: hidden;
        }

        .candidatura-expirando::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ff6b6b, #feca57);
            animation: countdown-bar 3s linear;
        }

        @keyframes countdown-bar {
            from { width: 100%; }
            to { width: 0%; }
        }

        /* Indicador de tempo restante */
        .tempo-restante {
            font-size: 0.8em;
            color: #666;
            font-style: italic;
            margin-top: 5px;
        }

        .tempo-restante.urgente {
            color: #dc3545;
            font-weight: bold;
        }

        /* Badge de novo status */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            margin-left: 8px;
            animation: pulse 2s infinite;
        }

        .status-badge.aprovado {
            background-color: #28a745;
            color: white;
        }

        .status-badge.reprovado {
            background-color: #dc3545;
            color: white;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Notificação de status */
        .status-notification {
            position: fixed;
            top: 80px;
            right: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-width: 300px;
            z-index: 2001;
            display: none;
        }

        .status-notification.show {
            display: block;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification-close {
            position: absolute;
            top: 5px;
            right: 8px;
            cursor: pointer;
            color: #999;
            font-size: 18px;
        }

        .notification-close:hover {
            color: #333;
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
                <li><a href="../php/pagina_webinar.php">Webinars</a></li>
                <li><a href="#contato">Contato</a></li>
                <?php if (!isset($_SESSION['usuario_logado'])): ?>
                    <li><a href="../pages/login.html">Login</a></li>
                <?php endif; ?>
            </ul>
            <div class="profile">
            </div>
        </nav>
    </header>

    <div id="close-menu" class="menu-close-item">
        <img src="../assets/img/icons8-menu-48.png" alt="Fechar" class="menu-icon">
    </div>

    <?php if (isset($_SESSION['id_usuario'])): ?>
        <!-- Seção de vagas salvas/candidatadas -->
        <section class="saved-jobs-section">
            <div class="saved-jobs-container">
                <h2 class="saved-jobs-title">Minhas Candidaturas</h2>

            <?php
                // Buscar APENAS candidaturas ATIVAS (ativo = 1) - SEM FILTROS DE TEMPO
               try {
                   $stmt = $pdo->prepare("
                       SELECT c.*, v.titulo_vaga, v.tipo_emprego, v.localizacao, v.empresa, a.nome_area
                       FROM Candidatura c
                       JOIN Perfil p ON c.id_perfil = p.id_perfil
                       JOIN Vagas v ON c.id_vaga = v.id_vaga
                       LEFT JOIN AreaAtuacao a ON v.id_area = a.id_area
                       WHERE p.id_usuario = ?
                       AND c.ativo = 1
                       ORDER BY 
                           CASE c.status 
                               WHEN 'Aprovado' THEN 1 
                               WHEN 'Reprovado' THEN 2 
                               WHEN 'Pendente' THEN 3 
                           END,
                           c.data_atualizacao_status DESC,
                           c.data_candidatura DESC
                   ");
                   $stmt->execute([$_SESSION['id_usuario']]);
                   $minhas_candidaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                     if (empty($minhas_candidaturas)): ?>
                        <p>Você ainda não se candidatou a nenhuma vaga.</p>
                    <?php else: ?>
                        <div class="saved-jobs-list">
                            <?php foreach ($minhas_candidaturas as $candidatura): 
                    $classes = ['saved-job-card', 'status-' . strtolower($candidatura['status'])];
                    
                    // Sanitizar dados da candidatura
                    $titulo_vaga = htmlspecialchars($candidatura['titulo_vaga'], ENT_QUOTES, 'UTF-8');
                    $empresa = htmlspecialchars($candidatura['empresa'], ENT_QUOTES, 'UTF-8');
                    $nome_area = htmlspecialchars($candidatura['nome_area'] ?? 'Área não especificada', ENT_QUOTES, 'UTF-8');
                    $tipo_emprego = htmlspecialchars($candidatura['tipo_emprego'], ENT_QUOTES, 'UTF-8');
                    $localizacao = htmlspecialchars($candidatura['localizacao'] ?? 'Local não especificado', ENT_QUOTES, 'UTF-8');
                    $status = htmlspecialchars($candidatura['status'], ENT_QUOTES, 'UTF-8');
                    $id_candidatura = filter_var($candidatura['id_candidatura'], FILTER_VALIDATE_INT);
                    
                    // Formatando a data da candidatura
                    $data_candidatura = date('d/m/Y', strtotime($candidatura['data_candidatura']));
                ?>
                    <div class="<?= implode(' ', $classes) ?>" 
                        data-candidatura-id="<?= $id_candidatura ?>">
                        <h4>
                            <?= $titulo_vaga ?>
                            <?php if ($candidatura['status'] !== 'Pendente'): ?>
                                <span class="status-badge <?= strtolower($candidatura['status']) ?>">
                                    <?= $status ?>
                                </span>
                            <?php endif; ?>
                        </h4>
                        <p><strong>Empresa:</strong> <?= $empresa ?></p>
                        <p><?= $nome_area ?></p>
                        <p><?= $tipo_emprego ?> - <?= $localizacao ?></p>
                        <p><strong>Data da Candidatura:</strong> <?= $data_candidatura ?></p>
                        
                        <span class="saved-job-status status-<?= strtolower($candidatura['status']) ?>">
                            <?= $status ?>
                        </span>
                        <button class="cancel-application-btn" data-candidatura-id="<?= $id_candidatura ?>">
                            Apagar Registro
                        </button>
                    </div>
                <?php endforeach; ?>
                        </div>
                <?php endif;
                } catch (PDOException $e) {
                    echo "<p>Erro ao carregar candidaturas.</p>";
                }
                ?>
            </div>
        </section>
    <?php endif; ?>


      <div class="status-notification" id="statusNotification">
        <span class="notification-close" onclick="closeNotification()">&times;</span>
        <div id="notificationContent"></div>
    </div>

    <section id="job-opportunities" class="job-opportunities">
        <h2>Oportunidades de Emprego</h2>

        <form method="GET" action="">
            <div class="search-filter-container">
                <input type="text" name="search" id="searchInput" class="search-bar"
                    placeholder="Pesquisar por título da vaga ou área de atuação..."
                    value="<?= $termoBuscaDisplay ?>">
                <button type="submit" class="search-btn">Procurar</button>
            </div>
        </form>

        <!-- Lista de oportunidades -->
        <div class="job-listings">
            <?php if (empty($vagas)): ?>
                <p>Nenhuma vaga encontrada.</p>
            <?php else: ?>
                <?php foreach ($vagas as $vaga): 
                    // Sanitizar dados da vaga
                    $titulo_vaga = htmlspecialchars($vaga['titulo_vaga'], ENT_QUOTES, 'UTF-8');
                    $empresa = htmlspecialchars($vaga['empresa'], ENT_QUOTES, 'UTF-8');
                    $nome_area = htmlspecialchars($vaga['nome_area'] ?? 'Área não especificada', ENT_QUOTES, 'UTF-8');
                    $tipo_emprego = htmlspecialchars($vaga['tipo_emprego'], ENT_QUOTES, 'UTF-8');
                    $localizacao = htmlspecialchars($vaga['localizacao'] ?? 'Não especificado', ENT_QUOTES, 'UTF-8');
                    $id_vaga = filter_var($vaga['id_vaga'], FILTER_VALIDATE_INT);
                ?>
                    <div class="job-card">
                        <h3><?= $titulo_vaga ?></h3>
                        <p><strong>Empresa:</strong> <?= $empresa ?></p>
                        <p><?= $nome_area ?></p>
                        <p>Tipo: <?= $tipo_emprego ?></p>
                        <p>Localização: <?= $localizacao ?></p>
                        <?php if ($vaga['salario']): ?>
                            <p><strong>Salário:</strong> R$ <?= number_format($vaga['salario'], 2, ',', '.') ?></p>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['id_usuario'])): ?>
                            <?php if (in_array($vaga['id_vaga'], $candidaturas_usuario)): ?>
                                <button class="already-applied" disabled>Candidatura Enviada</button>
                            <?php else: ?>
                                <button class="more-info-btn" data-vaga-id="<?= $id_vaga ?>">Saiba Mais</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <p><a href="../pages/login.html" class="job-link">Faça login para se candidatar</a></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Modal para detalhes da vaga -->
    <div class="modal-overlay" id="job-modal">
        <div class="modal-content">
            <span class="modal-close" id="modal-close">&times;</span>
            <h3 class="modal-title" id="modal-title">Título da Vaga</h3>

            <div class="modal-info-grid">
                <p class="modal-info"><strong>Empresa:</strong> <span id="modal-empresa">Empresa</span></p>
                <p class="modal-info"><strong>Área:</strong> <span id="modal-area">Área</span></p>
                <p class="modal-info"><strong>Tipo:</strong> <span id="modal-tipo">Tipo</span></p>
                <p class="modal-info"><strong>Localização:</strong> <span id="modal-localizacao">Localização</span></p>
                <p class="modal-info" id="modal-salario-container"><strong>Salário:</strong> <span id="modal-salario">Não informado</span></p>
                <p class="modal-info" id="modal-encerramento-container"><strong>Data de Encerramento:</strong> <span id="modal-encerramento">Não informado</span></p>
            </div>

            <div class="modal-section">
                <h4>Descrição da Vaga:</h4>
                <div class="modal-description" id="modal-descricao">
                    Descrição detalhada da vaga...
                </div>
            </div>

            <div class="modal-section" id="modal-requisitos-section">
                <h4>Requisitos:</h4>
                <div class="modal-text" id="modal-requisitos">
                    Requisitos da vaga...
                </div>
            </div>

            <div class="modal-section" id="modal-beneficios-section">
                <h4>Benefícios:</h4>
                <div class="modal-text" id="modal-beneficios">
                    Benefícios oferecidos...
                </div>
            </div>

            <div class="modal-actions">
                <button class="modal-btn-cancel" id="modal-btn-cancel">Fechar</button>
                <button class="apply-btn" id="modal-apply-btn" data-vaga-id="">Candidatar-se</button>
            </div>
        </div>
    </div>

    <!-- Modal de confirmação -->
    <div class="modal-overlay" id="confirm-modal">
        <div class="confirm-dialog">
            <h3 class="confirm-title">Confirmar Candidatura</h3>
            <p class="confirm-message">Deseja realmente se candidatar a esta vaga?</p>
            <div class="confirm-actions">
                <button class="confirm-no" id="confirm-no">Cancelar</button>
                <button class="confirm-yes" id="confirm-yes" data-vaga-id="">Confirmar</button>
            </div>
        </div>
    </div>

    <section id="contato" class="contact-section">
        <div class="contact-container">
            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3656.3465896377126!2d-46.64165882513707!3d-23.53003478469527!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94ce5857a5c48815%3A0x70b13f63e8491df3!2sETESP!5e0!3m2!1spt-BR!2sbr!4v1696952749192!5m2!1spt-BR!2sbr" width="400" height="300" style="border:0; border-radius: 15px;" allowfullscreen="" loading="lazy"></iframe>
            </div>
            <div class="contact-info">
                <p>Bom Retiro, São Paulo - SP, 01124-010<br>ETESP</p>
                <hr class="small-hr">
                <p>Email: contato@empresa.com<br>Telefone: (11) 1234-5678</p>
            </div>
        </div>
    </section>

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
    // ===== PROTEÇÃO CONTRA MANIPULAÇÃO DE INPUTS (Adicionado do primeiro código) =====
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
        if (searchInput.closest('form')) {
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
    }
    
    // Função para validar conteúdo baseado no tipo esperado
    function validarConteudoPorTipo(input, tipoEsperado) {
        const valor = input.value;
        
        // Para campo de busca, permitir apenas caracteres seguros
        const regexTexto = /^[\w\sáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\-.,;:!?@#%&*()+=]*$/;
        if (!regexTexto.test(valor)) {
            input.value = valor.replace(/[^\w\sáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\-.,;:!?@#%&*()+=]/g, '');
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

    // ===== CÓDIGO ORIGINAL CONTINUA AQUI =====
    // Verificar mudanças de status periodicamente (apenas para notificações)
    checkStatusUpdates();
    setInterval(checkStatusUpdates, 30000); // Verifica a cada 30 segundos

    // Modal de detalhes da vaga
    $('.more-info-btn').on('click', function() {
        const vagaId = $(this).data('vaga-id');

        $.ajax({
            type: 'GET',
            url: window.location.href,
            data: {
                ajax: 'buscar_vaga',
                id_vaga: vagaId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const vaga = response.vaga;
                    
                    $('#modal-title').text(vaga.titulo_vaga);
                    $('#modal-empresa').text(vaga.empresa);
                    $('#modal-area').text(vaga.nome_area || 'Área não especificada');
                    $('#modal-tipo').text(vaga.tipo_emprego);
                    $('#modal-localizacao').text(vaga.localizacao || 'Localização não especificada');
                    $('#modal-descricao').html(vaga.descricao ? vaga.descricao.replace(/\n/g, '<br>') : 'Sem descrição detalhada.');

                    if (vaga.salario_formatado) {
                        $('#modal-salario').text(vaga.salario_formatado);
                        $('#modal-salario-container').show();
                    } else {
                        $('#modal-salario').text('Não informado');
                        $('#modal-salario-container').show();
                    }

                    if (vaga.data_encerramento_formatada) {
                        $('#modal-encerramento').text(vaga.data_encerramento_formatada);
                        $('#modal-encerramento-container').show();
                    } else {
                        $('#modal-encerramento').text('Não informado');
                        $('#modal-encerramento-container').show();
                    }

                    if (vaga.requisitos) {
                        $('#modal-requisitos').html(vaga.requisitos.replace(/\n/g, '<br>'));
                        $('#modal-requisitos-section').show();
                    } else {
                        $('#modal-requisitos-section').hide();
                    }

                    if (vaga.beneficios) {
                        $('#modal-beneficios').html(vaga.beneficios.replace(/\n/g, '<br>'));
                        $('#modal-beneficios-section').show();
                    } else {
                        $('#modal-beneficios-section').hide();
                    }

                    $('#modal-apply-btn').data('vaga-id', vagaId);
                    $('#job-modal').addClass('active');
                } else {
                    alert(response.message || 'Erro ao carregar detalhes da vaga.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro AJAX:', error);
                alert('Erro de conexão ao carregar detalhes da vaga.');
            }
        });
    });

    // Fechar modal
    $('#modal-close, #modal-btn-cancel').on('click', function() {
        $('#job-modal').removeClass('active');
    });

    // Candidatar-se
    $('#modal-apply-btn').on('click', function() {
        const vagaId = $(this).data('vaga-id');
        $('#confirm-yes').data('vaga-id', vagaId);
        $('#confirm-modal').addClass('active');
    });

    // Confirmação de candidatura
    $('#confirm-yes').on('click', function() {
        const vagaId = $(this).data('vaga-id');
        
        $.ajax({
            type: 'POST',
            url: window.location.href,
            data: {
                ajax: 'candidatura',
                id_vaga: vagaId
            },
            dataType: 'json',
            success: function(response) {
                $('#confirm-modal').removeClass('active');
                $('#job-modal').removeClass('active');
                
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message || 'Erro ao processar candidatura.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro AJAX:', error);
                $('#confirm-modal').removeClass('active');
                $('#job-modal').removeClass('active');
                alert('Erro de conexão ao processar candidatura.');
            }
        });
    });

    // Cancelar confirmação
    $('#confirm-no').on('click', function() {
        $('#confirm-modal').removeClass('active');
    });

    // Fechar modal clicando fora
    $('.modal-overlay').on('click', function(e) {
        if (e.target === this) {
            $(this).removeClass('active');
        }
    });

    // Fechar modal com tecla ESC
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.modal-overlay.active').removeClass('active');
        }
    });

    // Cancelar candidatura
    $(document).on('click', '.cancel-application-btn', function() {
        const candidaturaId = $(this).data('candidatura-id');
        const card = $(this).closest('.saved-job-card');
        
        if (confirm('Tem certeza que deseja apagar o registro dessa candidatura?')) {
            $.ajax({
                type: 'POST',
                url: window.location.href,
                data: {
                    ajax: 'inativar_candidatura',
                    id_candidatura: candidaturaId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Adiciona animação de fade out e remove o card
                        card.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Mostra mensagem se não houver mais candidaturas
                            if ($('.saved-job-card').length === 0) {
                                $('.saved-jobs-list').html('<p>Você não possui candidaturas ativas.</p>');
                            }
                        });
                        
                        // Mostra notificação de sucesso
                        showCustomNotification('Registro apagado com sucesso!', 'success');
                    } else {
                        showCustomNotification(response.message || 'Erro ao apagar registro da candidatura.', 'error');
                    }
                },
                error: function() {
                    showCustomNotification('Erro de conexão ao cancelar candidatura.', 'error');
                }
            });
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Verificar se os elementos necessários existem
    let navbar = document.querySelector('.navbar');
    let menu = document.getElementById('menu');
    let mobileMenuBtn = document.getElementById('mobile-menu');
    let closeMenuBtn = document.getElementById('close-menu');
    
    // Se não existir navbar, tentar encontrar um container alternativo
    if (!navbar) {
        navbar = document.querySelector('nav') || document.querySelector('.nav-container');
    }
    
    // Criar botão do menu mobile se não existir
    if (navbar && !mobileMenuBtn) {
        mobileMenuBtn = document.createElement('button');
        mobileMenuBtn.id = 'mobile-menu';
        mobileMenuBtn.className = 'menu-toggle';
        mobileMenuBtn.innerHTML = '<img src="../assets/img/icons8-menu-48.png" alt="Menu" class="menu-icon">';
        mobileMenuBtn.style.display = 'none'; // Inicialmente oculto
        navbar.appendChild(mobileMenuBtn);
    }
    
    // Criar botão de fechar se não existir
    if (!closeMenuBtn && menu) {
        closeMenuBtn = document.createElement('button');
        closeMenuBtn.id = 'close-menu';
        closeMenuBtn.className = 'menu-close-item';
        closeMenuBtn.innerHTML = '<img src="../assets/img/icons8-menu-48.png" alt="Fechar Menu" class="menu-icon">';
        closeMenuBtn.style.display = 'none';
        document.body.appendChild(closeMenuBtn);
    }
    
    // Função para mostrar/ocultar elementos baseado no tamanho da tela
    function adjustMenuDisplay() {
        const isMobile = window.innerWidth < 992;
        
        if (mobileMenuBtn) {
            mobileMenuBtn.style.display = isMobile ? 'block' : 'none';
        }
        
        if (menu) {
            if (!isMobile) {
                menu.classList.remove('active');
                if (closeMenuBtn) {
                    closeMenuBtn.style.display = 'none';
                }
            }
        }
    }
    
    // Event listener para abrir menu mobile
    if (mobileMenuBtn && menu) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            menu.classList.add('active');
            this.style.display = 'none';
            
            if (closeMenuBtn) {
                closeMenuBtn.style.display = 'flex';
            }
        });
    }
    
    // Event listener para fechar menu
    if (closeMenuBtn && menu) {
        closeMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            menu.classList.remove('active');
            this.style.display = 'none';
            
            if (mobileMenuBtn && window.innerWidth < 992) {
                mobileMenuBtn.style.display = 'block';
            }
        });
    }
    
    // Fechar menu ao clicar em links (apenas em mobile)
    if (menu) {
        const menuLinks = menu.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    menu.classList.remove('active');
                    
                    if (closeMenuBtn) {
                        closeMenuBtn.style.display = 'none';
                    }
                    
                    if (mobileMenuBtn) {
                        mobileMenuBtn.style.display = 'block';
                    }
                }
            });
        });
    }
    
    // Fechar menu ao clicar fora dele (apenas em mobile)
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 992 && menu && menu.classList.contains('active')) {
            const isClickInsideMenu = menu.contains(e.target);
            const isClickOnMenuButton = mobileMenuBtn && mobileMenuBtn.contains(e.target);
            const isClickOnCloseButton = closeMenuBtn && closeMenuBtn.contains(e.target);
            
            if (!isClickInsideMenu && !isClickOnMenuButton && !isClickOnCloseButton) {
                menu.classList.remove('active');
                
                if (closeMenuBtn) {
                    closeMenuBtn.style.display = 'none';
                }
                
                if (mobileMenuBtn) {
                    mobileMenuBtn.style.display = 'block';
                }
            }
        }
    });
    
    // Event listener para redimensionamento da janela
    let resizeTimeout;
    window.addEventListener('resize', function() {
        // Usar debounce para evitar múltiplas execuções
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(adjustMenuDisplay, 100);
    });
    
    // Inicialização
    adjustMenuDisplay();
});

// ===== CSS ADICIONAL PARA CORRIGIR CONFLITOS =====
// Adicionar estilos dinamicamente se necessário


// ===== FUNÇÕES ORIGINAIS MANTIDAS =====
// Função para verificar atualizações de status (apenas para mostrar notificações)
function checkStatusUpdates() {
    if (!window.location.href.includes('oportunidades.php')) return;
    
    $.ajax({
        type: 'GET',
        url: window.location.href,
        data: {
            ajax: 'verificar_status_atualizacoes'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.atualizacoes && response.atualizacoes.length > 0) {
                response.atualizacoes.forEach(function(atualizacao) {
                    showStatusNotification(atualizacao);
                });
            }
        },
        error: function() {
            // Silenciosamente falha para não incomodar o usuário
        }
    });
}

// Função para mostrar notificação de status
function showStatusNotification(atualizacao) {
    const notification = $('#statusNotification');
    const content = $('#notificationContent');
    
    let statusText = atualizacao.status === 'Aprovado' ? 'aprovada' : 'reprovada';
    let statusClass = atualizacao.status === 'Aprovado' ? 'aprovado' : 'reprovado';
    
    content.html(`
        <div class="notification-header">
            <strong>Candidatura ${statusText.charAt(0).toUpperCase() + statusText.slice(1)}!</strong>
        </div>
        <div class="notification-body">
            <p>Sua candidatura para <strong>${atualizacao.titulo_vaga}</strong> foi ${statusText}.</p>
        </div>
    `);
    
    notification.removeClass('aprovado reprovado').addClass(statusClass);
    notification.addClass('show');
}

// Função para fechar notificação
function closeNotification() {
    $('#statusNotification').removeClass('show');
}

</script>
</body>