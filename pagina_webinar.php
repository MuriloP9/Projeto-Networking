<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProLink - Webinar</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "Montserrat", sans-serif;
            background-color: #f5f5f5;
            margin: 0;
        }

        .container {
            display: flex;
            max-width: 1200px;
            background: linear-gradient(135deg, #4a90e2, #357ABD);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin: 20px auto;
        }

        .webinar-info {
            background: linear-gradient(#3c4b9e,#697df0) ;
            padding: 30px;
            flex: 1;
            color: #fff;
        }

        .webinar-details h1 {
            font-size: 2.5em;
            margin: 20px 0;
            line-height: 1.2;
        }

        .webinar-details p {
            margin: 10px 0;
        }

        .webinar-meta {
            margin: 20px 0;
        }
        .webinar-description{
           background:linear-gradient(#3c4b9e,#9ca6df);
            color: #ffffff;
        }

        .tutor-info {
            display: flex;
            align-items: center;
            margin-top: 30px;
        }

        .tutor-photo {
            border-radius: 50%;
            width: 60px;
            height: 60px;
            margin-right: 15px;
        }

        .registration-form {
            background:linear-gradient(rgb(97, 94, 94), rgb(156, 150, 150)) ;
            color: #fff;
            padding: 30px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .registration-form h2 {
            color: #6f84f7;
            margin-bottom: 15px;
        }

        .registration-form form {
            display: flex;
            flex-direction: column;
        }

        .registration-form input,
        .registration-form button {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border: none;
            outline: none;
            font-size: 1em;
        }

        .registration-form input {
            background-color: #fff;
            color: #333;
        }

        .registration-form button {
            background-color: #3c4b9e;
            color: #fff;
            cursor: pointer;
            transition: background 0.5s;
        }

        .registration-form button:hover {
            background-color: #7186ff;
        }

        .registration-form label {
            font-size: 0.9em;
            margin: 10px 0;
        }

        .importance-section, .live-webinar-section, .calendar-section {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .importance-section h2, .live-webinar-section h2, .calendar-section h2 {
            font-size: 2em;
            color: #333;
            margin-bottom: 15px;
        }

        .importance-section p {
            font-size: 1.1em;
            color: #555;
            line-height: 1.6;
        }

        .live-webinar-section iframe {
            width: 100%;
            height: 400px;
            border: none;
            border-radius: 10px;
        }

        .calendar-table {
            width: 100%;
            border-collapse: collapse;
        }

        .calendar-table th, .calendar-table td {
            padding: 15px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .calendar-table th {
            background-color: #357ABD;
            color: #fff;
        }

        .calendar-table td {
            background-color: #f9f9f9;
            color: #666; 
        }

        .calendar-table tr:nth-child(even) td {
            background-color: #e3f2fd;
        }

        .calendar-table a {
            text-decoration: none;
            color: #fff;
        }

        .webinar-date {
            font-weight: bold;
            color: #357ABD;
        }

        .inscreva-se-btn {
            background-color: #4a90e2;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            transition: background-color 0.3s;
            display: inline-block;
            font-weight: bold;
        }

        .inscreva-se-btn:hover {
            background-color: #357ABD;
        }

        /* Novas Seções Adicionadas */
        .testimonials-section {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .testimonials-section h2 {
            font-size: 2em;
            color: #333;
            margin-bottom: 15px;
        }

        .testimonial {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .testimonial img {
            border-radius: 50%;
            width: 60px;
            height: 60px;
            margin-right: 15px;
        }

        .testimonial p {
            font-size: 1.1em;
            color: #555;
            line-height: 1.6;
        }

        .faq-section {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .faq-section h2 {
            font-size: 2em;
            color: #333;
            margin-bottom: 15px;
        }

        .faq-item {
            margin-bottom: 15px;
        }

        .faq-item h3 {
            font-size: 1.2em;
            color: #357ABD;
            margin-bottom: 10px;
        }

        .faq-item p {
            font-size: 1em;
            color: #555;
            line-height: 1.6;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .webinar-info, .registration-form {
                padding: 20px;
            }

            .webinar-details h1 {
                font-size: 2em;
            }

            .registration-form h2 {
                font-size: 1.5em;
            }

            .live-webinar-section iframe {
                height: 300px;
            }

            .testimonial {
                flex-direction: column;
                text-align: center;
            }

            .testimonial img {
                margin-bottom: 10px;
            }
        }

        @media (max-width: 480px) {
            .webinar-details h1 {
                font-size: 1.5em;
            }

            .registration-form h2 {
                font-size: 1.2em;
            }

            .live-webinar-section iframe {
                height: 200px;
            }

            .calendar-table th, .calendar-table td {
                padding: 10px;
            }

            .inscreva-se-btn {
                padding: 8px 16px;
                font-size: 0.9em;
            }
        }

    /* Estilização da seção FAQ */
.faq-section {
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.faq-section h2 {
    font-size: 28px;
    color: #007bff;
    margin-bottom: 20px;
}

.faq-item {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease-in-out;
}

.faq-item:hover {
    background: #eef7ff;
}

.faq-question {
    font-size: 18px;
    color: #007bff;
    cursor: pointer;
    margin: 0;
}

.faq-answer {
    font-size: 16px;
    color: #333;
    display: none;
    padding-top: 10px;
}

/* Efeito de expansão */
.faq-item.active .faq-answer {
    display: block;
}

    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo-container">
                <img src="img/globo-mundial.png" alt="Logo" class="logo-icon">
                <div class="logo">ProLink</div>
            </div>
            <ul class="menu">
                <li><a href="./index.php">Home</a></li>
                <li><a href="./pagina_emprego.php">Oportunidades de Trabalho</a></li>
                <?php if (!isset($_SESSION['usuario_logado'])): ?>
                <li><a href="login.html">Login</a></li>
                <?php endif; ?>
            </ul>
            <div class="profile">
                <a href="./perfil.php"><img src="./img/user-48.png" alt="Profile" class="profile-icon"></a>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="webinar-info">
            <div class="webinar-details">
                <h1>O Melhor Webinar Anual <br> de UX Design</h1>
                <p class="webinar-description">
                    Conferência de três dias focada em UX Design e Inovação. Este evento é simples, mas incrivelmente inspirador.
                </p>
                <div class="webinar-meta">
                    <p><strong>Duração:</strong> 2 Horas e 30 Minutos</p>
                    <p><strong>Data da Conferência:</strong> 14.10.24, 21:00</p>
                </div>
                <div class="tutor-info">
                    <img src="img/jonh.jpg" alt="Instrutor Principal" class="tutor-photo">
                    <div class="tutor-details">
                        <p class="tutor-name">John Jonson</p>
                        <p class="tutor-title">Editor Chefe Web MGN</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="registration-form">
            <h2>Não Perca Tempo! <br> Participe do Melhor Webinar</h2>
            <form action="processa_inscricao.php" method="POST">
                <input type="text" placeholder="Nome Completo" name="name" required>
                <input type="email" placeholder="Webinar@exemplo.com" name="email" required>
                <input type="tel" placeholder="+55(11) 5555-5555" name="phone">
                <label>
                    <input type="checkbox" name="subscribe">
                    Eu concordo em receber notificações sobre a Conferência e eventos
                    <br>
                    <input type="checkbox" id="lgpd-consent" name="lgpd-consent" required>
                    Eu concordo que os meus dados pessoais fornecidos sejam armazenados e tratados pela ProLink, conforme descrito na 
                    <a href="politica-de-privacidade.html" target="_blank">Política de Privacidade</a>, e estou ciente de que posso solicitar a exclusão dos meus dados a qualquer momento.
                </label>
                <button type="submit">Inscreva-se Agora</button>
            </form>
        </div>
    </div>

    <!-- Seção explicando a importância de um webinar -->
    <section class="importance-section">
        <h2>Importância dos Webinars</h2>
        <p>
            Webinars são uma excelente forma de compartilhar conhecimento e se conectar com pessoas ao redor do mundo. 
            Eles permitem que especialistas e empresas apresentem conteúdos relevantes, ensinem novas habilidades e interajam 
            diretamente com o público, criando oportunidades para aprendizado, networking e engajamento em tempo real.
        </p>
    </section>

    <!-- Seção de live de webinar com iframe -->
    <section class="live-webinar-section">
        <h2>Assista a Um Webinar ao Vivo</h2>
        <iframe width="560" height="315" src="https://www.youtube.com/embed/iijBEfyhtCo?si=zV4vT_kvRnA_GkJB" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
    </section>

    <!-- Calendário de próximos webinars -->
    <section class="calendar-section">
        <h2>Próximos Webinars</h2>
        <table class="calendar-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Título</th>
                    <th>Horário</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>14 de Outubro, 2024</td>
                    <td>UX Design</td>
                    <td>14:00 - 15:30</td>
                    <td><a href="#" class="inscreva-se-btn">Inscreva-se</a></td>
                </tr>
                <tr>
                    <td>22 de Outubro, 2024</td>
                    <td>Desenvolvimento de Aplicações Web</td>
                    <td>16:00 - 17:30</td>
                    <td><a href="#" class="inscreva-se-btn">Inscreva-se</a></td>
                </tr>
                <tr>
                    <td>30 de Outubro, 2024</td>
                    <td>Como Criar Conteúdo Engajador</td>
                    <td>18:00 - 19:30</td>
                    <td><a href="#" class="inscreva-se-btn">Inscreva-se</a></td>
                </tr>
            </tbody>
        </table>
    </section>

    <!-- Nova Seção: Depoimentos -->
    <section class="testimonials-section">
        <h2>Depoimentos</h2>
        <div class="testimonial">
            <img src="img/user1.jpg" alt="Usuário 1">
            <p>"O webinar foi incrível! Aprendi muito sobre UX Design e pôr em prática imediatamente."</p>
        </div>
        <div class="testimonial">
            <img src="img/user2.jpg" alt="Usuário 2">
            <p>"A ProLink sempre traz conteúdos de alta qualidade. Recomendo a todos!"</p>
        </div>
    </section>

    <!-- Nova Seção: Perguntas Frequentes -->
<section class="faq-section">
    <h2>Perguntas Frequentes</h2>
    <div class="faq-item">
        <h3 class="faq-question">Como posso participar do webinar?</h3>
        <p class="faq-answer">Basta se inscrever no formulário acima e aguardar o link de acesso por e-mail.</p>
    </div>
    <div class="faq-item">
        <h3 class="faq-question">O webinar é gratuito?</h3>
        <p class="faq-answer">Sim, todos os webinars da ProLink são gratuitos.</p>
    </div>
    <div class="faq-item">
        <h3 class="faq-question">Posso assistir ao webinar depois?</h3>
        <p class="faq-answer">Sim, disponibilizamos a gravação do webinar para todos os inscritos.</p>
    </div>
</section>


    <footer class="footer-section">
        <div class="footer-content">
            <img src="./img/globo-mundial.png" alt="Logo da Empresa" class="footer-logo">
            <p>&copy; 2024 ProLink. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="script.js"></script>
    <script>
        // Script para expandir respostas ao clicar na pergunta
document.querySelectorAll(".faq-question").forEach(item => {
    item.addEventListener("click", () => {
        item.parentElement.classList.toggle("active");
    });
});

    </script>
</body>
</html>