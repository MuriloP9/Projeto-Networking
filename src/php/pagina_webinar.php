<?php
session_start();
include("../php/conexao.php");
$pdo = conectar();

// Processar busca de detalhes do webinar via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === 'buscar_webinar') {
    header('Content-Type: application/json');
    
    if (!isset($_GET['id_webinar'])) {
        echo json_encode(['success' => false, 'message' => 'Webinar não especificado.']);
        exit;
    }
    
    // Sanitizar e validar id_webinar
    $id_webinar = filter_var($_GET['id_webinar'], FILTER_VALIDATE_INT);
    if ($id_webinar === false || $id_webinar <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID do webinar inválido.']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT * 
            FROM Webinar 
            WHERE id_webinar = ? AND ativo = 1
        ");
        $stmt->execute([$id_webinar]);
        $webinar = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($webinar) {
            // Sanitizar dados do webinar antes de retornar
            $webinar['tema'] = htmlspecialchars($webinar['tema'], ENT_QUOTES, 'UTF-8');
            $webinar['palestrante'] = htmlspecialchars($webinar['palestrante'], ENT_QUOTES, 'UTF-8');
            $webinar['descricao'] = htmlspecialchars($webinar['descricao'], ENT_QUOTES, 'UTF-8');
            $webinar['link'] = filter_var($webinar['link'], FILTER_SANITIZE_URL);
            
            // Formatar a data e hora
            if ($webinar['data_hora']) {
                $webinar['data_formatada'] = date('d/m/Y', strtotime($webinar['data_hora']));
                $webinar['hora_formatada'] = date('H:i', strtotime($webinar['data_hora']));
                $webinar['data_completa'] = date('d/m/Y \à\s H:i', strtotime($webinar['data_hora']));
            }
            
            // Verificar se é futuro ou passado
            $webinar['is_futuro'] = strtotime($webinar['data_hora']) > time();
            
            echo json_encode(['success' => true, 'webinar' => $webinar]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Webinar não encontrado.']);
        }
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar webinar.']);
        exit;
    }
}

