<?php
session_start();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProLink - Networking Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
</head>
<body>
<header>
    <nav class="navbar">
        <div class="logo-container">
            <img src="../assets/img/globo-mundial.png" alt="Logo" class="logo-icon">
            <div class="logo">ProLink</div>
        </div>
        
        <!-- Botão de menu hambúrguer (só aparece em mobile) -->
        <div class="menu-toggle" id="mobile-menu">
            <img src="../assets/img/icons8-menu-48.png" alt="Menu" class="menu-icon">
        </div>
        
        <!-- Menu de navegação -->
        <ul class="menu" id="menu">
            <li><a href="#home">Home</a></li>
            <li><a href="#webinars">Webinars</a></li>
            <li><a href="#job-opportunities">Oportunidades de Trabalho</a></li>
            <li><a href="#contato">Contato</a></li>
            <?php if (!isset($_SESSION['usuario_logado'])): ?>
                <li><a href="../pages/login.html">Login</a></li>
                <?php if (!isset($_SESSION['cadastro_realizado'])): ?>
                    <li><a href="../pages/cadastro.html" class="signup-btn">Cadastre-se</a></li>
                <?php endif; ?>
            <?php else: ?>
                <li><button class="logout-btn" onclick="logout()">Olá, <?php echo $_SESSION['nome_usuario']; ?></button></li>
            <?php endif; ?>
            <li class="profile-item">
                <a href="../php/perfil.php"><img src="../assets/img/user-48.png" alt="Profile" class="profile-icon"></a>
            </li>
        </ul>
    </nav>
