<?php
session_start();

// Limpa todas as variáveis de sessão
/*session_unset();

// Destrói a sessão
session_destroy();*/
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProLink - Networking Platform</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
</head>
<body>
<header>
        <nav class="navbar">
            <div class="logo-container">
                <img src="./img/icons8-network-96.png" alt="Logo" class="logo-icon">
                <div class="logo">ProLink</div>
            </div>
            <ul class="menu">
                <li><a href="#home">Home</a></li>
                <li><a href="#webinars">Webinars</a></li>
                <li><a href="#job-opportunities">Oportunidades de Trabalho</a></li>
                <li><a href="#contato">Contato</a></li>
                <?php if (!isset($_SESSION['usuario_logado'])): ?>
                    <li><a href="login.html">Login</a></li>
                    <?php if (!isset($_SESSION['cadastro_realizado'])): ?>
                        <li><a href="cadastro.html" class="signup-btn">Cadastre-se</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><button class="logout-btn" onclick="logout()">Olá, <?php echo $_SESSION['nome_usuario']; ?></button></li>
                <?php endif; ?>
            </ul>
            <div class="profile">
                <a href="./perfil.html"><img src="./img/Perfil2.png" alt="Profile" class="profile-icon"></a>
                <a href="./chat.html"><img src="./img/chat-icon.svg" alt="Chat" class="chat-icon"></a>
            </div>
        </nav>
    </header>

    <section id="home" class="carousel">
        <div class="carousel-container">
            <div class="carousel-item">
                <img src="./img/group-people-working-out-business-plan-office.jpg" alt="Imagem 1">
                <div class="carousel-text">
                    <h2>ProLink: Networking Simplificado</h2>
                    <p>Conecte-se com profissionais ao redor do mundo!</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="./img/group-people-working-out-business-plan-office2.jpg" alt="Imagem 2">
                <div class="carousel-text">
                    <h2>ProLink: Oportunidades Globais</h2>
                    <p>Encontre empregos e webinars em várias áreas.</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="./img/view-from-group-young-professional-entrepreneurs-sitting-table-coworking-space-discussing-profits-last-team-project-using-laptop-digital-tablet-smartphone.jpg" alt="Imagem 3">
                <div class="carousel-text">
                    <h2>Junte-se à Comunidade ProLink</h2>
                    <p>Seja parte de uma rede global de profissionais.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="webinars" class="hero">
        <h1>Descubra. Aprenda. Aproveite</h1>
        <p>Plataforma para profissionais de todo o mundo</p>
        <div class="search-bar">
            <input type="text" placeholder="Pesquisar por profissionais, áreas...">
            <a href="./lista_profissionais.html"><button class="search-btn1">Procurar</button></a>
        </div>
    </section>

    <section id="webinars1" class="webinars">
        <h2>Próximos Webinars</h2>
        <div class="webinar-container">
            <div class="webinar-description">
                <h3>Descubra Novas Oportunidades no Próximo Webinar!</h3>
                <p>Participe do nosso próximo webinar sobre networking global e descubra como expandir suas conexões profissionais.</p>
                <a href="./pagina_webinar.html" class="webinar-link">Saiba mais</a>
                <img src="./img/undraw_Graduation_re_gthn.png" alt="Imagem do Webinar">
            </div>
            <div class="webinar-image">
                <img src="./img/webinar-animate.svg" width="600px" height="600px" alt="Imagem de Capa do Webinar">
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
                    <a href="./pagina_emprego.html" class="job-link">Saiba mais</a>
                    <img src="./img/undraw_Finance_re_gnv2.png" alt="Imagem Oportunidade de Trabalho">
                </div>
                <div class="job-image">
                    <img src="./img/task-animate.svg" width="600px" height="600px"  alt="Imagem de Oportunidade de Trabalho">
                </div>
            </div>
    </section> 

    <section class="timeline">
        <div class="timeline-bg">
            <h2>Nosso Progresso</h2>
            <div class="timeline-container">
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <img src="./img/lupa.svg" alt="Icone de busca">
                    </div>
                    <div class="timeline-content">
                        <h3>Conecte-se Facilmente</h3>
                        <p>Encontre profissionais e oportunidades em diversas áreas usando nossa avançada ferramenta de busca.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <img src="./img/envelope.svg" alt="Icone de marcador">
                    </div>
                    <div class="timeline-content">
                        <h3>Salve e Organize</h3>
                        <p>Guarde suas pesquisas e organize suas oportunidades favoritas para acessar mais tarde.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <img src="./img/book.svg" alt="Icone de leitura">
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
                <img src="./img/duvida.svg" alt="Imagem Perguntas Frequentes">
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
            <img src="./img/icons8-network-96.png" alt="Logo da Empresa" class="footer-logo">
            <p>&copy; 2024 ProLink. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="script.js"></script>
    <script>
        function logout() {
            // Redireciona para o arquivo de logout (que destruirá a sessão)
            window.location.href = 'logout.php';
        }
    </script>
</body>
</html>
