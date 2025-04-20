<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProLink - Profissionais</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Mantendo o estilo global -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <style>
        /* Estilo da lista de profissionais */
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
            flex: 1; /* Para ocupar o espaço disponível acima do footer */
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
            background-color:rgb(21, 118, 228);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
        }

        .chat-btn:hover {
            background-color:rgb(116, 154, 224);
            transform: scale(1.05);
        }

        .chat-icon {
            width: 30px;
            height: 30px;
            margin-left: 20px;
        }


        /* Fixando o footer na parte inferior */
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
session_start(); // Inicia a sessão
function conectar() {
    //$local_server = "PC_NASA\SQLEXPRESS";
    $local_server = "Book3-Marina";
    $usuario_server = "sa";               
    $senha_server = "etesp";              
    $banco_de_dados = "prolink";         

    try {
        $pdo = new PDO("sqlsrv:server=$local_server;database=$banco_de_dados", $usuario_server, $senha_server);
        return $pdo;
    } catch (Exception $erro) {
        echo "ATENÇÃO - ERRO NA CONEXÃO: " . $erro->getMessage();
        die;
    }
}

$pdo = conectar(); 

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : ''; //Captura do Termo de Pesquisa -> $_GET['search']: Obtém o termo de pesquisa enviado via URL 
//Operador ternário (?:):
//Se search for informado, usa o valor.
//Se não for informado, define searchQuery como string vazia ('').

if ($searchQuery !== '') {
    try {
        // Consulta para buscar profissionais pela formação
        $sql = $pdo->prepare("
            SELECT U.nome, P.formacao, P.experiencia_profissional, U.email  
            FROM Perfil P
            INNER JOIN Usuario U ON P.id_usuario = U.id_usuario
            WHERE P.formacao LIKE :searchQuery
        ");

        $sql->bindValue(":searchQuery", "%" . $searchQuery . "%");
        $sql->execute();

        $profissionais = $sql->fetchAll(PDO::FETCH_ASSOC);

        if ($profissionais) {
            echo "<div class='professional-list'>";
            foreach ($profissionais as $profissional) {
                echo "<div class='professional-item'>";
                echo "<div class='profile-pic' style='background-image: url(../assets/img/userp.jpg);'></div>"; // Imagem de perfil padrão
                echo "<div class='professional-info'>";
                echo "<div class='professional-name'>" . htmlspecialchars($profissional['nome']) . "</div>";
                echo "<div class='professional-specialization'>" . htmlspecialchars($profissional['formacao']) . "</div>";
                echo "<p><strong>Experiência:</strong> " . nl2br(htmlspecialchars($profissional['experiencia_profissional'])) . "</p>";
                echo "<p><strong>Email:</strong> " . htmlspecialchars($profissional['email']) . "</p>";
                echo "</div>";
                echo "<a href='#'><button class='chat-btn'>QR Code<img src='../assets/img/icons8-qrcodeb.png' alt='qrcode' class='chat-icon'></button></a>";
                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "<div class='professional-list'><p>Nenhum profissional encontrado para a formação '$searchQuery'.</p></div>";
        }
    } catch (Exception $erro) {
        echo "<div class='professional-list'><p>Erro ao buscar profissionais: " . $erro->getMessage() . "</p></div>";
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
                <a href="../php//perfil.php"><img src="../assets/img/user-48.png" alt="Profile" class="profile-icon"></a>
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
