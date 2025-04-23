<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>ProLink - Vagas</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            font-family: 'Montserrat', sans-serif;
        }

        .professional-list {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            flex: 1;
        }

        .professional-item {
            display: flex;
            align-items: center;
            background-color: #2e2e2e;
            padding: 15px;
            margin: 10px 0;
            width: 80%;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .profile-pic {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 20px;
            background-image: url(../assets/img/office-icon.png);
            background-size: cover;
            background-position: center;
        }

        .professional-info {
            flex: 1;
            color: #fff;
        }

        .professional-name {
            font-size: 18px;
            font-weight: 600;
        }

        .professional-specialization {
            font-size: 14px;
            color: #cccccc;
        }

        .chat-btn {
            background-color: rgb(21, 118, 228);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
        }

        .chat-btn:hover {
            background-color: rgb(116, 154, 224);
            transform: scale(1.05);
        }

        .chat-icon {
            width: 30px;
            height: 30px;
            margin-left: 20px;
        }

        .footer-section {
            background-color: #2e2e2e;
            color: white;
            padding: 10px;
            text-align: center;
            position: relative;
            bottom: 0;
            width: 100%;
        }

        .footer-content {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .footer-logo {
            width: 40px;
            height: 40px;
        }
    </style>
</head>
<body>

<?php
include("../php/conexao.php"); 


$pdo = conectar();

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($searchQuery !== '') {
    try {
        $sql = $pdo->prepare("
            SELECT titulo_vaga, localizacao, tipo_emprego
            FROM Vagas
            WHERE titulo_vaga LIKE :searchQuery
        ");
        $sql->bindValue(":searchQuery", "%" . $searchQuery . "%");
        $sql->execute();

        $vagas = $sql->fetchAll(PDO::FETCH_ASSOC);

        if ($vagas) {
            echo "<div class='professional-list'>";
            foreach ($vagas as $vaga) {
                echo "<div class='professional-item'>";
                echo "<div class='profile-pic'></div>";
                echo "<div class='professional-info'>";
                echo "<div class='professional-name'>" . htmlspecialchars($vaga['titulo_vaga']) . "</div>";
                echo "<div class='professional-specialization'><strong>Local:</strong> " . htmlspecialchars($vaga['localizacao']) . "</div>";
                echo "<div class='professional-specialization'><strong>Tipo:</strong> " . htmlspecialchars($vaga['tipo_emprego']) . "</div>";
                echo "</div>";
                echo "<a href='#'><button class='chat-btn'>Candidatar-se</button></a>";
                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "<div class='professional-list'><p>Nenhuma vaga encontrada com o termo '$searchQuery'.</p></div>";
        }
    } catch (Exception $erro) {
        echo "<div class='professional-list'><p>Erro ao buscar vagas: " . $erro->getMessage() . "</p></div>";
    }
} else {
    echo "<div class='professional-list'><p>Por favor, forneça um termo de pesquisa.</p></div>";
}
?>

<header>
    <nav class="navbar">
        <div class="logo-container">
            <img src="../assets/img/globo-mundial.png" alt="Logo" class="logo-icon">
            <div class="logo">ProLink</div>
        </div>
        <ul class="menu">
            <li><a href="../php/index.php">Home</a></li>
            <li><a href="../php/pagina_emprego.php">Oportunidades de Trabalho</a></li>
            <li><a href="../php/pagina_webinar.php">Webinars</a></li>
        </ul>
        <div class="profile">
            <a href="../php/perfil.php"><img src="../assets/img/user-48.png" alt="Profile" class="profile-icon"></a>
        </div>
    </nav>
</header>

<footer class="footer-section">
    <div class="footer-content">
        <img src="../assets/img/globo-mundial.png" alt="Logo da Empresa" class="footer-logo">
        <p>&copy; 2024 ProLink. Todos os direitos reservados.</p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../assets/js/script.js"></script>
</body>
</html>