</header>

    <section id="home" class="carousel">
        <div class="carousel-container">
            <div class="carousel-item">
                <img src="../assets/img/group-people-working-out-business-plan-office.jpg" alt="Imagem 1">
                <div class="carousel-text">
                    <h2>ProLink: Networking Simplificado</h2>
                    <p>Conecte-se com profissionais ao redor do mundo!</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="../assets/img/group-people-working-out-business-plan-office2.jpg" alt="Imagem 2">
                <div class="carousel-text">
                    <h2>ProLink: Oportunidades Globais</h2>
                    <p>Encontre empregos e webinars em várias áreas.</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="../assets/img/view-from-group-young-professional-entrepreneurs-sitting-table-coworking-space-discussing-profits-last-team-project-using-laptop-digital-tablet-smartphone.jpg" alt="Imagem 3">
                <div class="carousel-text">
                    <h2>Junte-se à Comunidade ProLink</h2>
                    <p>Seja parte de uma rede global de profissionais.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="search" class="hero">
        <h1>Descubra. Aprenda. Aproveite</h1>
        <p>Plataforma para profissionais de todo o mundo</p>

        <div class="search-bar">
    <input type="text" id="searchInput" placeholder="Pesquisar por profissionais, áreas...">
    <button class="search-btn1" onclick="buscarProfissionais()">Procurar</button>
    </div>

    <script>
   function buscarProfissionais() {
    // Pega o valor do campo de busca e remove espaços extras
    const termoBusca = document.getElementById('searchInput').value.trim();
    
    // Verifica se o usuário digitou algo
    if (termoBusca !== "") {
        // Redireciona para a página de resultados com o termo de busca
        window.location.href = `listaProfissionais.php?search=${encodeURIComponent(termoBusca)}`;
    } else {
        // Se estiver vazio, mostra alerta e coloca o foco no campo
        alert("Por favor, digite um termo para buscar.");
        document.getElementById('searchInput').focus();
    }
}
</script>
    </section>

    <section id="webinars" class="webinars">
        <h2>Próximos Webinars</h2>
        <div class="webinar-container">
            <div class="webinar-description">
                <h3>Descubra Novos Conhecimentos no Próximo Webinar!</h3>
                <p>Participe do nosso próximo webinar sobre networking global e descubra como expandir suas conexões profissionais.</p>
                <a href="../php/pagina_webinar.php" class="webinar-link">Saiba mais</a>
                <img src="../assets/img/undraw_Graduation_re_gthn.png" alt="Imagem do Webinar">
            </div>
            <div class="webinar-image">
                <img src="../assets/img/webinar-animate.svg" width="600px" height="600px" alt="Imagem de Capa do Webinar">
            </div>
        </div>
    </section>
        
    <section id="job-opportunities" class="job-opportunities">
        <h2>Oportunidades de emprego</h2>
        <br><br>
            <div class="job-container">
                <div class="job-description">
                    <h3>Encontre a Oportunidade dos Seus Sonhos!</h3>
                    <p>Confira as vagas abertas em diversas áreas profissionais e conecte-se com empregadores ao redor do mundo.</p>
                    <a href="../php/paginaEmprego.php" class="job-link">Saiba mais</a>
                    <img src="../assets/img/undraw_Finance_re_gnv2.png" alt="Imagem Oportunidade de Trabalho">
                </div>
                <div class="job-image">
                    <img src="../assets/img/task-animate.svg" width="600px" height="600px"  alt="Imagem de Oportunidade de Trabalho">
                </div>
            </div>
    </section> 

    <section class="timeline">
        <div class="timeline-bg">
            <h2>Nosso Progresso</h2>
            <div class="timeline-container">
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <img src="../assets/img/lupa.svg" alt="Icone de busca">
                    </div>
                    <div class="timeline-content">
                        <h3>Conecte-se Facilmente</h3>
                        <p>Encontre profissionais e oportunidades em diversas áreas usando nossa avançada ferramenta de busca.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <img src="../assets/img/envelope.svg" alt="Icone de marcador">
                    </div>
                    <div class="timeline-content">
                        <h3>Salve e Organize</h3>
                        <p>Guarde suas pesquisas e organize suas oportunidades favoritas para acessar mais tarde.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <img src="../assets/img/book.svg" alt="Icone de leitura">
                    </div>
                    <div class="timeline-content">
                        <h3>Aprenda e Cresça</h3>
                        <p>Participe de webinars e eventos para adquirir novos conhecimentos e expandir sua rede.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <section class="faq-section">
        <div class="faq-container">
            <div class="faq-image">
                <img src="../assets/img/duvida.svg" alt="Imagem Perguntas Frequentes">
            </div>
            <div class="faq-content">
                <h2>Perguntas Frequentes</h2>
                <div class="faq-item">
                    <button class="faq-question">O que é o ProLink?</button>
                    <div class="faq-answer">
                        <p>O ProLink é uma plataforma de networking profissional que conecta pessoas com oportunidades de carreira e conhecimento.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question">Como posso encontrar profissionais?</button>
                    <div class="faq-answer">
                        <p>Você pode usar nossa ferramenta de busca para encontrar profissionais em diversas áreas de atuação.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <button class="faq-question">É necessário pagar?</button>
                    <div class="faq-answer">
                        <p>O acesso à plataforma ProLink possui tanto funcionalidades gratuitas quanto serviços pagos, dependendo da sua necessidade.</p>
                    </div>
                </div>
            </div>
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
        function logout() {
            // Redireciona para o arquivo de logout (que destruirá a sessão)
            window.location.href = '../php/logout.php';
        }

        // Script para menu responsivo
        document.addEventListener('DOMContentLoaded', function() {
            // Selecionando os elementos
            const mobileMenu = document.getElementById('mobile-menu');
            const closeMenu = document.getElementById('close-menu');
            const closeMenuOutside = document.getElementById('close-menu-outside');
            const menu = document.getElementById('menu');
            
            // Função para abrir o menu
            if (mobileMenu) {
                mobileMenu.addEventListener('click', function() {
                    menu.classList.add('active');
                    this.style.display = 'none';
                    closeMenuOutside.style.display = 'flex'; // Mostrar botão de fechar fora do menu
                });
            }
            
            // Função para fechar o menu (botão dentro do menu)
            if (closeMenu) {
                closeMenu.addEventListener('click', function() {
                    menu.classList.remove('active');
                    mobileMenu.style.display = 'block';
                    closeMenuOutside.style.display = 'none';
                });
            }
            
            // Função para fechar o menu (botão fora do menu)
            if (closeMenuOutside) {
                closeMenuOutside.addEventListener('click', function() {
                    menu.classList.remove('active');
                    mobileMenu.style.display = 'block';
                    this.style.display = 'none';
                });
            }
            
            // Fechar o menu ao clicar em um link
            const menuLinks = menu.querySelectorAll('a');
            menuLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        menu.classList.remove('active');
                        mobileMenu.style.display = 'block';
                        closeMenuOutside.style.display = 'none';
                    }
                });
            });
            
            // Ajustar visualização em redimensionamento
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) {
                    menu.classList.remove('active');
                    if (mobileMenu) mobileMenu.style.display = 'none';
                    closeMenuOutside.style.display = 'none';
                } else {
                    if (mobileMenu && !menu.classList.contains('active')) {
                        mobileMenu.style.display = 'block';
                    }
                }
            });
            
            // Inicialização - esconder botão mobile em telas grandes
            if (window.innerWidth >= 992) {
                if (mobileMenu) mobileMenu.style.display = 'none';
            }
        });
    </script>
</body>
</html>