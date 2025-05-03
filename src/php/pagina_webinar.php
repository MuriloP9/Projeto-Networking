<?php
session_start();

include("../php/conexao.php");

$pdo = conectar();

// Buscar webinars com base no termo de pesquisa
$termoBusca = isset($_GET['search']) ? trim($_GET['search']) : '';
$webinars = [];

try {
    if (!empty($termoBusca)) {
        $stmt = $pdo->prepare("SELECT * FROM Webinar 
                              WHERE tema LIKE ? OR palestrante LIKE ? OR descricao LIKE ?
                              ORDER BY data_hora DESC");
        $termoLike = "%$termoBusca%";
        $stmt->execute([$termoLike, $termoLike, $termoLike]);
    } else {
        $stmt = $pdo->query("SELECT * FROM Webinar ORDER BY data_hora DESC");
    }

    $webinars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<script>alert('Erro ao buscar webinars: " . addslashes($e->getMessage()) . "');</script>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProLink - Webinars</title>
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

        /* Section - Webinars */
        .webinars-section {
            padding: 40px;
            background-color: #f9f9f9;
            min-height: 70vh;
        }

        .webinars-section h2 {
            font-size: 2em;
            margin-bottom: 20px;
            color: #333;
        }

        .search-container {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }

        .search-bar {
            flex-grow: 2;
            padding: 10px;
            font-size: 1em;
            border-radius: 5px;
            border: 1px solid #ccc;
            height: 40px;
            box-sizing: border-box;
        }

        .search-btn {
            padding: 0 20px;
            font-size: 1em;
            background-color: #0e1768;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            height: 40px;
            white-space: nowrap;
            transition: background-color 0.3s;
        }

        .search-btn:hover {
            background-color: #3b6ebb;
        }

        /* Webinar Listings */
        .webinar-listings {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .webinar-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            color: #333;
        }

        .webinar-card h3 {
            margin: 0 0 10px 0;
            color: #0e1768;
            font-size: 1.3em;
        }

        .webinar-card .webinar-date {
            color: #666;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .webinar-card .webinar-speaker {
            font-style: italic;
            margin-bottom: 10px;
        }

        .webinar-card .webinar-description {
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .watch-btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #0e1768;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .watch-btn:hover {
            background-color: #3b6ebb;
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
            color: #333;
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

        /* Media Queries */
        @media (max-width: 768px) {
            .webinar-listings {
                grid-template-columns: 1fr;
            }

            .search-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-bar,
            .search-btn {
                width: 100%;
                margin-bottom: 10px;
            }

            .contact-container {
                flex-direction: column;
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
            <ul class="menu">
                <li><a href="../php/index.php">Home</a></li>
                <li><a href="../php/paginaEmprego.php">Oportunidades</a></li>
                <li><a href="#contato">Contato</a></li>
                <?php if (!isset($_SESSION['usuario_logado'])): ?>
                    <li><a href="../pages/login.html">Login</a></li>
                <?php endif; ?>
            </ul>
            <div class="profile">
                <a href="../php/perfil.php"><img src="../assets/img/user-48.png" alt="Profile" class="profile-icon"></a>
            </div>
        </nav>
    </header>

    <section class="webinars-section">
        <h2>Webinars Disponíveis</h2>

        <form method="GET" action="">
            <div class="search-container">
                <input type="text" name="search" id="searchInput" class="search-bar"
                    placeholder="Pesquisar por tema, palestrante ou descrição..."
                    value="<?= htmlspecialchars($termoBusca) ?>">
                <button type="submit" class="search-btn">Procurar</button>
            </div>
        </form>

        <!-- Lista de webinars -->
        <div class="webinar-listings">
            <?php if (empty($webinars)): ?>
                <p>Nenhum webinar encontrado.</p>
            <?php else: ?>
                <?php foreach ($webinars as $webinar): ?>
                    <div class="webinar-card">
                        <h3><?= htmlspecialchars($webinar['tema']) ?></h3>
                        <p class="webinar-date">
                            <?= date('d/m/Y H:i', strtotime($webinar['data_hora'])) ?>
                        </p>
                        <p class="webinar-speaker">Palestrante: <?= htmlspecialchars($webinar['palestrante']) ?></p>
                        <?php if (!empty($webinar['descricao'])): ?>
                            <p class="webinar-description"><?= nl2br(htmlspecialchars($webinar['descricao'])) ?></p>
                        <?php endif; ?>
                        <a href="<?= htmlspecialchars($webinar['link']) ?>" target="_blank" class="watch-btn">
                            Assistir Webinar
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

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
        // Função para buscar webinars via AJAX (opcional)
        function buscarWebinars() {
            const termo = document.getElementById('searchInput').value;
            window.location.href = '?search=' + encodeURIComponent(termo);
        }

        // Adicionar evento de tecla para buscar ao pressionar Enter
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                buscarWebinars();
            }
        });
    </script>
</body>
</html>