// Processar filtros de pesquisa aprimorados
$termoBusca = isset($_GET['search']) ? filter_var($_GET['search'], FILTER_SANITIZE_SPECIAL_CHARS) : '';
$palestrante = isset($_GET['palestrante']) ? filter_var($_GET['palestrante'], FILTER_SANITIZE_SPECIAL_CHARS) : '';
$dataInicio = isset($_GET['data_inicio']) ? filter_var($_GET['data_inicio'], FILTER_SANITIZE_SPECIAL_CHARS) : '';
$dataFim = isset($_GET['data_fim']) ? filter_var($_GET['data_fim'], FILTER_SANITIZE_SPECIAL_CHARS) : '';
$statusFiltro = isset($_GET['status']) ? filter_var($_GET['status'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

// Buscar palestrantes únicos
$palestrantes = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT palestrante FROM Webinar WHERE ativo = 1 AND palestrante IS NOT NULL ORDER BY palestrante");
    $palestrantes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Tratamento de erro silencioso
}

// Construir query dinâmica com filtros
$webinars = [];
try {
    $whereConditions = ["ativo = 1"];
    $params = [];
    
    if (!empty($termoBusca)) {
        $whereConditions[] = "(tema LIKE ? OR palestrante LIKE ? OR descricao LIKE ?)";
        $termoLike = "%$termoBusca%";
        $params[] = $termoLike;
        $params[] = $termoLike;
        $params[] = $termoLike;
    }
    
    if (!empty($palestrante)) {
        $whereConditions[] = "palestrante = ?";
        $params[] = $palestrante;
    }
    
    // CORREÇÃO: Converter datas para formato do SQL Server e usar CAST
    if (!empty($dataInicio)) {
        $whereConditions[] = "CAST(data_hora AS DATE) >= CAST(? AS DATE)";
        $params[] = $dataInicio;
    }
    
    if (!empty($dataFim)) {
        $whereConditions[] = "CAST(data_hora AS DATE) <= CAST(? AS DATE)";
        $params[] = $dataFim;
    }
    
    if (!empty($statusFiltro)) {
        if ($statusFiltro === 'futuro') {
            $whereConditions[] = "data_hora > GETDATE()";
        } elseif ($statusFiltro === 'passado') {
            $whereConditions[] = "data_hora <= GETDATE()";
        }
    }
    
    $whereClause = implode(" AND ", $whereConditions);
    
    $sql = "SELECT * 
            FROM Webinar 
            WHERE $whereClause 
            ORDER BY data_hora DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $webinars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sanitizar dados para exibição
    foreach ($webinars as &$webinar) {
        $webinar['tema'] = htmlspecialchars($webinar['tema'], ENT_QUOTES, 'UTF-8');
        $webinar['palestrante'] = htmlspecialchars($webinar['palestrante'], ENT_QUOTES, 'UTF-8');
        $webinar['descricao'] = htmlspecialchars($webinar['descricao'], ENT_QUOTES, 'UTF-8');
        $webinar['link'] = filter_var($webinar['link'], FILTER_SANITIZE_URL);
    }
    unset($webinar); // Quebrar referência
    
} catch (PDOException $e) {
    // Mostrar erro de forma mais detalhada para debug (remover em produção)
    error_log("Erro ao buscar webinars: " . $e->getMessage());
    echo "<script>console.error('Erro ao buscar webinars: " . addslashes($e->getMessage()) . "');</script>";
}

// Sanitizar dados para exibição
$termoBuscaDisplay = htmlspecialchars($termoBusca, ENT_QUOTES, 'UTF-8');
$palestranteDisplay = htmlspecialchars($palestrante, ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProLink - Webinars</title>
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
            background: linear-gradient( #006afeff 100%);
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

        /* Hero Section for Webinars */
        .webinars-hero {
            background: var(--gradient-primary);
            padding: 4rem 2rem 2rem;
            color: var(--white);
            text-align: center;
        }

        .webinars-hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .webinars-hero p {
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

        /* Webinar Opportunities Section */
        .webinar-opportunities {
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
            color: white;
            font-weight: 500;
        }

        /* Webinar Cards */
        .webinar-listings {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }

        .webinar-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid #E9ECEF;
            position: relative;
            overflow: hidden;
        }

        .webinar-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-primary);
        }

        .webinar-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .webinar-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .webinar-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-gray);
            margin-bottom: 0.5rem;
        }

        .webinar-speaker {
            color: var(--primary-blue);
            font-weight: 600;
            font-size: 1rem;
        }

        .webinar-badge {
            background: var(--light-blue);
            color: var(--primary-blue);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .webinar-badge.live {
            background: linear-gradient(135deg, #FF4444, #FF6B6B);
            color: var(--white);
            animation: pulse 2s infinite;
        }

        .webinar-badge.upcoming {
            background: linear-gradient(135deg, #28A745, #20C997);
            color: var(--white);
        }

        .webinar-badge.past {
            background: var(--gray);
            color: var(--white);
        }

        .webinar-details {
            margin: 1rem 0;
        }

        .webinar-detail {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .webinar-detail i {
            width: 16px;
            color: var(--primary-blue);
        }

        .webinar-datetime {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            margin: 0.75rem 0;
            display: inline-block;
        }

        .webinar-actions {
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

        .btn-watch {
            background: linear-gradient(135deg, #FF4444, #FF6B6B);
            color: var(--white);
            flex: 1;
        }

        .btn-disabled {
            background: var(--gray);
            color: var(--white);
            cursor: default;
            flex: 1;
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

        .modal-speaker {
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
            border-bottom: 2px solid var(--primary-blue);
            padding-bottom: 0.5rem;
        }

        .modal-description,
        .modal-text {
            color: var(--dark-gray);
            line-height: 1.6;
            font-size: 1rem;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1.5rem;
            border-top: 1px solid #E9ECEF;
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

        /* Aplicação das animações */
        .webinar-card {
            animation: fadeInUp 0.8s ease forwards;
        }

        .webinar-card:nth-child(1) { animation-delay: 0.1s; }
        .webinar-card:nth-child(2) { animation-delay: 0.2s; }
        .webinar-card:nth-child(3) { animation-delay: 0.3s; }
        .webinar-card:nth-child(4) { animation-delay: 0.4s; }

        /* Menu mobile responsivo */
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

            /* Ajustes para o conteúdo principal */
            .webinars-hero h1 {
                font-size: 2.2rem;
            }

            .search-filters-section {
                margin: -1rem 1rem 1rem;
            }

            .webinar-listings {
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
            .navbar {
                padding: 15px 30px;
            }
        }

        /* Responsive Design adicional */
        @media (max-width: 576px) {
            .navbar {
                padding: 15px 15px;
            }

            .webinars-hero {
                padding: 3rem 1rem 1rem;
            }

            .webinars-hero h1 {
                font-size: 1.8rem;
            }

            .webinar-opportunities {
                padding: 1rem;
            }

            .modal-content {
                padding: 1.5rem;
                margin: 1rem;
            }

            .modal-info-grid {
                grid-template-columns: 1fr;
            }

            .webinar-actions {
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

        /* Animações suaves para elementos do menu mobile */
        .menu.active li {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }

        .menu.active li:nth-child(1) { animation-delay: 0.1s; }
        .menu.active li:nth-child(2) { animation-delay: 0.2s; }
        .menu.active li:nth-child(3) { animation-delay: 0.3s; }
        .menu.active li:nth-child(4) { animation-delay: 0.4s; }

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

        /* Notification Toast */
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
                <li><a href="../php/paginaEmprego.php">Oportunidades</a></li>
                <li><a href="#contato">Contato</a></li>
            </ul>
            
            <button class="menu-toggle" id="mobile-menu">
                <img src="../assets/img/icons8-menu-48.png" alt="Menu" class="menu-icon">
            </button>
        </nav>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="webinars-hero">
            <h1><i class="fas fa-video"></i> Webinars Educacionais</h1>
            <p>Aprenda com especialistas e desenvolva suas habilidades profissionais através dos nossos webinars</p>
        </section>

        <!-- Search and Filters -->
        <section class="search-filters-section">
            <div class="filters-container">
                <div class="search-header">
                    <h2><i class="fas fa-search"></i> Buscar Webinars</h2>
                    <button type="button" class="filters-toggle" id="filters-toggle">
                        <i class="fas fa-filter"></i> Filtros Avançados
                    </button>
                </div>

                <form method="GET" action="" id="search-form">
                    <div class="search-bar-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" id="searchInput" class="search-bar"
                            placeholder="Pesquisar por tema, palestrante ou palavra-chave..."
                            value="<?= $termoBuscaDisplay ?>">
                    </div>

                    <div class="advanced-filters" id="advanced-filters">
                        <div class="filters-grid">
                            <div class="filter-group">
                                <label class="filter-label">
                                    <i class="fas fa-user"></i> Palestrante
                                </label>
                                <select name="palestrante" class="filter-select">
                                    <option value="">Todos os palestrantes</option>
                                    <?php foreach ($palestrantes as $palestr): ?>
                                        <option value="<?= htmlspecialchars($palestr, ENT_QUOTES, 'UTF-8') ?>" 
                                                <?= $palestrante == $palestr ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($palestr, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">
                                    <i class="fas fa-calendar-day"></i> Data Início
                                </label>
                                <input type="date" name="data_inicio" class="filter-input" 
                                    value="<?= $dataInicio ?>">
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">
                                    <i class="fas fa-calendar-day"></i> Data Fim
                                </label>
                                <input type="date" name="data_fim" class="filter-input" 
                                    value="<?= $dataFim ?>">
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">
                                    <i class="fas fa-clock"></i> Status
                                </label>
                                <select name="status" class="filter-select">
                                    <option value="">Todos os status</option>
                                    <option value="futuro" <?= $statusFiltro == 'futuro' ? 'selected' : '' ?>>
                                        Próximos
                                    </option>
                                    <option value="passado" <?= $statusFiltro == 'passado' ? 'selected' : '' ?>>
                                        Passados
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="filters-actions">
                            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Limpar Filtros
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Buscar Webinars
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- Webinar Opportunities Section -->
        <section class="webinar-opportunities">
            <div class="opportunities-header">
                <div class="results-count">
                    <i class="fas fa-video"></i>
                    <?= count($webinars) ?> webinar<?= count($webinars) != 1 ? 's' : '' ?> encontrado<?= count($webinars) != 1 ? 's' : '' ?>
                </div>
            </div>

            <div class="webinar-listings">
                <?php if (empty($webinars)): ?>
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fas fa-video-slash"></i>
                        <h3>Nenhum webinar encontrado</h3>
                        <p>Tente ajustar seus filtros de pesquisa ou remover alguns critérios</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($webinars as $webinar): 
                        $tema = htmlspecialchars($webinar['tema'], ENT_QUOTES, 'UTF-8');
                        $palestrante = htmlspecialchars($webinar['palestrante'], ENT_QUOTES, 'UTF-8');
                        $descricao = htmlspecialchars($webinar['descricao'], ENT_QUOTES, 'UTF-8');
                        $id_webinar = filter_var($webinar['id_webinar'], FILTER_VALIDATE_INT);
                        $data_hora = strtotime($webinar['data_hora']);
                        $agora = time();
                        $is_futuro = $data_hora > $agora;
                        $diff_horas = abs($data_hora - $agora) / 3600;
                        $is_live = $is_futuro && $diff_horas <= 1; // Live se for nos próximos 60 minutos
                    ?>
                        <div class="webinar-card fade-in">
                            <div class="webinar-header">
                                <div>
                                    <h3 class="webinar-title"><?= $tema ?></h3>
                                    <p class="webinar-speaker"><?= $palestrante ?></p>
                                </div>
                                <?php if ($is_live): ?>
                                    <span class="webinar-badge live">
                                        <i class="fas fa-circle"></i> AO VIVO
                                    </span>
                                <?php elseif ($is_futuro): ?>
                                    <span class="webinar-badge upcoming">
                                        <i class="fas fa-calendar-plus"></i> PRÓXIMO
                                    </span>
                                <?php else: ?>
                                    <span class="webinar-badge past">
                                        <i class="fas fa-history"></i> FINALIZADO
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="webinar-details">
                                <div class="webinar-detail">
                                    <i class="fas fa-calendar"></i>
                                    <span><?= date('d/m/Y', $data_hora) ?></span>
                                </div>
                                <div class="webinar-detail">
                                    <i class="fas fa-clock"></i>
                                    <span><?= date('H:i', $data_hora) ?></span>
                                </div>
                                <?php if (!empty($descricao)): ?>
                                    <div class="webinar-detail">
                                        <i class="fas fa-info-circle"></i>
                                        <span><?= strlen($descricao) > 80 ? substr($descricao, 0, 80) . '...' : $descricao ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="webinar-datetime">
                                <i class="fas fa-calendar-alt"></i>
                                <?= date('d/m/Y \à\s H:i', $data_hora) ?>
                            </div>

                            <div class="webinar-actions">
                                <button class="btn btn-info more-info-btn" data-webinar-id="<?= $id_webinar ?>">
                                    <i class="fas fa-info-circle"></i> Ver Detalhes
                                </button>
                                
                                <?php if ($is_live || $is_futuro): ?>
                                    <a href="<?= htmlspecialchars($webinar['link']) ?>" target="_blank" class="btn btn-watch">
                                        <i class="fas fa-play"></i> 
                                        <?= $is_live ? 'Assistir Agora' : 'Acessar Link' ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($webinar['link']) ?>" target="_blank" class="btn btn-success">
                                        <i class="fas fa-play"></i> Ver Gravação
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Modal para detalhes do webinar -->
    <div class="modal-overlay" id="webinar-modal">
        <div class="modal-content">
            <button class="modal-close" id="modal-close">
                <i class="fas fa-times"></i>
            </button>
            
            <h3 class="modal-title" id="modal-title">Título do Webinar</h3>
            <p class="modal-speaker" id="modal-palestrante">Palestrante</p>

            <div class="modal-info-grid">
                <div class="modal-info-item">
                    <div class="modal-info-label">
                        <i class="fas fa-calendar"></i> Data
                    </div>
                    <div class="modal-info-value" id="modal-data">Data</div>
                </div>
                <div class="modal-info-item">
                    <div class="modal-info-label">
                        <i class="fas fa-clock"></i> Horário
                    </div>
                    <div class="modal-info-value" id="modal-horario">Horário</div>
                </div>
                <div class="modal-info-item">
                    <div class="modal-info-label">
                        <i class="fas fa-info-circle"></i> Status
                    </div>
                    <div class="modal-info-value" id="modal-status">Status</div>
                </div>
            </div>

            <div class="modal-section" id="modal-descricao-section">
                <h4><i class="fas fa-file-alt"></i> Descrição do Webinar</h4>
                <div class="modal-description" id="modal-descricao">
                    Descrição detalhada do webinar...
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn btn-secondary" id="modal-btn-cancel">
                    <i class="fas fa-times"></i> Fechar
                </button>
                <a href="#" target="_blank" class="btn btn-success" id="modal-watch-btn">
                    <i class="fas fa-play"></i> Acessar Webinar
                </a>
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
                const regexTexto = /^[\w\sáàâãéèêíïóôõöúçñÁÀÂãÉÈÊÍÏÓÔÕÖÚÇÑ\-.,;:!?@#%&*()+=]*$/;
                if (!regexTexto.test(valor)) {
                    input.value = valor.replace(/[^\w\sáàâãéèêíïóôõöúçñÁÀÂãÉÈÊÍÏÓÔÕÖÚÇÑ\-.,;:!?@#%&*()+=]/g, '');
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
                $('.webinar-card').each(function(index) {
                    $(this).css('animation-delay', (index * 0.1) + 's');
                });
            }
            animateCards();

            // Modal de detalhes do webinar
            $('.more-info-btn').on('click', function() {
                const webinarId = $(this).data('webinar-id');

                $.ajax({
                    type: 'GET',
                    url: window.location.href,
                    data: {
                        ajax: 'buscar_webinar',
                        id_webinar: webinarId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const webinar = response.webinar;
                            
                            $('#modal-title').text(webinar.tema);
                            $('#modal-palestrante').text(webinar.palestrante);
                            $('#modal-data').text(webinar.data_formatada || 'Não informado');
                            $('#modal-horario').text(webinar.hora_formatada || 'Não informado');
                            
                            if (webinar.is_futuro) {
                                $('#modal-status').html('<span style="color: var(--success);">Próximo</span>');
                                $('#modal-watch-btn').html('<i class="fas fa-calendar-plus"></i> Acessar Link');
                            } else {
                                $('#modal-status').html('<span style="color: var(--gray);">Finalizado</span>');
                                $('#modal-watch-btn').html('<i class="fas fa-play"></i> Ver Gravação');
                            }

                            if (webinar.descricao) {
                                $('#modal-descricao').html(webinar.descricao.replace(/\n/g, '<br>'));
                                $('#modal-descricao-section').show();
                            } else {
                                $('#modal-descricao').text('Sem descrição detalhada.');
                                $('#modal-descricao-section').show();
                            }

                            $('#modal-watch-btn').attr('href', webinar.link);
                            $('#webinar-modal').addClass('active');
                        } else {
                            showCustomNotification(response.message || 'Erro ao carregar detalhes do webinar.', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Erro AJAX:', error);
                        showCustomNotification('Erro de conexão ao carregar detalhes do webinar.', 'error');
                    }
                });
            });

            // Fechar modal
            $('#modal-close, #modal-btn-cancel').on('click', function() {
                $('#webinar-modal').removeClass('active');
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
        });

        // ===== FUNÇÕES DE NOTIFICAÇÃO =====
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
    </script>
</body>
</html>