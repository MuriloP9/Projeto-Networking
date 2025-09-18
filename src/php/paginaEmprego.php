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

// Processar filtros de pesquisa aprimorados
$termoBusca = isset($_GET['search']) ? filter_var($_GET['search'], FILTER_SANITIZE_SPECIAL_CHARS) : '';
$areaFiltro = isset($_GET['area']) ? filter_var($_GET['area'], FILTER_VALIDATE_INT) : '';
$tipoEmpregoFiltro = isset($_GET['tipo_emprego']) ? filter_var($_GET['tipo_emprego'], FILTER_SANITIZE_SPECIAL_CHARS) : '';
$localizacaoFiltro = isset($_GET['localizacao']) ? filter_var($_GET['localizacao'], FILTER_SANITIZE_SPECIAL_CHARS) : '';
$salarioMinimo = isset($_GET['salario_min']) ? filter_var($_GET['salario_min'], FILTER_VALIDATE_FLOAT) : '';

// Buscar áreas para o filtro
$areas = [];
try {
    $stmt = $pdo->query("SELECT id_area, nome_area FROM AreaAtuacao ORDER BY nome_area");
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Tratamento de erro silencioso
}

// Buscar tipos de emprego únicos
$tiposEmprego = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT tipo_emprego FROM Vagas WHERE ativa = 1 AND tipo_emprego IS NOT NULL ORDER BY tipo_emprego");
    $tiposEmprego = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Tratamento de erro silencioso
}

// Buscar localizações únicas
$localizacoes = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT localizacao FROM Vagas WHERE ativa = 1 AND localizacao IS NOT NULL ORDER BY localizacao");
    $localizacoes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Tratamento de erro silencioso
}

