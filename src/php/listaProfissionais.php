<?php
// PRIMEIRA LINHA DO ARQUIVO - antes de qualquer saída
session_start();
include("../php/conexao.php"); 

// Funções de busca (mantidas iguais)
function buscarPorNome($pdo, $searchQuery) {
    $query = "SELECT U.nome, P.formacao, P.experiencia_profissional, U.email, P.habilidades, U.foto_perfil, U.qr_code
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
    $query = "SELECT U.nome, P.formacao, P.experiencia_profissional, U.email, P.habilidades, U.foto_perfil, U.qr_code
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
    $query = "SELECT U.nome, P.formacao, P.experiencia_profissional, U.email, P.habilidades, U.foto_perfil, U.qr_code
              FROM Perfil P
              INNER JOIN Usuario U ON P.id_usuario = U.id_usuario
              WHERE P.habilidades LIKE :searchPattern";
    
    $sql = $pdo->prepare($query);
    $searchPattern = '%' . $searchQuery . '%';
    $sql->bindValue(":searchPattern", $searchPattern, PDO::PARAM_STR);
    $sql->execute();
    
    return $sql->fetchAll(PDO::FETCH_ASSOC);
}

// Processar a pesquisa
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Inicializar variáveis para armazenar os resultados
$resultadosHTML = '';
$modalHTML = '';

