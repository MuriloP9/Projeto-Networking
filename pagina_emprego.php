<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProLink - Oportunidades</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(to bottom,  #050a37,  #0e1768);
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
            gap: 20px; /* Adiciona espaçamento entre os elementos */
        }

        .search-bar, .filter-dropdown {
            padding: 10px;
            font-size: 1em;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .search-bar {
            flex-grow: 2;
        }

        .search-btn {
            padding: 10px 20px;
            font-size: 1em;
            background-color: #00bcd4;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-btn:hover {
            background-color: #0097a7;
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
            color: #00bcd4;
            text-decoration: none;
            transition: color 0.3s;
        }

        .job-card .job-link:hover {
            color: #0097a7;
            text-decoration: underline;
        }

        /* Job Highlights Section */
        .job-highlights {
            padding: 40px;
            background-color: #ffffff;
        }

        .highlighted-job-listings {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .highlight {
            border: 2px solid #00bcd4;
        }

        /* Apply Section */
        .apply-section {
            padding: 40px;
            background-color: #f9f9f9;
        }

        .apply-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .apply-form .form-input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 1em;
        }

        .apply-btn {
            width: 100%;
            padding: 10px;
            font-size: 1em;
            background-color: #00bcd4;
            color: rgb(0, 0, 0);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .apply-btn:hover {
            background-color: #0097a7;
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
            background-color:#00bcd4;
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
            border: 1px solid #00bcd4;
            font-size: 1em;
        }

        .notifications-btn {
            width: 100%;
            padding: 10px;
            font-size: 1em;
            background-color: #00bcd4;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .notifications-btn:hover {
            background-color: #0097a7;
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
            .job-listings, .highlighted-job-listings {
                grid-template-columns: 1fr;
            }

            .search-filter-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-bar, .filter-dropdown, .search-btn {
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
                <img src="./img/globo-mundial.png" alt="Logo" class="logo-icon">
                <div class="logo">ProLink</div>
            </div>
            <ul class="menu">
                <li><a href="./index.php">Home</a></li>
                <li><a href="./pagina_webinar.php">Webinars</a></li>
                <li><a href="#contato">Contato</a></li>
                <?php if (!isset($_SESSION['usuario_logado'])): ?>
                <li><a href="login.html">Login</a></li>
                <?php endif; ?>
            </ul>
            <div class="profile">
                <a href="./perfil.html"><img src="./img/Perfil2.png" alt="Profile" class="profile-icon"></a> <!-- Sem âncora, link externo -->
                <a href="./chat.html"><img src="./img/chat-icon.svg" alt="Chat" class="chat-icon"></a> <!-- Novo ícone de chat -->
            </div>
        </nav>
    </header>
    <section id="job-opportunities" class="job-opportunities">
        <h2>Oportunidades de Emprego</h2>
    
        <!-- Barra de pesquisa e filtros -->
        <div class="search-filter-container">
            <form id="search-form" action="cadastroVagas.php" method="post" class="search-form">
                <input type="text" placeholder="Pesquisar vagas..." class="search-bar" name="search-keyword">
                <select name="location" class="filter-dropdown">
                    <option value="">Selecione a localização</option>
                    <option value="remote">Remoto</option>
                    <option value="sp">São Paulo</option>
                    <option value="rj">Rio de Janeiro</option>
                    <!-- Outras opções -->
                </select>
                <select name="job-type" class="filter-dropdown">
                    <option value="">Tipo de emprego</option>
                    <option value="full-time">Tempo Integral</option>
                    <option value="part-time">Meio Período</option>
                    <option value="internship">Estágio</option>
                    <!-- Outras opções -->
                </select>
                <button class="search-btn" type="submit">Procurar</button>
            </form>
        </div>
    
        <!-- Lista de oportunidades -->
        <div class="job-listings">
            <div class="job-card">
                <h3>Desenvolvedor Web - Empresa ABC</h3>
                <p>Vaga para desenvolvedor(a) full stack com experiência em React e Node.js.</p>
                <p>Localização: São Paulo</p>
                <p><a href="#" class="job-link">Ver mais detalhes</a></p>
            </div>
            <div class="job-card">
                <h3>Designer Gráfico - Empresa XYZ</h3>
                <p>Vaga para designer gráfico com experiência em Adobe Suite e design UX/UI.</p>
                <p>Localização: Remoto</p>
                <p><a href="#" class="job-link">Ver mais detalhes</a></p>
            </div>
        </div>
    </section>
    
            <!-- Mais ofertas de emprego -->
        </div>
    </section>
    
    <!-- Seção de Destaques -->
    <section id="job-highlights" class="job-highlights">
        <h2>Vagas em Destaque</h2>
        <div class="highlighted-job-listings">
            <div class="job-card highlight">
                <h3>Gerente de Projetos - Empresa DEF</h3>
                <p><strong>Localização:</strong> Rio de Janeiro, RJ</p>
                <p><strong>Data de Postagem:</strong> 09 de outubro, 2024</p>
                <p>
                    Estamos em busca de um Gerente de Projetos altamente qualificado para liderar equipes multidisciplinares e garantir a entrega bem-sucedida de projetos estratégicos. O candidato ideal deve possuir experiência comprovada na área, habilidades excepcionais de comunicação, e ser capaz de trabalhar sob pressão para cumprir prazos e metas.
                </p>
                <p>
                    Requisitos incluem graduação em Administração, Engenharia ou áreas relacionadas, certificação PMP será um diferencial. Oferecemos um pacote de benefícios atrativo, incluindo plano de saúde, bônus por performance e oportunidade de crescimento dentro da empresa.
                </p>
                <a href="./pagina_emprego.html" class="job-link">Saiba mais</a>
            </div>
            
            <div class="job-card highlight">
                <h3>Designer Gráfico - Empresa GHI</h3>
                <p><strong>Localização:</strong> Remoto</p>
                <p><strong>Data de Postagem:</strong> 07 de outubro, 2024</p>
                <p>
                    Procuramos um Designer Gráfico criativo e inovador para integrar nossa equipe remota. O profissional será responsável pela criação de materiais visuais impactantes, incluindo banners, posts para redes sociais, apresentações, e design de interfaces para aplicativos e websites. 
                </p>
                <p>
                    Requisitos incluem domínio de ferramentas como Adobe Photoshop, Illustrator e Figma, além de experiência prévia em design gráfico. Conhecimento em motion design será considerado um diferencial. Oferecemos flexibilidade de horário, ambiente colaborativo e oportunidade de crescimento em projetos desafiadores.
                </p>
                <a href="./pagina_emprego.html" class="job-link">Saiba mais</a>
            </div>
            
            <!-- Mais vagas em destaque -->
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
    
    <!-- Notificações de Vagas -->
    <section id="notifications-section" class="notifications-section">
        <h2>Notificações</h2>
        <p>Receba notificações de novas vagas compatíveis com seus interesses.</p>
        <form action="notificacoes.php" method="post" id="notifications-form" class="notifications-form">
            <label for="email-notifications">Email para notificações:</label>
            <input type="email" id="email-notifications" name="notification-email" placeholder="Seu email" required class="form-input">
            
            <label for="job-preferences">Áreas de interesse:</label>
            <select id="job-preferences" name="job-preferences[]" multiple class="form-input">
                <option value="development">Desenvolvimento</option>
                <option value="marketing">Marketing</option>
                <option value="design">Design</option>
                <option value="project-management">Gestão de Projetos</option>
                <!-- Outras opções -->
            </select>
            
            <button type="button" id="submit-notifications" class="notifications-btn">Receber Notificações</button>
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
            <img src="./img/icons8-network-96.png" alt="Logo da Empresa" class="footer-logo">
            <p>&copy; 2024 ProLink. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="script.js"></script>
</body>
</html>