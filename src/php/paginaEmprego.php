<?php
session_start();

include("../php/conexao.php");

$pdo = conectar();

// Processar candidatura se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_vaga'])) {
    if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_usuario'])) {
        echo "<script>alert('Você precisa estar logado para se candidatar a vagas.');</script>";
    } else {
        $id_vaga = $_POST['id_vaga'];
        $id_usuario = $_SESSION['id_usuario'];

        try {
            // Primeiro obtemos o id_perfil do usuário
            $stmt = $pdo->prepare("SELECT id_perfil FROM Perfil WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);
            $perfil = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$perfil) {
                echo "<script>alert('Você precisa completar seu perfil antes de se candidatar.');</script>";
            } else {
                $id_perfil = $perfil['id_perfil'];

                // Verificar se já existe uma candidatura para esta vaga
                $stmt = $pdo->prepare("SELECT * FROM Candidatura WHERE id_vaga = ? AND id_perfil = ?");
                $stmt->execute([$id_vaga, $id_perfil]);

                if ($stmt->rowCount() > 0) {
                    echo "<script>alert('Você já se candidatou a esta vaga.');</script>";
                } else {
                    // Inserir nova candidatura
                    $stmt = $pdo->prepare("INSERT INTO Candidatura (id_vaga, id_perfil, data_candidatura, status) 
                                         VALUES (?, ?, GETDATE(), 'Pendente')");
                    $stmt->execute([$id_vaga, $id_perfil]);
                    echo "<script>alert('Candidatura realizada com sucesso!');</script>";
                }
            }
        } catch (PDOException $e) {
            echo "<script>alert('Erro ao processar candidatura: " . addslashes($e->getMessage()) . "');</script>";
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

        .apply-btn:hover {
            background-color: #3b6ebb;
        }

        /* Saved Jobs Section */
        .saved-jobs-section {
            padding: 40px;

            background-color: #ffffff;
        }

        .saved-jobs-container {
            border: 2px solid #ccc;
            border-radius: 15px;
            padding: 20px;
            background-color: #0e1768;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        .saved-jobs-container p {
            align-items: center;
        }


        /* Notifications Section */
        .notifications-section {
            align-items: center;
            color: #333;
            padding: 40px;
            background-color: #f9f9f9;
        }

        .notifications-form .form-input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #0e1768;
            font-size: 1em;
        }

        .notifications-btn {
            width: 100%;
            padding: 10px;
            font-size: 1em;
            background-color: #0e1768;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .notifications-btn:hover {
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

            .job-listings,
            .highlighted-job-listings {
                grid-template-columns: 1fr;
            }

            .search-filter-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-bar,
            .filter-dropdown,
            .search-btn {
                margin-bottom: 10px;
            }

            .contact-container {
                flex-direction: column;
            }

            .saved-jobs-container {
                padding: 10px;
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
                <li><a href="../php/pagina_webinar.php">Webinars</a></li>
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

                        <form method="POST" action="">
                            <input type="hidden" name="id_vaga" value="<?= $vaga['id_vaga'] ?>">
                            <button type="submit" class="apply-btn">Candidatar-se</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>


    <!-- Sistema de Salvamento de Vagas -->
    <section id="saved-jobs-section" class="saved-jobs-section">
        <h2>Vagas Salvas</h2>
        <form action="/save-jobs" method="post" class="saved-jobs-form">
            <div class="saved-jobs-container">
                <div class="saved-jobs-listings">
                    <!-- Vagas salvas aparecerão aqui -->
                    <p>Você ainda não salvou nenhuma vaga.</p>
                </div>
            </div>
        </form>
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
        // Função para buscar vagas via AJAX (opcional)
        function buscarVagas() {
            const termo = document.getElementById('searchInput').value;
            window.location.href = '?search=' + encodeURIComponent(termo);
        }

        // Adicionar evento de tecla para buscar ao pressionar Enter
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                buscarVagas();
            }
        });
    </script>
</body>

</html>