<?php
// PRIMEIRA LINHA DO ARQUIVO - antes de qualquer saída
session_start();
include("../php/conexao.php"); 

// Funções de busca (sanitizadas)
function buscarPorNome($pdo, $searchQuery) {
    // Sanitizar entrada
    $searchQuery = filter_var($searchQuery, FILTER_SANITIZE_SPECIAL_CHARS);
    
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
    // Sanitizar entrada
    $searchQuery = filter_var($searchQuery, FILTER_SANITIZE_SPECIAL_CHARS);
    
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
    // Sanitizar entrada
    $searchQuery = filter_var($searchQuery, FILTER_SANITIZE_SPECIAL_CHARS);
    
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

// Processar a pesquisa com sanitização
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS);
    $searchQuery = trim($searchQuery);
    
    // Validar se não está vazio após sanitização
    if (empty($searchQuery)) {
        $searchQuery = '';
    }
}

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
            // Sanitizar email antes de usar como chave
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
                $email = filter_var($profissional['email'], FILTER_SANITIZE_EMAIL);
                
                // Validar email
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue; // Pular este profissional se email inválido
                }
                
                $fotoPerfil = !empty($profissional['foto_perfil']) 
                    ? "data:image/jpeg;base64," . base64_encode($profissional['foto_perfil'])
                    : "../assets/img/userp.jpg";
                
                $qrCodePath = !empty($profissional['qr_code']) ? 
                    'get_qrcode.php?file=' . basename(htmlspecialchars($profissional['qr_code'], ENT_QUOTES, 'UTF-8')) : '';
                
                // Obter o valor do qr_code da tabela Usuario para este email
                $stmt = $pdo->prepare("SELECT qr_code FROM Usuario WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                $qrCodeValue = $stmt->fetchColumn();
                $qrCodeValue = htmlspecialchars($qrCodeValue, ENT_QUOTES, 'UTF-8');
                
                $resultadosHTML .= "<div class='professional-item'>";
                $resultadosHTML .= "<div class='profile-pic' style='background-image: url($fotoPerfil);'></div>";
                $resultadosHTML .= "<div class='professional-info'>";
                $resultadosHTML .= "<div class='professional-name'>" . $nome . "</div>";
                
                if (!empty($formacao)) {
                    $resultadosHTML .= "<div class='professional-specialization'>" . $formacao . "</div>";
                }
                
                if (!empty($habilidades)) {
                    $resultadosHTML .= "<p><strong>Habilidades:</strong> " . $habilidades . "</p>";
                }
                
                if (!empty($experiencia)) {
                    $resultadosHTML .= "<p><strong>Experiência:</strong> " . nl2br($experiencia) . "</p>";
                }
                
                if (!empty($email)) {
                    $resultadosHTML .= "<p><strong>Email:</strong> " . $email . "</p>";
                }
                
                $resultadosHTML .= "</div>";
                
                if (!empty($qrCodePath)) {
                    if ($usuarioLogado) {
                        $emailEscaped = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
                        $resultadosHTML .= "<button class='chat-btn show-qr' data-qrcode-path='$qrCodePath' data-email='" . $emailEscaped . "' data-qrcode='" . $qrCodeValue . "'>";
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

    <!-- Estilo responsivo adaptado -->
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

        /* Área de busca */
        .search-container {
            display: flex;
            align-items: center;
            margin: 20px;
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
            background-color: rgb(21, 118, 228);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            height: 40px;
            white-space: nowrap;
            transition: background-color 0.3s;
        }

        .search-btn:hover {
            background-color: rgb(116, 154, 224);
        }

        /* Lista de Profissionais */
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
            width: 60px;
            height: 60px;
            min-width: 60px; /* Impede que a imagem encolha */
            border-radius: 50%;
            margin-right: 20px;
            background-size: cover;
            background-position: center;
        }

        .professional-info {
            flex: 1;
            color: #fff;
            overflow: hidden; /* Previne que o texto ultrapasse os limites */
        }

        .professional-name {
            font-size: 18px;
            font-weight: 600;
        }

        .professional-specialization {
            font-size: 14px;
            color: #cccccc;
            margin-bottom: 5px;
        }

        .professional-info p {
            margin-bottom: 5px;
            word-wrap: break-word; /* Quebra palavras longas */
        }

        .chat-btn {
            background-color: rgb(21, 118, 228);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
            display: flex;
            align-items: center;
            white-space: nowrap;
        }

        .chat-btn:hover {
            background-color: rgb(116, 154, 224);
            transform: scale(1.05);
        }

        .chat-icon {
            width: 30px;
            height: 30px;
            margin-left: 10px;
        }

        /* Modal QR Code */
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
            padding: 25px;
            border-radius: 10px;
            max-width: 90vw;
            width: auto;
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

        /* Footer */
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
            
            .professional-item {
                width: 95%;
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .profile-pic {
                margin-right: 0;
                margin-bottom: 15px;
                width: 80px;
                height: 80px;
            }
            
            .professional-info {
                width: 100%;
                margin-bottom: 15px;
            }
            
            .chat-btn {
                width: 100%;
                justify-content: center;
            }

             .profile-icon{
                display: none;
            }
        }

        @media (max-width: 768px) {
            .professional-item {
                padding: 10px;
            }
            
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
            }
            
            .search-bar, 
            .search-btn {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .modal-content {
                padding: 15px;
            }
            
            .profile-icon {
                display: none;
            }

             .profile-icon{
                display: none;
            }
        }

        @media (max-width: 480px) {
            .profile-pic {
                width: 60px;
                height: 60px;
            }
            
            .professional-name {
                font-size: 16px;
            }
            
            .professional-info p {
                font-size: 14px;
            }
            
            .link-container {
                flex-direction: column;
            }

             .profile-icon{
                display: none;
            }
            
            #profileLink, 
            #copyLink {
                width: 100%;
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
            <ul class="menu" id="menu">
                <li><a href="../php/index.php">Home</a></li>
                <li><a href="../php/paginaEmprego.php">Oportunidades de Trabalho</a></li>
                <li><a href="../php/pagina_webinar.php">Webinars</a></li>
                <?php if (!isset($_SESSION['usuario_logado'])): ?>
                    <li><a href="../pages/login.html">Login</a></li>
                <?php endif; ?>
            </ul>
            <div class="profile">
                <a href="../php/perfil.php"><img src="../assets/img/user-48.png" alt="Profile" class="profile-icon"></a>
            </div>
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
                placeholder="Pesquisar por nome, formação ou habilidades..."
                value="<?= $searchQueryDisplay ?>">
            <button type="submit" class="search-btn">Procurar</button>
        </div>
    </form>

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
        
        // ===== SEU CÓDIGO EXISTENTE CONTINUA AQUI =====
        
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
                    closeMenuBtn.style.display = 'flex'; // Mostrar botão de fechar
                });
            }
            
            if (closeMenuBtn) {
                closeMenuBtn.addEventListener('click', function() {
                    menu.classList.remove('active');
                    if (mobileMenu) mobileMenu.style.display = 'block';
                    this.style.display = 'none'; // Esconder botão de fechar
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
    });

    // Função para buscar profissionais com validação de segurança (VERSÃO MELHORADA)
    function buscarProfissionais() {
        // Pega o valor do campo de busca
        const inputElement = document.getElementById('searchInput');
        
        // Verificar se o input ainda existe e não foi manipulado
        if (!inputElement || inputElement.type !== 'text') {
            console.warn('Input de busca foi manipulado ou não existe');
            mostrarAvisoSeguranca();
            return false;
        }
        
        let termoBusca = inputElement.value.trim();
        
        // Sanitização do lado do cliente (defesa em profundidade)
        termoBusca = termoBusca.replace(/[\x00-\x1F\x7F]/g, ''); // Remove caracteres de controle
        termoBusca = termoBusca.substring(0, 100); // Limita o tamanho
        
        // Verifica se o termo de busca é válido
        if (!termoBusca || !/^[\w\sáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\-.,;:!?@#%&*()+=]{3,}$/.test(termoBusca)) {
            // Mostra mensagem de erro acessível (melhor que alert)
            const errorElement = document.createElement('div');
            errorElement.className = 'search-error';
            errorElement.textContent = 'Por favor, digite um termo válido (mínimo 3 caracteres).';
            errorElement.setAttribute('role', 'alert');
            errorElement.setAttribute('aria-live', 'assertive');
            
            // Remove mensagens anteriores
            const oldError = document.querySelector('.search-error');
            if (oldError) oldError.remove();
            
            // Insere a mensagem após a barra de pesquisa
            inputElement.insertAdjacentElement('afterend', errorElement);
            inputElement.focus();
            return false;
        }
        
        // Codifica o termo para URL (previne XSS e injection na URL)
        const termoCodificado = encodeURIComponent(termoBusca)
            .replace(/%20/g, '+') // Espaços como +
            .replace(/[!'()*]/g, function(c) {
                return '%' + c.charCodeAt(0).toString(16);
            });
        
        console.log('Termo de busca validado:', termoCodificado);
        return true;
    }
</script>