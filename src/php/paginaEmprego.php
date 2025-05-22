<?php
session_start();

include("../php/conexao.php");

$pdo = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'candidatura') {
    if (!isset($_SESSION['id_usuario'])) {
        echo json_encode(['success' => false, 'message' => 'Você precisa fazer login para se candidatar.']);
        exit;
    }

    // Processar candidatura via AJAX
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'candidatura') {
        header('Content-Type: application/json');

        if (!isset($_POST['id_vaga'])) {
            echo json_encode(['success' => false, 'message' => 'Vaga não especificada.']);
            exit;
        }

        $id_vaga = $_POST['id_vaga'];
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

            // Verificar se já existe candidatura
            $stmt = $pdo->prepare("SELECT * FROM Candidatura WHERE id_vaga = ? AND id_perfil = ?");
            $stmt->execute([$id_vaga, $id_perfil]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Você já se candidatou a esta vaga.']);
                exit;
            }

            // Inserir nova candidatura
            $stmt = $pdo->prepare("INSERT INTO Candidatura (id_vaga, id_perfil, data_candidatura, status) 
                              VALUES (?, ?, GETDATE(), 'Pendente')");
            $stmt->execute([$id_vaga, $id_perfil]);

            echo json_encode(['success' => true, 'message' => 'Candidatura realizada com sucesso!']);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao processar candidatura: ' . $e->getMessage()]);
            exit;
        }
    }
}

// Buscar vagas com base no termo de pesquisa
$termoBusca = isset($_GET['search']) ? trim($_GET['search']) : '';
$vagas = [];

