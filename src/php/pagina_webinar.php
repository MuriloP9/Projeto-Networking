<?php
session_start();

include("../php/conexao.php");

$pdo = conectar();

// Buscar webinars com base no termo de pesquisa
$termoBusca = isset($_GET['search']) ? trim(filter_var($_GET['search'], FILTER_SANITIZE_SPECIAL_CHARS)) : '';
$webinars = [];

try {
    if (!empty($termoBusca)) {
        // Sanitização adicional para o termo de busca
        $termoBusca = preg_replace('/[^\w\sáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\-.,;:!?@#%&*()+=]/u', '', $termoBusca);
        $termoBusca = substr($termoBusca, 0, 100); // Limita o tamanho
        
        $stmt = $pdo->prepare("SELECT * FROM Webinar 
                              WHERE (tema LIKE ? OR palestrante LIKE ? OR descricao LIKE ?)
                              AND ativo = 1
                              ORDER BY data_hora DESC");
        $termoLike = "%$termoBusca%";
        $stmt->execute([$termoLike, $termoLike, $termoLike]);
    } else {
        $stmt = $pdo->query("SELECT * FROM Webinar WHERE ativo = 1 ORDER BY data_hora DESC");
    }

    $webinars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sanitizar os dados dos webinars
    foreach ($webinars as &$webinar) {
        $webinar['tema'] = htmlspecialchars($webinar['tema'], ENT_QUOTES, 'UTF-8');
        $webinar['palestrante'] = htmlspecialchars($webinar['palestrante'], ENT_QUOTES, 'UTF-8');
        $webinar['descricao'] = htmlspecialchars($webinar['descricao'], ENT_QUOTES, 'UTF-8');
        $webinar['link'] = filter_var($webinar['link'], FILTER_SANITIZE_URL);
    }
    unset($webinar); // Quebra a referência
    
} catch (PDOException $e) {
    error_log("Erro ao buscar webinars: " . $e->getMessage());
    echo "<script>alert('Erro ao buscar webinars.');</script>";
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
            display: none; /* Será mostrado via JS quando o menu estiver ativo */
            position: fixed; /* Fixo na tela */
            top: 20px; /* Espaço do topo */
            right: 20px; /* Espaço da direita */
            padding: 10px;
            background-color: rgba(14, 23, 104, 0.8); /* Fundo semi-transparente */
            border-radius: 50%; /* Formato circular */
            width: 40px; /* Largura fixa */
            height: 40px; /* Altura fixa */
            display: flex; /* Para centralizar o ícone */
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 1200; /* Acima do menu */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2); /* Sombra para destacar */
        }

        .menu-close-item .menu-icon {
            width: 24px;
            height: 24px;
            transform: rotate(45deg); /* Rotacionar para formar um X */
        }

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
            
            .search-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-bar,
            .search-btn {
                width: 100%;
                margin-bottom: 10px;
                border-radius: 5px;
            }
            
            .webinar-listings {
                grid-template-columns: 1fr;
            }
            
            .contact-container {
                flex-direction: column;
            }

             }
            .profile-icon{
                display: none;
            }

        @media (max-width: 768px) {
            .webinars-section {
                padding: 20px;
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
            
            .map-container iframe {
                width: 100%;
                height: 250px;
            }
            .profile-icon{
                display: none;
            }

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

    <section class="webinars-section">
        <h2>Webinars Disponíveis</h2>

        <form method="GET" action="">
            <div class="search-container">
                <input type="text" name="search" id="searchInput" class="search-bar"
                    placeholder="Pesquisar por tema, palestrante ou descrição..."
                   value="<?= htmlspecialchars($termoBusca, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="search-btn">Procurar</button>
            </div>
        </form>

        <!-- Lista de webinars -->
        <div class="webinar-listings">
            <?php if (empty($webinars)): ?>
                <p style="color: #000000;">Nenhum webinar encontrado.</p>
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

    // ===== CÓDIGO ORIGINAL DOS WEBINARS =====
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
                mobileMenu.style.display = 'block';
                this.style.display = 'none'; // Esconder botão de fechar
            });
        }
        
        // Fechar o menu ao clicar em um link
        const menuLinks = menu.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    menu.classList.remove('active');
                    mobileMenu.style.display = 'block';
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

    // Inicializar proteções
    protegerInputs();
</script>
</body>
</html>