// Construir query dinâmica com filtros
$vagas = [];
try {
    $whereConditions = ["v.ativa = 1"];
    $params = [];
    
    if (!empty($termoBusca)) {
        $whereConditions[] = "(v.titulo_vaga LIKE ? OR a.nome_area LIKE ? OR v.empresa LIKE ?)";
        $termoLike = "%$termoBusca%";
        $params[] = $termoLike;
        $params[] = $termoLike;
        $params[] = $termoLike;
    }
    
    if (!empty($areaFiltro)) {
        $whereConditions[] = "v.id_area = ?";
        $params[] = $areaFiltro;
    }
    
    if (!empty($tipoEmpregoFiltro)) {
        $whereConditions[] = "v.tipo_emprego = ?";
        $params[] = $tipoEmpregoFiltro;
    }
    
    if (!empty($localizacaoFiltro)) {
        $whereConditions[] = "v.localizacao LIKE ?";
        $params[] = "%$localizacaoFiltro%";
    }
    
    if (!empty($salarioMinimo)) {
        $whereConditions[] = "v.salario >= ?";
        $params[] = $salarioMinimo;
    }
    
    $whereClause = implode(" AND ", $whereConditions);
    
    $sql = "SELECT v.*, a.nome_area 
            FROM Vagas v 
            LEFT JOIN AreaAtuacao a ON v.id_area = a.id_area 
            WHERE $whereClause 
            ORDER BY v.id_vaga DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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

// Sanitizar dados para exibição
$termoBuscaDisplay = htmlspecialchars($termoBusca, ENT_QUOTES, 'UTF-8');
$tipoEmpregoDisplay = htmlspecialchars($tipoEmpregoFiltro, ENT_QUOTES, 'UTF-8');
$localizacaoDisplay = htmlspecialchars($localizacaoFiltro, ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProLink - Oportunidades</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
   <style>
        :root {
            --primary-blue: #0052CC;
            --secondary-blue: #0066FF;
            --accent-blue: #1E90FF;
            --light-blue: #E8F4FD;
            --dark-blue: #003D99;
            --white: #FFFFFF;
            --light-gray: #F8F9FA;
            --gray: #6C757D;
            --dark-gray: #343A40;
            --success: #28A745;
            --warning: #FFC107;
            --danger: #DC3545;
            --gradient-primary: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            --gradient-accent: linear-gradient(135deg, var(--accent-blue) 0%, var(--secondary-blue) 100%);
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 8px 16px rgba(0, 0, 0, 0.15);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #ffffffff 0%, #0098feff 100%);
            color: var(--dark-gray);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Updated Navigation Bar */
        .navbar {
            position: fixed;
            z-index: 1000;
            display: flex;
            width: 100%;
            top: 0;
            left: 0;
            justify-content: space-between;
            align-items: center;
            padding: 15px 50px;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(100, 181, 246, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }

        .navbar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, 
                rgba(100, 181, 246, 0.05) 0%, 
                rgba(59, 110, 187, 0.08) 50%, 
                rgba(100, 181, 246, 0.05) 100%);
            z-index: -1;
        }

        .logo-container {
            display: flex;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .logo-icon {
            width: 45px;
            height: 45px;
            margin-right: 12px;
            filter: drop-shadow(0 4px 8px rgba(100, 181, 246, 0.4));
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .logo {
            font-size: 35px;
            font-weight: 700;
            background: linear-gradient(45deg, #64b5f6, #ffffff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 4px rgba(100, 181, 246, 0.3);
        }

        .menu {
            list-style: none;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .menu li a {
            color: #0a0a0a;
            text-decoration: none;
            padding: 12px 20px;
            background: linear-gradient(135deg, #ffffff 0%, #f0f8ff 100%);
            border-radius: 25px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: block;
            text-align: center;
            font-weight: 600;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(100, 181, 246, 0.2);
            border: 1px solid rgba(100, 181, 246, 0.1);
        }

        .menu li a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(100, 181, 246, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .menu li a:hover::before {
            left: 100%;
        }

        .menu li a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(100, 181, 246, 0.4);
            background: linear-gradient(135deg, #ffffff 0%, #e3f2fd 100%);
        }

        .menu li {
            margin: 0 3px;
        }

        .menu .profile-item a {
            background: transparent;
            padding: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            transform: none !important;
            border: none !important;
        }

        .menu .profile-item a:hover {
            background: transparent !important;
            transform: none !important;
            box-shadow: none !important;
        }

        .menu .profile-item a::before {
            display: none !important;
        }

        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(100, 181, 246, 0.3);
            transition: all 0.3s ease;
            display: block;
            border: 2px solid rgba(100, 181, 246, 0.5);
        }

        .profile-icon:hover {
            box-shadow: 0 4px 15px rgba(100, 181, 246, 0.5);
            transform: scale(1.05);
            border-color: rgba(100, 181, 246, 0.8);
        }

        /* Menu toggle para responsividade */
        .menu-toggle {
            display: none;
            cursor: pointer;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            z-index: 1100;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .menu-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .menu-icon {
            width: 30px;
            height: 30px;
            transition: transform 0.3s ease;
            filter: brightness(0) invert(1);
        }

        .menu-toggle.active .menu-icon {
            transform: rotate(90deg);
        }

        /* Main Content */
        main {
            margin-top: 80px;
            min-height: calc(100vh - 80px);
        }


        /* Hero Section for Opportunities */
        .opportunities-hero {
            background: var(--gradient-primary);
            padding: 4rem 2rem 2rem;
            color: var(--white);
            text-align: center;
        }

        .opportunities-hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .opportunities-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Search and Filters Section */
        .search-filters-section {
            background: var(--white);
            padding: 2rem;
            margin: -2rem 2rem 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 10;
        }

        .filters-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .search-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .search-header h2 {
            color: var(--dark-gray);
            font-size: 1.5rem;
            font-weight: 600;
        }

        .filters-toggle {
            background: var(--light-blue);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            color: var(--primary-blue);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
        }

        .filters-toggle:hover {
            background: var(--primary-blue);
            color: var(--white);
        }

        .search-bar-container {
            position: relative;
            margin-bottom: 1rem;
        }

        .search-bar {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            font-size: 1rem;
            border: 2px solid #E9ECEF;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-family: 'Montserrat', sans-serif;
        }

        .search-bar:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 1.1rem;
        }

        .advanced-filters {
            display: none;
            background: var(--light-gray);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-top: 1rem;
            transition: var(--transition);
        }

        .advanced-filters.active {
            display: block;
            animation: fadeInDown 0.3s ease-out;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-weight: 600;
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        .filter-select, .filter-input {
            padding: 0.75rem;
            border: 1px solid #DDD;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            transition: var(--transition);
        }

        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 2px rgba(0, 82, 204, 0.1);
        }

        .filters-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Montserrat', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: var(--light-gray);
            color: var(--dark-gray);
        }

        .btn-secondary:hover {
            background: #E9ECEF;
        }

        /* Job Opportunities Section */
        .job-opportunities {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .opportunities-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .results-count {
            color: #000000ff;
            font-weight: 500;
        }

        .view-toggle {
            display: flex;
            background: var(--light-gray);
            border-radius: 8px;
            padding: 4px;
        }

        .view-btn {
            padding: 0.5rem 1rem;
            border: none;
            background: none;
            cursor: pointer;
            border-radius: 6px;
            transition: var(--transition);
        }

        .view-btn.active {
            background: var(--white);
            box-shadow: var(--shadow-sm);
            color: var(--primary-blue);
        }

        /* Job Cards */
        .job-listings {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }

        .job-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid #E9ECEF;
            position: relative;
            overflow: hidden;
        }

        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-primary);
        }

        .job-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .job-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-gray);
            margin-bottom: 0.5rem;
        }

        .job-company {
            color: var(--primary-blue);
            font-weight: 600;
            font-size: 1rem;
        }

        .job-badge {
            background: var(--light-blue);
            color: var(--primary-blue);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .job-details {
            margin: 1rem 0;
        }

        .job-detail {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .job-detail i {
            width: 16px;
            color: var(--primary-blue);
        }

        .job-salary {
            background: linear-gradient(135deg, #28A745, #20C997);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            margin: 0.75rem 0;
            display: inline-block;
        }

        .job-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .btn-info {
            background: var(--gradient-accent);
            color: var(--white);
            flex: 1;
        }

        .btn-success {
            background: var(--gradient-primary);
            color: var(--white);
            flex: 1;
        }

        .btn-applied {
            background: var(--success);
            color: var(--white);
            cursor: default;
            flex: 1;
        }

        .btn-login {
            background: var(--gradient-accent);
            color: var(--white);
            flex: 1;
        }

        /* Saved Jobs Section */
        .saved-jobs-section {
            background: var(--white);
            margin: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .saved-jobs-header {
            background: var(--gradient-primary);
            color: var(--white);
            padding: 2rem;
            text-align: center;
        }

        .saved-jobs-header h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .saved-jobs-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .saved-jobs-container {
            padding: 2rem;
        }

        .saved-jobs-list {
            display: grid;
            gap: 1rem;
        }

        .saved-job-card {
            background: var(--white);
            border: 1px solid #E9ECEF;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            transition: var(--transition);
            position: relative;
        }

        .saved-job-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .saved-job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .saved-job-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark-gray);
            margin-bottom: 0.5rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        .status-pendente {
            background: #FFF3CD;
            color: #856404;
            border: 1px solid #FFEAA7;
        }

        .status-aprovado {
            background: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }

        .status-reprovado {
            background: #F8D7DA;
            color: #721C24;
            border: 1px solid #F1AEB5;
        }

        .cancel-application-btn {
            background: var(--danger);
            color: var(--white);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        .cancel-application-btn:hover {
            background: #C82333;
            transform: translateY(-1px);
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            visibility: hidden;
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modal-overlay.active {
            visibility: visible;
            opacity: 1;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            max-width: 700px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
            box-shadow: var(--shadow-lg);
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modal-overlay.active .modal-content {
            transform: scale(1);
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 40px;
            height: 40px;
            background: var(--light-gray);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--gray);
            transition: var(--transition);
        }

        .modal-close:hover {
            background: var(--danger);
            color: var(--white);
        }

        .modal-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark-gray);
            margin-bottom: 1rem;
            padding-right: 3rem;
        }

        .modal-company {
            color: var(--primary-blue);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .modal-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .modal-info-item {
            background: var(--light-gray);
            padding: 1rem;
            border-radius: 8px;
        }

        .modal-info-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .modal-info-value {
            font-weight: 600;
            color: var(--dark-gray);
        }

        .modal-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--light-gray);
            border-radius: var(--border-radius);
        }

        .modal-section h4 {
            color: var(--dark-gray);
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-description,
        .modal-text {
            color: var(--dark-gray);
            line-height: 1.6;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1.5rem;
            border-top: 1px solid #E9ECEF;
        }

        /* Confirmation Dialog */
        .confirm-dialog {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: var(--shadow-lg);
        }

        .confirm-title {
            color: var(--dark-gray);
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .confirm-message {
            color: var(--gray);
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .confirm-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        /* Toast Notifications */
        .status-notification {
            position: fixed;
            top: 100px;
            right: 20px;
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1rem 1.5rem;
            box-shadow: var(--shadow-lg);
            max-width: 350px;
            z-index: 2001;
            display: none;
            border-left: 4px solid var(--primary-blue);
        }

        .status-notification.show {
            display: block;
            animation: slideInRight 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .status-notification.aprovado {
            border-left-color: var(--success);
        }

        .status-notification.reprovado {
            border-left-color: var(--danger);
        }

        @keyframes slideInRight {
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
            top: 0.5rem;
            right: 0.75rem;
            cursor: pointer;
            color: var(--gray);
            font-size: 1.2rem;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }

        .notification-close:hover {
            background: var(--light-gray);
            color: var(--dark-gray);
        }

        /* Contact Section */
        .contact-section {
            background: var(--white);
            padding: 4rem 2rem;
            margin: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
        }

        .contact-container {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .contact-info {
            text-align: left;
        }

        .contact-info h3 {
            color: var(--dark-gray);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .contact-detail {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            color: var(--gray);
        }

        .contact-detail i {
            color: var(--primary-blue);
            font-size: 1.2rem;
            width: 24px;
        }

        .map-container {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .map-container iframe {
            width: 100%;
            height: 300px;
            border: none;
        }

        /* Footer */
        footer {
            background: var(--dark-gray);
            color: var(--white);
            padding: 2rem;
            text-align: center;
            margin-top: 2rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .footer-logo {
            width: 40px;
            height: 40px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--dark-gray);
        }

        /* Animações avançadas */
        @keyframes slideInFromLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInFromRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

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

        @keyframes gradient-rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes slide {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-100px, -100px); }
        }

        /* Aplicação das animações */
        .webinar-description,
        .job-description {
            animation: fadeInUp 0.8s ease forwards;
        }

        .timeline-item {
            animation: slideInFromLeft 0.8s ease forwards;
        }

        .timeline-item:nth-child(even) {
            animation: slideInFromRight 0.8s ease forwards;
        }

        .timeline-item:nth-child(1) { animation-delay: 0.2s; }
        .timeline-item:nth-child(2) { animation-delay: 0.4s; }
        .timeline-item:nth-child(3) { animation-delay: 0.6s; }
        .timeline-item:nth-child(4) { animation-delay: 0.8s; }

        /* Efeitos de hover melhorados */
        .webinar-description:hover,
        .job-description:hover,
        .contact-info:hover {
            animation-play-state: paused;
        }

        /* Menu mobile responsivo mantido */
        @media screen and (max-width: 991px) {
            .navbar {
                padding: 15px 20px;
            }
            
            .logo {
                font-size: 22px;
            }
            
            .logo-icon {
                width: 35px;
                height: 35px;
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
                background: linear-gradient(135deg, #0e1768 0%, #1a2980 100%);
                padding: 80px 20px 20px;
                z-index: 1000;
                justify-content: flex-start;
                overflow-y: auto;
                backdrop-filter: blur(20px);
            }
            
            .menu.active {
                display: flex;
            }
            
            .menu li {
                width: 100%;
                margin: 12px 0;
            }
            
            .menu li a {
                width: 100%;
                text-align: center;
                padding: 15px;
                font-size: 16px;
            }
            
            .menu-close-item {
                display: none;
            }
            
            .profile-item{
                display: block;
            }
            
            .signup-btn {
                margin-top: 15px;
            }
            
            .webinar-container, .job-container, .faq-container {
                flex-direction: column;
                gap: 30px;
            }
            
            .webinar-image, .job-image {
                order: -1;
            }
            
            .job-description {
                order: 1;
            }
            
            .job-image {
                order: 2;
            }
            
            .timeline-container {
                padding: 0 20px;
            }
            
            .timeline-container::before {
                left: 30px;
            }
            
            .timeline-icon {
                width: 60px;
                height: 60px;
                min-width: 60px;
                margin-right: 20px;
            }
            
            .timeline-icon img {
                width: 30px;
                height: 30px;
            }

            /* Ajustes para o conteúdo principal */
            .opportunities-hero h1 {
                font-size: 2.2rem;
            }

            .search-filters-section {
                margin: -1rem 1rem 1rem;
            }

            .job-listings {
                grid-template-columns: 1fr;
            }

            .contact-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .filters-actions {
                flex-direction: column;
            }
        }

        /* Ajustes para tablets */
        @media screen and (min-width: 768px) and (max-width: 991px) {
            .carousel-container {
                height: 380px;
            }
            
            .map-container iframe {
                height: 300px;
            }
            
            .navbar {
                padding: 15px 30px;
            }
        }

        /* Animações suaves para elementos do menu mobile */
        .menu.active li {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }

        .menu.active li:nth-child(1) { animation-delay: 0.1s; }
        .menu.active li:nth-child(2) { animation-delay: 0.2s; }
        .menu.active li:nth-child(3) { animation-delay: 0.3s; }
        .menu.active li:nth-child(4) { animation-delay: 0.4s; }
        .menu.active li:nth-child(5) { animation-delay: 0.5s; }
        .menu.active li:nth-child(6) { animation-delay: 0.6s; }
        .menu.active li:nth-child(7) { animation-delay: 0.7s; }

        /* Melhorias adicionais para interatividade */
        .navbar, .webinar-description, .job-description, .contact-info, .faq-question {
            will-change: transform;
        }

        /* Scrollbar personalizada */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(14, 23, 104, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #0e1768, #3b6ebb);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #3b6ebb, #64b5f6);
        }

        /* Efeitos de loading suaves */
        @keyframes shimmerLoading {
            0% {
                background-position: -200px 0;
            }
            100% {
                background-position: calc(200px + 100%) 0;
            }
        }

        .loading-shimmer {
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            background-size: 200px 100%;
            animation: shimmerLoading 1.5s infinite;
        }

        /* Responsive Design adicional */
        @media (max-width: 576px) {
            .navbar {
                padding: 15px 15px;
            }

            .opportunities-hero {
                padding: 3rem 1rem 1rem;
            }

            .opportunities-hero h1 {
                font-size: 1.8rem;
            }

            .job-opportunities {
                padding: 1rem;
            }

            .modal-content {
                padding: 1.5rem;
                margin: 1rem;
            }

            .modal-info-grid {
                grid-template-columns: 1fr;
            }

            .job-actions {
                flex-direction: column;
            }
        }

        /* Animation classes */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { 
                transform: scale(1);
                opacity: 1; 
            }
            50% { 
                transform: scale(1.05);
                opacity: 0.8; 
            }
        }

        /* Security Warning */
        .security-warning {
            position: fixed;
            top: 100px;
            right: 20px;
            background: var(--danger);
            color: var(--white);
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            z-index: 10000;
            max-width: 300px;
            animation: slideInRight 0.3s ease-out;
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
            </ul>
            
            <button class="menu-toggle" id="mobile-menu">
                <img src="../assets/img/icons8-menu-48.png" alt="Menu" class="menu-icon">
            </button>
        </nav>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="opportunities-hero">
            <h1><i class="fas fa-briefcase"></i> Encontre Sua Oportunidade</h1>
            <p>Descubra vagas que combinam com seu perfil e impulsione sua carreira profissional</p>
        </section>

        <!-- Search and Filters -->
        <section class="search-filters-section">
            <div class="filters-container">
                <div class="search-header">
                    <h2><i class="fas fa-search"></i> Buscar Vagas</h2>
                    <button type="button" class="filters-toggle" id="filters-toggle">
                        <i class="fas fa-filter"></i> Filtros Avançados
                    </button>
                </div>

                <form method="GET" action="" id="search-form">
                    <div class="search-bar-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" id="searchInput" class="search-bar"
                            placeholder="Pesquisar por título, empresa ou palavra-chave..."
                            value="<?= $termoBuscaDisplay ?>">
                    </div>

                    <div class="advanced-filters" id="advanced-filters">
                        <div class="filters-grid">
                            <div class="filter-group">
                                <label class="filter-label">
                                    <i class="fas fa-tag"></i> Área de Atuação
                                </label>
                                <select name="area" class="filter-select">
                                    <option value="">Todas as áreas</option>
                                    <?php foreach ($areas as $area): ?>
                                        <option value="<?= $area['id_area'] ?>" 
                                                <?= $areaFiltro == $area['id_area'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($area['nome_area'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">
                                    <i class="fas fa-clock"></i> Tipo de Emprego
                                </label>
                                <select name="tipo_emprego" class="filter-select">
                                    <option value="">Todos os tipos</option>
                                    <?php foreach ($tiposEmprego as $tipo): ?>
                                        <option value="<?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?>" 
                                                <?= $tipoEmpregoFiltro == $tipo ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">
                                    <i class="fas fa-map-marker-alt"></i> Localização
                                </label>
                                <select name="localizacao" class="filter-select">
                                    <option value="">Todas as localizações</option>
                                    <?php foreach ($localizacoes as $localizacao): ?>
                                        <option value="<?= htmlspecialchars($localizacao, ENT_QUOTES, 'UTF-8') ?>" 
                                                <?= $localizacaoFiltro == $localizacao ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($localizacao, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">
                                    <i class="fas fa-dollar-sign"></i> Salário Mínimo
                                </label>
                                <input type="number" name="salario_min" class="filter-input" 
                                    placeholder="Ex: 2000" 
                                    value="<?= $salarioMinimo ?>" 
                                    min="0" step="100">
                            </div>
                        </div>

                        <div class="filters-actions">
                            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Limpar Filtros
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Buscar Vagas
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <?php if (isset($_SESSION['id_usuario'])): ?>
        <!-- Minhas Candidaturas -->
        <section class="saved-jobs-section">
            <div class="saved-jobs-header">
                <h2><i class="fas fa-bookmark"></i> Minhas Candidaturas</h2>
                <p>Acompanhe o status das suas candidaturas</p>
            </div>

            <div class="saved-jobs-container">
                <?php
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
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>Nenhuma candidatura ainda</h3>
                            <p>Explore as oportunidades abaixo e candidate-se às vagas que mais combinam com você!</p>
                        </div>
                    <?php else: ?>
                        <div class="saved-jobs-list">
                            <?php foreach ($minhas_candidaturas as $candidatura): 
                                $titulo_vaga = htmlspecialchars($candidatura['titulo_vaga'], ENT_QUOTES, 'UTF-8');
                                $empresa = htmlspecialchars($candidatura['empresa'], ENT_QUOTES, 'UTF-8');
                                $nome_area = htmlspecialchars($candidatura['nome_area'] ?? 'Área não especificada', ENT_QUOTES, 'UTF-8');
                                $tipo_emprego = htmlspecialchars($candidatura['tipo_emprego'], ENT_QUOTES, 'UTF-8');
                                $localizacao = htmlspecialchars($candidatura['localizacao'] ?? 'Local não especificado', ENT_QUOTES, 'UTF-8');
                                $status = htmlspecialchars($candidatura['status'], ENT_QUOTES, 'UTF-8');
                                $id_candidatura = filter_var($candidatura['id_candidatura'], FILTER_VALIDATE_INT);
                                $data_candidatura = date('d/m/Y', strtotime($candidatura['data_candidatura']));
                            ?>
                                <div class="saved-job-card fade-in" data-candidatura-id="<?= $id_candidatura ?>">
                                    <div class="saved-job-header">
                                        <div>
                                            <h4 class="saved-job-title"><?= $titulo_vaga ?></h4>
                                            <p class="modal-company"><?= $empresa ?></p>
                                        </div>
                                        <span class="status-badge status-<?= strtolower($candidatura['status']) ?>">
                                            <?php if ($candidatura['status'] === 'Aprovado'): ?>
                                                <i class="fas fa-check-circle"></i>
                                            <?php elseif ($candidatura['status'] === 'Reprovado'): ?>
                                                <i class="fas fa-times-circle"></i>
                                            <?php else: ?>
                                                <i class="fas fa-clock"></i>
                                            <?php endif; ?>
                                            <?= $status ?>
                                        </span>
                                    </div>

                                    <div class="job-details">
                                        <div class="job-detail">
                                            <i class="fas fa-tag"></i>
                                            <span><?= $nome_area ?></span>
                                        </div>
                                        <div class="job-detail">
                                            <i class="fas fa-clock"></i>
                                            <span><?= $tipo_emprego ?></span>
                                        </div>
                                        <div class="job-detail">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?= $localizacao ?></span>
                                        </div>
                                        <div class="job-detail">
                                            <i class="fas fa-calendar"></i>
                                            <span>Candidatura em <?= $data_candidatura ?></span>
                                        </div>
                                    </div>

                                    <button class="cancel-application-btn" data-candidatura-id="<?= $id_candidatura ?>">
                                        <i class="fas fa-trash"></i> Remover Candidatura
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif;
                } catch (PDOException $e) {
                    echo '<div class="empty-state"><p>Erro ao carregar candidaturas.</p></div>';
                }
                ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Job Opportunities Section -->
        <section class="job-opportunities">
            <div class="opportunities-header">
                <div class="results-count">
                    <i class="fas fa-list"></i>
                    <?= count($vagas) ?> vaga<?= count($vagas) != 1 ? 's' : '' ?> encontrada<?= count($vagas) != 1 ? 's' : '' ?>
                </div>
            </div>

            <div class="job-listings">
                <?php if (empty($vagas)): ?>
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fas fa-search"></i>
                        <h3>Nenhuma vaga encontrada</h3>
                        <p>Tente ajustar seus filtros de pesquisa ou remover alguns critérios</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($vagas as $vaga): 
                        $titulo_vaga = htmlspecialchars($vaga['titulo_vaga'], ENT_QUOTES, 'UTF-8');
                        $empresa = htmlspecialchars($vaga['empresa'], ENT_QUOTES, 'UTF-8');
                        $nome_area = htmlspecialchars($vaga['nome_area'] ?? 'Área não especificada', ENT_QUOTES, 'UTF-8');
                        $tipo_emprego = htmlspecialchars($vaga['tipo_emprego'], ENT_QUOTES, 'UTF-8');
                        $localizacao = htmlspecialchars($vaga['localizacao'] ?? 'Não especificado', ENT_QUOTES, 'UTF-8');
                        $id_vaga = filter_var($vaga['id_vaga'], FILTER_VALIDATE_INT);
                    ?>
                        <div class="job-card fade-in">
                            <div class="job-header">
                                <div>
                                    <h3 class="job-title"><?= $titulo_vaga ?></h3>
                                    <p class="job-company"><?= $empresa ?></p>
                                </div>
                                <span class="job-badge"><?= $nome_area ?></span>
                            </div>

                            <div class="job-details">
                                <div class="job-detail">
                                    <i class="fas fa-clock"></i>
                                    <span><?= $tipo_emprego ?></span>
                                </div>
                                <div class="job-detail">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?= $localizacao ?></span>
                                </div>
                                <?php if ($vaga['data_encerramento']): ?>
                                    <div class="job-detail">
                                        <i class="fas fa-calendar-times"></i>
                                        <span>Até <?= date('d/m/Y', strtotime($vaga['data_encerramento'])) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($vaga['salario']): ?>
                                <div class="job-salary">
                                    <i class="fas fa-dollar-sign"></i>
                                    R$ <?= number_format($vaga['salario'], 2, ',', '.') ?>
                                </div>
                            <?php endif; ?>

                            <div class="job-actions">
                                <?php if (isset($_SESSION['id_usuario'])): ?>
                                    <?php if (in_array($vaga['id_vaga'], $candidaturas_usuario)): ?>
                                        <button class="btn btn-applied" disabled>
                                            <i class="fas fa-check"></i> Candidatura Enviada
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-info more-info-btn" data-vaga-id="<?= $id_vaga ?>">
                                            <i class="fas fa-info-circle"></i> Ver Detalhes
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-login" onclick="window.location.href='../php/index.php?openLoginModal=true';">
                                        <i class="fas fa-sign-in-alt"></i> Faça login para se candidatar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Modal para detalhes da vaga -->
    <div class="modal-overlay" id="job-modal">
        <div class="modal-content">
            <button class="modal-close" id="modal-close">
                <i class="fas fa-times"></i>
            </button>
            
            <h3 class="modal-title" id="modal-title">Título da Vaga</h3>
            <p class="modal-company" id="modal-empresa">Empresa</p>

            <div class="modal-info-grid">
                <div class="modal-info-item">
                    <div class="modal-info-label">
                        <i class="fas fa-tag"></i> Área
                    </div>
                    <div class="modal-info-value" id="modal-area">Área</div>
                </div>
                <div class="modal-info-item">
                    <div class="modal-info-label">
                        <i class="fas fa-clock"></i> Tipo
                    </div>
                    <div class="modal-info-value" id="modal-tipo">Tipo</div>
                </div>
                <div class="modal-info-item">
                    <div class="modal-info-label">
                        <i class="fas fa-map-marker-alt"></i> Localização
                    </div>
                    <div class="modal-info-value" id="modal-localizacao">Localização</div>
                </div>
                <div class="modal-info-item" id="modal-salario-container">
                    <div class="modal-info-label">
                        <i class="fas fa-dollar-sign"></i> Salário
                    </div>
                    <div class="modal-info-value" id="modal-salario">Não informado</div>
                </div>
                <div class="modal-info-item" id="modal-encerramento-container">
                    <div class="modal-info-label">
                        <i class="fas fa-calendar-times"></i> Encerramento
                    </div>
                    <div class="modal-info-value" id="modal-encerramento">Não informado</div>
                </div>
            </div>

            <div class="modal-section">
                <h4><i class="fas fa-file-alt"></i> Descrição da Vaga</h4>
                <div class="modal-description" id="modal-descricao">
                    Descrição detalhada da vaga...
                </div>
            </div>

            <div class="modal-section" id="modal-requisitos-section">
                <h4><i class="fas fa-list-check"></i> Requisitos</h4>
                <div class="modal-text" id="modal-requisitos">
                    Requisitos da vaga...
                </div>
            </div>

            <div class="modal-section" id="modal-beneficios-section">
                <h4><i class="fas fa-gift"></i> Benefícios</h4>
                <div class="modal-text" id="modal-beneficios">
                    Benefícios oferecidos...
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn btn-secondary" id="modal-btn-cancel">
                    <i class="fas fa-times"></i> Fechar
                </button>
                <button class="btn btn-success" id="modal-apply-btn" data-vaga-id="">
                    <i class="fas fa-paper-plane"></i> Candidatar-se
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de confirmação -->
    <div class="modal-overlay" id="confirm-modal">
        <div class="confirm-dialog">
            <div style="margin-bottom: 1rem;">
                <i class="fas fa-question-circle" style="font-size: 3rem; color: var(--primary-blue);"></i>
            </div>
            <h3 class="confirm-title">Confirmar Candidatura</h3>
            <p class="confirm-message">Deseja realmente se candidatar a esta vaga?</p>
            <div class="confirm-actions">
                <button class="btn btn-secondary" id="confirm-no">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn btn-primary" id="confirm-yes" data-vaga-id="">
                    <i class="fas fa-check"></i> Confirmar
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div class="status-notification" id="statusNotification">
        <span class="notification-close" onclick="closeNotification()">
            <i class="fas fa-times"></i>
        </span>
        <div id="notificationContent"></div>
    </div>

    <!-- Contact Section -->
    <section id="contato" class="contact-section">
        <div class="contact-container">
            <div class="contact-info">
                <h3><i class="fas fa-phone"></i> Entre em Contato</h3>
                <div class="contact-detail">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Bom Retiro, São Paulo - SP, 01124-010<br>ETESP</span>
                </div>
                <div class="contact-detail">
                    <i class="fas fa-envelope"></i>
                    <span>contato@empresa.com</span>
                </div>
                <div class="contact-detail">
                    <i class="fas fa-phone"></i>
                    <span>(11) 1234-5678</span>
                </div>
            </div>
            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3656.3465896377126!2d-46.64165882513707!3d-23.53003478469527!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94ce5857a5c48815%3A0x70b13f63e8491df3!2sETESP!5e0!3m2!1spt-BR!2sbr!4v1696952749192!5m2!1spt-BR!2sbr" 
                    allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-content">
            <img src="../assets/img/globo-mundial.png" alt="Logo da Empresa" class="footer-logo">
            <p>&copy; 2024 ProLink. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        $(document).ready(function() {
            // ===== PROTEÇÃO CONTRA MANIPULAÇÃO DE INPUTS =====
            function protegerInputs() {
                const searchInput = document.getElementById('searchInput');
                
                if (!searchInput) return;
                
                const tipoOriginal = searchInput.type;
                const attributosOriginais = {
                    type: searchInput.type,
                    name: searchInput.name,
                    id: searchInput.id,
                    required: searchInput.required,
                    maxLength: searchInput.maxLength || 100
                };
                
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes') {
                            const attrName = mutation.attributeName;
                            
                            if (['type', 'name', 'id'].includes(attrName)) {
                                const valorAtual = searchInput.getAttribute(attrName);
                                const valorOriginal = attributosOriginais[attrName];
                                
                                if (valorAtual !== valorOriginal.toString()) {
                                    console.warn('Tentativa de manipulação detectada no atributo:', attrName);
                                    searchInput.setAttribute(attrName, valorOriginal);
                                    searchInput.value = '';
                                    mostrarAvisoSeguranca();
                                }
                            }
                        }
                    });
                });
                
                observer.observe(searchInput, {
                    attributes: true,
                    attributeFilter: ['type', 'name', 'id', 'required', 'maxlength']
                });
                
                setInterval(function() {
                    if (searchInput.type !== tipoOriginal) {
                        searchInput.type = tipoOriginal;
                        searchInput.value = '';
                        mostrarAvisoSeguranca();
                    }
                }, 1000);
                
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
                
                searchInput.addEventListener('input', function(e) {
                    if (this.type !== tipoOriginal) {
                        this.type = tipoOriginal;
                        this.value = '';
                        mostrarAvisoSeguranca();
                        e.preventDefault();
                        return false;
                    }
                    
                    validarConteudoPorTipo(this, tipoOriginal);
                });
                
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
            
            function validarConteudoPorTipo(input, tipoEsperado) {
                const valor = input.value;
                const regexTexto = /^[\w\sáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\-.,;:!?@#%&*()+=]*$/;
                if (!regexTexto.test(valor)) {
                    input.value = valor.replace(/[^\w\sáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\-.,;:!?@#%&*()+=]/g, '');
                }
                
                if (valor.length > 100) {
                    input.value = valor.substring(0, 100);
                }
            }
            
            function mostrarAvisoSeguranca() {
                const avisoAnterior = document.querySelector('.security-warning');
                if (avisoAnterior) {
                    avisoAnterior.remove();
                }
                
                const aviso = document.createElement('div');
                aviso.className = 'security-warning';
                aviso.innerHTML = `
                    <strong>⚠️ Aviso de Segurança</strong><br>
                    Tentativa de manipulação detectada. O formulário foi resetado por segurança.
                `;
                
                document.body.appendChild(aviso);
                
                setTimeout(() => {
                    if (aviso.parentNode) {
                        aviso.style.animation = 'slideInRight 0.3s ease-out reverse';
                        setTimeout(() => aviso.remove(), 300);
                    }
                }, 5000);
            }
            
            protegerInputs();

            // ===== FUNCIONALIDADES DA INTERFACE =====
            
            // Toggle de filtros avançados
            $('#filters-toggle').on('click', function() {
                const filters = $('#advanced-filters');
                const isActive = filters.hasClass('active');
                
                if (isActive) {
                    filters.removeClass('active');
                    $(this).html('<i class="fas fa-filter"></i> Filtros Avançados');
                } else {
                    filters.addClass('active');
                    $(this).html('<i class="fas fa-filter"></i> Ocultar Filtros');
                }
            });

            // Animação de entrada dos cards
            function animateCards() {
                $('.job-card').each(function(index) {
                    $(this).css('animation-delay', (index * 0.1) + 's');
                });
            }
            animateCards();

            // Verificar mudanças de status periodicamente
            checkStatusUpdates();
            setInterval(checkStatusUpdates, 30000);

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
                            showCustomNotification(response.message || 'Erro ao carregar detalhes da vaga.', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Erro AJAX:', error);
                        showCustomNotification('Erro de conexão ao carregar detalhes da vaga.', 'error');
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
                            showCustomNotification(response.message, 'success');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showCustomNotification(response.message || 'Erro ao processar candidatura.', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Erro AJAX:', error);
                        $('#confirm-modal').removeClass('active');
                        $('#job-modal').removeClass('active');
                        showCustomNotification('Erro de conexão ao processar candidatura.', 'error');
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

            // Fechar modal com ESC
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.modal-overlay.active').removeClass('active');
                }
            });

            // Cancelar candidatura
            $(document).on('click', '.cancel-application-btn', function() {
                const candidaturaId = $(this).data('candidatura-id');
                const card = $(this).closest('.saved-job-card');
                
                if (confirm('Tem certeza que deseja remover esta candidatura?')) {
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
                                card.fadeOut(300, function() {
                                    $(this).remove();
                                    
                                    if ($('.saved-job-card').length === 0) {
                                        $('.saved-jobs-container').html(`
                                            <div class="empty-state">
                                                <i class="fas fa-search"></i>
                                                <h3>Nenhuma candidatura ainda</h3>
                                                <p>Explore as oportunidades abaixo e candidate-se às vagas que mais combinam com você!</p>
                                            </div>
                                        `);
                                    }
                                });
                                
                                showCustomNotification('Candidatura removida com sucesso!', 'success');
                            } else {
                                showCustomNotification(response.message || 'Erro ao remover candidatura.', 'error');
                            }
                        },
                        error: function() {
                            showCustomNotification('Erro de conexão ao remover candidatura.', 'error');
                        }
                    });
                }
            });

            // Mobile menu functionality
            const mobileMenuBtn = $('#mobile-menu');
            const menu = $('#menu');
            
            mobileMenuBtn.on('click', function() {
                menu.toggleClass('active');
            });

            // Fechar menu ao clicar fora (mobile)
            $(document).on('click', function(e) {
                if (window.innerWidth < 992 && menu.hasClass('active')) {
                    if (!menu.is(e.target) && menu.has(e.target).length === 0 && 
                        !mobileMenuBtn.is(e.target) && mobileMenuBtn.has(e.target).length === 0) {
                        menu.removeClass('active');
                    }
                }
            });

            // Fechar menu ao redimensionar para desktop
            $(window).on('resize', function() {
                if (window.innerWidth >= 992) {
                    menu.removeClass('active');
                }
            });
        });

        // ===== FUNÇÕES DE NOTIFICAÇÃO E STATUS =====
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
                    // Falha silenciosa
                }
            });
        }

        function showStatusNotification(atualizacao) {
            const notification = $('#statusNotification');
            const content = $('#notificationContent');
            
            let statusText = atualizacao.status === 'Aprovado' ? 'aprovada' : 'reprovada';
            let statusClass = atualizacao.status === 'Aprovado' ? 'aprovado' : 'reprovado';
            let icon = atualizacao.status === 'Aprovado' ? 'fas fa-check-circle' : 'fas fa-times-circle';
            
            content.html(`
                <div class="notification-header">
                    <i class="${icon}"></i>
                    <strong>Candidatura ${statusText.charAt(0).toUpperCase() + statusText.slice(1)}!</strong>
                </div>
                <div class="notification-body">
                    <p>Sua candidatura para <strong>${atualizacao.titulo_vaga}</strong> foi ${statusText}.</p>
                </div>
            `);
            
            notification.removeClass('aprovado reprovado').addClass(statusClass);
            notification.addClass('show');
            
            // Auto-fechar após 8 segundos
            setTimeout(() => {
                notification.removeClass('show');
            }, 8000);
        }

        function showCustomNotification(message, type = 'info') {
            const notification = $(`
                <div class="status-notification show ${type}">
                    <span class="notification-close" onclick="$(this).parent().removeClass('show')">
                        <i class="fas fa-times"></i>
                    </span>
                    <div class="notification-content">
                        <strong>${message}</strong>
                    </div>
                </div>
            `);
            
            $('body').append(notification);
            
            setTimeout(() => {
                notification.removeClass('show');
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

        function closeNotification() {
            $('#statusNotification').removeClass('show');
        }

        // Smooth scroll para âncoras
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            const target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 600);
            }
        });

        // Adicionar efeito de scroll suave na página
        document.querySelectorAll('.job-card').forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    </script>
</body>
</html>