try {
    if (!empty($termoBusca)) {
        $stmt = $pdo->prepare("SELECT v.*, a.nome_area 
                              FROM Vagas v
                              LEFT JOIN AreaAtuacao a ON v.id_area = a.id_area
                              WHERE v.titulo_vaga LIKE ? OR a.nome_area LIKE ?
                              ORDER BY v.id_vaga DESC");
        $termoLike = "%$termoBusca%";
        $stmt->execute([$termoLike, $termoLike]);
    } else {
        $stmt = $pdo->query("SELECT v.*, a.nome_area 
                            FROM Vagas v
                            LEFT JOIN AreaAtuacao a ON v.id_area = a.id_area
                            ORDER BY v.id_vaga DESC");
    }

    $vagas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<script>alert('Erro ao buscar vagas: " . addslashes($e->getMessage()) . "');</script>";
}

// Buscar candidaturas do usuário logado
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
        // Silenciosamente falha
    }
}
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
                <a href="../php/perfil.php"><img src="../assets/img/user-48.png" alt="Perfil" class="profile-icon"></a>
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
                // Buscar candidaturas do usuário
                try {
                    $stmt = $pdo->prepare("
                    SELECT c.*, v.titulo_vaga, v.tipo_emprego, v.localizacao, a.nome_area 
                    FROM Candidatura c
                    JOIN Perfil p ON c.id_perfil = p.id_perfil
                    JOIN Vagas v ON c.id_vaga = v.id_vaga
                    LEFT JOIN AreaAtuacao a ON v.id_area = a.id_area
                    WHERE p.id_usuario = ?
                    ORDER BY c.data_candidatura DESC
                ");
                    $stmt->execute([$_SESSION['id_usuario']]);
                    $minhas_candidaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($minhas_candidaturas)): ?>
                        <p>Você ainda não se candidatou a nenhuma vaga.</p>
                    <?php else: ?>
                        <div class="saved-jobs-list">
                            <?php foreach ($minhas_candidaturas as $candidatura): ?>
                                <div class="saved-job-card">
                                    <h4><?= htmlspecialchars($candidatura['titulo_vaga']) ?></h4>
                                    <p><?= htmlspecialchars($candidatura['nome_area'] ?? 'Área não especificada') ?></p>
                                    <p><?= htmlspecialchars($candidatura['tipo_emprego']) ?> - <?= htmlspecialchars($candidatura['localizacao'] ?? 'Local não especificado') ?></p>
                                    <span class="saved-job-status status-<?= strtolower($candidatura['status']) ?>">
                                        <?= htmlspecialchars($candidatura['status']) ?>
                                    </span>
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

    <section id="job-opportunities" class="job-opportunities">
        <h2>Oportunidades de Emprego</h2>

        <form method="GET" action="">
            <div class="search-filter-container">
                <input type="text" name="search" id="searchInput" class="search-bar"
                    placeholder="Pesquisar por título da vaga ou área de atuação..."
                    value="<?= htmlspecialchars($termoBusca) ?>">
                <button type="submit" class="search-btn">Procurar</button>
            </div>
        </form>

        <!-- Lista de oportunidades -->
        <div class="job-listings">
            <?php if (empty($vagas)): ?>
                <p>Nenhuma vaga encontrada.</p>
            <?php else: ?>
                <?php foreach ($vagas as $vaga): ?>
                    <div class="job-card">
                        <h3><?= htmlspecialchars($vaga['titulo_vaga']) ?></h3>
                        <p><?= htmlspecialchars($vaga['nome_area'] ?? 'Área não especificada') ?></p>
                        <p>Tipo: <?= htmlspecialchars($vaga['tipo_emprego']) ?></p>
                        <p>Localização: <?= htmlspecialchars($vaga['localizacao'] ?? 'Não especificado') ?></p>

                        <?php if (isset($_SESSION['id_usuario'])): ?>
                            <?php if (in_array($vaga['id_vaga'], $candidaturas_usuario)): ?>
                                <button class="already-applied" disabled>Candidatura Enviada</button>
                            <?php else: ?>
                                <button class="more-info-btn" data-vaga-id="<?= $vaga['id_vaga'] ?>">Saiba Mais</button>
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

            <p class="modal-info"><strong>Área:</strong> <span id="modal-area">Área</span></p>
            <p class="modal-info"><strong>Tipo:</strong> <span id="modal-tipo">Tipo</span></p>
            <p class="modal-info"><strong>Localização:</strong> <span id="modal-localizacao">Localização</span></p>

            <h4>Descrição da Vaga:</h4>
            <div class="modal-description" id="modal-descricao">
                Descrição detalhada da vaga...
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
            // Modal de detalhes da vaga
            $('.more-info-btn').on('click', function() {
                const vagaId = $(this).data('vaga-id');

                // Busca os detalhes completos da vaga via AJAX
                $.ajax({
                    type: 'GET',
                    url: 'buscar_vaga.php', // Você precisará criar este arquivo
                    data: {
                        id_vaga: vagaId
                    },
                    dataType: 'json',
                    success: function(vaga) {
                        // Preenche o modal com as informações da vaga
                        $('#modal-title').text(vaga.titulo_vaga);
                        $('#modal-area').text(vaga.nome_area || 'Área não especificada');
                        $('#modal-tipo').text(vaga.tipo_emprego);
                        $('#modal-localizacao').text(vaga.localizacao || 'Localização não especificada');
                        $('#modal-descricao').html(vaga.descricao ? vaga.descricao.replace(/\n/g, '<br>') : 'Sem descrição detalhada.');

                        // Define o ID da vaga no botão de candidatura
                        $('#modal-apply-btn').data('vaga-id', vagaId);

                        // Mostra o modal
                        $('#job-modal').addClass('active');
                    },
                    error: function() {
                        alert('Erro ao carregar detalhes da vaga. Tente novamente.');
                    }
                });
            });


            $('.more-info-btn').on('click', function() {
                const vagaId = $(this).data('vaga-id');
                const card = $(this).closest('.job-card');

                // Pega os dados diretamente do card
                const titulo = card.find('h3').text();
                const area = card.find('p').eq(0).text();
                const tipo = card.find('p').eq(1).text().replace('Tipo: ', '');
                const localizacao = card.find('p').eq(2).text().replace('Localização: ', '');

                // Busca a descrição (pode não estar completa no card)
                let descricao = '';
                if (card.find('p').length > 3) {
                    descricao = card.find('p').eq(4).text();
                } else {
                    descricao = 'Contate-nos para mais informações sobre esta vaga.';
                }

                // Preenche o modal
                $('#modal-title').text(titulo);
                $('#modal-area').text(area);
                $('#modal-tipo').text(tipo);
                $('#modal-localizacao').text(localizacao);
                $('#modal-descricao').html(descricao.replace(/\n/g, '<br>'));

                // Define o ID da vaga no botão de candidatura
                $('#modal-apply-btn').data('vaga-id', vagaId);

                // Mostra o modal
                $('#job-modal').addClass('active');
            });

            // Fechar modal
            $('#modal-close, #modal-btn-cancel').on('click', function() {
                $('#job-modal').removeClass('active');
            });

            // Botão de candidatura no modal
            $('#modal-apply-btn').on('click', function() {
                <?php if (!isset($_SESSION['id_usuario'])): ?>
                    redirectToLogin();
                    return false;
                <?php endif; ?>
                const vagaId = $(this).data('vaga-id');
                $('#confirm-yes').data('vaga-id', vagaId);

                // Fecha o modal de detalhes
                $('#job-modal').removeClass('active');

                // Abre o modal de confirmação
                $('#confirm-modal').addClass('active');
            });

            // Fechar modal de confirmação
            $('#confirm-no').on('click', function() {
                $('#confirm-modal').removeClass('active');
            });

            // Confirmar candidatura
            $('#confirm-yes').on('click', function() {
                const vagaId = $(this).data('vaga-id');

                // Desativa o botão temporariamente para evitar múltiplos cliques
                $(this).prop('disabled', true);

                // Processa a candidatura via AJAX
                $.ajax({
                    type: 'POST',
                    url: '', // A mesma página
                    data: {
                        ajax: 'candidatura',
                        id_vaga: vagaId
                    },
                    dataType: 'json',
                    success: function(response) {
                        // Fecha o modal de confirmação
                        $('#confirm-modal').removeClass('active');

                        if (response.success) {
                            // Mostra mensagem de sucesso
                            showToast(response.message);

                            // Atualiza o botão da vaga para "Candidatura Enviada"
                            $(`button[data-vaga-id="${vagaId}"]`).replaceWith(
                                $('<button class="already-applied" disabled>Candidatura Enviada</button>')
                            );

                            // Recarrega a página após 2 segundos para atualizar a lista de candidaturas
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            // Mostra mensagem de erro
                            alert(response.message || 'Erro ao processar candidatura. Tente novamente.');

                            // Reativa o botão
                            $('#confirm-yes').prop('disabled', false);
                        }
                    },
                    error: function() {
                        $('#confirm-modal').removeClass('active');
                        alert('Erro ao processar candidatura. Verifique sua conexão e tente novamente.');
                        $('#confirm-yes').prop('disabled', false);
                    }
                });
            });

            // Função para mostrar mensagem toast
            function showToast(message) {
                // Cria o elemento toast se ainda não existir
                if ($('#toast-message').length === 0) {
                    $('body').append('<div id="toast-message" class="toast-message"></div>');
                }

                // Define a mensagem e mostra o toast
                $('#toast-message').text(message).fadeIn();

                // Esconde o toast após 3 segundos
                setTimeout(function() {
                    $('#toast-message').fadeOut();
                }, 3000);
            }

            // Fechar modais ao clicar fora deles
            $('.modal-overlay').on('click', function(e) {
                if (e.target === this) {
                    $(this).removeClass('active');
                }
            });

            // Previne o fechamento do modal ao clicar no seu conteúdo
            $('.modal-content, .confirm-dialog').on('click', function(e) {
                e.stopPropagation();
            });

            // Animação suave ao rolar para âncoras
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();

                const target = $(this.getAttribute('href'));
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 800);
                }
            });
        });
    </script>