if ($searchQuery !== '') {
    try {
        $pdo = conectar();
        
        // Modificar as consultas para incluir qr_code da tabela Usuario
        $resultadosNome = buscarPorNome($pdo, $searchQuery);
        $resultadosFormacao = buscarPorFormacao($pdo, $searchQuery);
        $resultadosHabilidades = buscarPorHabilidades($pdo, $searchQuery);
        
        // Combinar resultados, removendo duplicatas
        $profissionais = array_merge($resultadosNome, $resultadosFormacao, $resultadosHabilidades);
        $profissionaisUnicos = array();
        
        foreach ($profissionais as $profissional) {
            $email = $profissional['email'];
            if (!isset($profissionaisUnicos[$email])) {
                $profissionaisUnicos[$email] = $profissional;
            }
        }
        
        // Verificar se o usuário está logado
        $usuarioLogado = isset($_SESSION['id_usuario']);
        
        // Construir HTML dos resultados
        if (empty($profissionaisUnicos)) {
            $resultadosHTML = "<div class='professional-list'><p>Nenhum profissional encontrado para '$searchQuery'.</p></div>";
        } else {
            $resultadosHTML = "<div class='professional-list'>";
            foreach ($profissionaisUnicos as $profissional) {
                $fotoPerfil = !empty($profissional['foto_perfil']) 
                    ? "data:image/jpeg;base64," . base64_encode($profissional['foto_perfil'])
                    : "../assets/img/userp.jpg";
                
                $qrCodePath = !empty($profissional['qr_code']) ? 
                    'get_qrcode.php?file=' . basename(htmlspecialchars($profissional['qr_code'])) : '';
                
                // Obter o valor do qr_code da tabela Usuario para este email
                $stmt = $pdo->prepare("SELECT qr_code FROM Usuario WHERE email = :email");
                $stmt->bindParam(':email', $profissional['email']);
                $stmt->execute();
                $qrCodeValue = $stmt->fetchColumn();
                
                $resultadosHTML .= "<div class='professional-item'>";
                $resultadosHTML .= "<div class='profile-pic' style='background-image: url($fotoPerfil);'></div>";
                $resultadosHTML .= "<div class='professional-info'>";
                $resultadosHTML .= "<div class='professional-name'>" . htmlspecialchars($profissional['nome']) . "</div>";
                
                if (!empty($profissional['formacao'])) {
                    $resultadosHTML .= "<div class='professional-specialization'>" . htmlspecialchars($profissional['formacao']) . "</div>";
                }
                
                if (!empty($profissional['habilidades'])) {
                    $resultadosHTML .= "<p><strong>Habilidades:</strong> " . htmlspecialchars($profissional['habilidades']) . "</p>";
                }
                
                if (!empty($profissional['experiencia_profissional'])) {
                    $resultadosHTML .= "<p><strong>Experiência:</strong> " . nl2br(htmlspecialchars($profissional['experiencia_profissional'])) . "</p>";
                }
                
                if (!empty($profissional['email'])) {
                    $resultadosHTML .= "<p><strong>Email:</strong> " . htmlspecialchars($profissional['email']) . "</p>";
                }
                
                $resultadosHTML .= "</div>";
                
                if (!empty($qrCodePath)) {
                    if ($usuarioLogado) {
                        $resultadosHTML .= "<button class='chat-btn show-qr' data-qrcode-path='$qrCodePath' data-email='" . htmlspecialchars($profissional['email']) . "' data-qrcode='" . htmlspecialchars($qrCodeValue) . "'>";
                        $resultadosHTML .= "Contato App<img src='../assets/img/adicionar-usuarios.png' alt='qrcode' class='chat-icon'>";
                        $resultadosHTML .= "</button>";
                    } else {
                        // Modificação aqui: botão para redirecionar para login.html
                        $resultadosHTML .= "<button class='chat-btn login-redirect' onclick=\"window.location.href='../pages/login.html';\">";
                        $resultadosHTML .= "Contato App<img src='../assets/img/adicionar-usuarios.png' alt='qrcode' class='chat-icon'>";
                        $resultadosHTML .= "</button>";
                    }
                } else {
                    $resultadosHTML .= "<button class='chat-btn' disabled title='QR Code não disponível'>";
                    $resultadosHTML .= "Contato App<img src='../assets/img/adicionar-usuarios.png' alt='qrcode' class='chat-icon'>";
                    $resultadosHTML .= "</button>";
                }
                
                $resultadosHTML .= "</div>";
            }
            $resultadosHTML .= "</div>";
            
            if ($usuarioLogado) {
                $modalHTML = '
                <div id="qrModal" class="modal">
                    <div class="modal-content">
                        <span class="close-modal">&times;</span>
                        <h3>QR Code de Contato</h3>
                        <div id="qrCodeContainer">
                            <img id="qrCodeImage" src="" alt="QR Code" style="max-width:250px">
                        </div>
                        <div class="link-container">
                            <input type="text" id="profileLink" readonly>
                            <button id="copyLink">Copiar Link</button>
                        </div>
                    </div>
                </div>';
            }
        }
    } catch (Exception $erro) {
        $resultadosHTML = "<div class='professional-list'><p>Erro ao buscar profissionais: " . htmlspecialchars($erro->getMessage()) . "</p></div>";
    }
} else {
    $resultadosHTML = "<div class='professional-list'><p>Por favor, forneça um termo de pesquisa.</p></div>";
}
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

    <!-- Restante do seu CSS aqui -->
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

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
        }

        .modal-content {
            background-color: #2e2e2e;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 300px;
            text-align: center;
            color: white;
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover {
            color: white;
        }

        #qrCodeContainer {
            margin: 20px auto;
            padding: 10px;
            background: white;
            display: inline-block;
        }

        .link-container {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        #profileLink {
            flex: 1;
            padding: 8px;
            border-radius: 5px;
            border: none;
        }

        #copyLink {
            background-color: rgb(21, 118, 228);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        #copyLink:hover {
            background-color: rgb(116, 154, 224);
        }

         /* Adicione isso ao seu CSS existente */
    .modal-content {
        max-width: 90vw; /* Largura máxima responsiva */
        width: auto;
        padding: 25px;
    }

    #qrCodeContainer {
        max-width: 100%;
        overflow: hidden;
        margin: 15px auto;
        text-align: center;
    }

    #qrCodeImage {
        max-width: 100%;
        height: auto;
        display: block;
        margin: 0 auto;
        border: 1px solid #ddd;
        padding: 5px;
        background: white;
    }

    .link-container {
        margin-top: 15px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }

    #profileLink {
        flex: 1;
        min-width: 200px;
        padding: 8px;
        border-radius: 5px;
        border: 1px solid #ccc;
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

    <header>
        <nav class="navbar">
            <div class="logo-container">
                <img src="../assets/img/globo-mundial.png" alt="Logo" class="logo-icon">
                <div class="logo">ProLink</div>
            </div>
            <ul class="menu">
                <li><a href="../php/index.php">Home</a></li>
                <li><a href="../php/paginaEmprego.php">Oportunidades de Trabalho</a></li>
                <li><a href="../php/pagina_webinar.php">Webinars</a></li>
            </ul>
            <div class="profile">
                <a href="../php/perfil.php"><img src="../assets/img/user-48.png" alt="Profile" class="profile-icon"></a>
            </div>
        </nav>
    </header>

    <?php echo $resultadosHTML; ?>

    <footer class="footer-section">
        <div class="footer-content">
            <img src="../assets/img/globo-mundial.png" alt="Logo da Empresa" class="footer-logo">
            <p>&copy; 2024 ProLink. Todos os direitos reservados.</p>
        </div>
    </footer> 

    <?php echo $modalHTML; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/script.js"></script>

    <script>
    $(document).ready(function() {
        // Mostrar modal com QR Code
        $('.show-qr').click(function() {
            const qrCodePath = $(this).data('qrcode-path');
            const email = $(this).data('email');
            const qrcode = $(this).data('qrcode');
            
            // Adiciona timestamp para evitar cache
            const timestamp = new Date().getTime();
            $('#qrCodeImage').attr('src', qrCodePath + '&t=' + timestamp);
            
            // Usar o valor da coluna qr_code da tabela Usuario se disponível, senão usar o email
            if (qrcode) {
                $('#profileLink').val(qrcode);
            } else {
                const profileLink = window.location.origin + '/perfil.php?email=' + encodeURIComponent(email);
                $('#profileLink').val(profileLink);
            }
            
            $('#qrModal').show();
        });
        
        // Fechar modal
        $('.close-modal').click(function() {
            $('#qrModal').hide();
        });
        
        // Copiar link
        $('#copyLink').click(function() {
            const linkInput = document.getElementById('profileLink');
            linkInput.select();
            document.execCommand('copy');
            
            $(this).text('Copiado!');
            setTimeout(() => {
                $(this).text('Copiar Link');
            }, 2000);
        });
        
        // Fechar modal ao clicar fora
        $(window).click(function(event) {
            if (event.target.id === 'qrModal') {
                $('#qrModal').hide();
            }
        });
    });
</script>
</body>
</html>