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
    <style>
        /* Estilos para o Modal de Login */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 5% auto;
            padding: 0;
            border: none;
            border-radius: 20px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            position: relative;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background: rgba(255,255,255,0.1);
            padding: 20px 30px;
            border-radius: 20px 20px 0 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            position: relative;
        }

        .modal-header h2 {
            color: white;
            margin: 0;
            text-align: center;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 1.8rem;
        }

        .close {
            color: white;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .close:hover,
        .close:focus {
            opacity: 0.7;
        }

        .modal-body {
            padding: 30px;
            background: white;
            border-radius: 0 0 20px 20px;
        }

        .modal-textfield {
            margin-bottom: 20px;
            position: relative;
        }

        .modal-textfield label {
            display: block;
            margin-bottom: 8px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            color: #333;
            font-size: 0.95rem;
        }

        .modal-textfield input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Montserrat', sans-serif;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .modal-textfield input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .modal-btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .modal-btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .modal-btn-login:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .modal-links {
            color: #010101ff;
            text-align: center;
            margin-top: 20px;
        }

        .modal-links a {
            color: #667eea;
            text-decoration: none;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .modal-links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .modal-mensagem {
            margin-top: 15px;
            padding: 10px;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
            text-align: center;
        }

        .modal-mensagem.error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .modal-mensagem.success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
            
            .modal-body {
                padding: 20px;
            }
            
            .modal-header h2 {
                font-size: 1.5rem;
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
        
        <!-- Botão de menu hambúrguer (só aparece em mobile) -->
        <div class="menu-toggle" id="mobile-menu">
            <img src="../assets/img/icons8-menu-48.png" alt="Menu" class="menu-icon">
        </div>
        
        <!-- Menu de navegação -->
        <ul class="menu" id="menu">
            <li><a href="#home">Home</a></li>
            <li><a href="#webinars">Webinars</a></li>
            <li><a href="#job-opportunities">Oportunidades</a></li>
            <li><a href="#contato">Contato</a></li>
            <?php if (!isset($_SESSION['usuario_logado'])): ?>
            <?php else: ?>
               <li><button class="logout-btn" onclick="logout()">Olá, <?php echo explode(' ', $_SESSION['nome_usuario'])[0]; ?></button></li>
            <?php endif; ?>
            <li class="profile-item">
                <a href="#" onclick="handleProfileClick(event)"><img src="../assets/img/user-48.png" alt="Profile" class="profile-icon"></a>
            </li>
        </ul>
    </nav>
</header>

<!-- Modal de Login -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Login</h2>
            <span class="close" onclick="closeLoginModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="form-modal-login">
                <div class="modal-textfield">
                    <label for="modal-email">Email</label>
                    <input type="email" id="modal-email" name="email" placeholder="Digite seu Email" required maxlength="100">
                </div>
                <div class="modal-textfield">
                    <label for="modal-senha">Senha</label>
                    <input type="password" id="modal-senha" name="senha" placeholder="Digite sua Senha" required maxlength="255">
                </div>
                <button type="button" class="modal-btn-login" id="btnModalLogin">Login</button>
            </form>
            
            <div class="modal-links">
                <a href="../php/esqueci-minha-senha.php">Esqueceu sua senha?</a>
                <br><br>
                <p style="font-size: 0.9rem;">Não tem uma conta? <a href="../pages/cadastro2.html">Cadastre-se agora</a></p>
            </div>
            
            <div id="modal-mensagem" class="modal-mensagem" style="display: none;"></div>
        </div>
    </div>
</div>

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
    <input type="text" id="searchInput" placeholder="Pesquisar por profissionais, áreas..." 
           maxlength="100" pattern="[^\x00-\x1F\x7F]+" title="Não use caracteres de controle">
    <button class="search-btn1" id="searchButton">Procurar</button>
</div>

    <script>
// Espera o DOM carregar completamente
document.addEventListener('DOMContentLoaded', function() {
    // Adiciona event listener ao botão
    document.getElementById('searchButton').addEventListener('click', buscarProfissionais);
    
    // Adiciona event listener para tecla Enter
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            buscarProfissionais();
        }
    });
});

function buscarProfissionais() {
    // Pega o valor do campo de busca
    const inputElement = document.getElementById('searchInput');
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
        return;
    }
    
    // Codifica o termo para URL (previne XSS e injection na URL)
    const termoCodificado = encodeURIComponent(termoBusca)
        .replace(/%20/g, '+') // Espaços como +
        .replace(/[!'()*]/g, function(c) {
            return '%' + c.charCodeAt(0).toString(16);
        });
    
    // Redireciona de forma segura
    window.location.href = `listaProfissionais.php?search=${termoCodificado}`;
}
</script>
   <section id="webinars" class="webinars">
        <h2>Próximos Webinars</h2>
        <div class="webinar-container">
            <div class="webinar-description">
                <h3>🚀 Transforme Sua Carreira com Networking Global!</h3>
                <p>Junte-se ao nosso webinar exclusivo e aprenda estratégias comprovadas para expandir suas conexões profissionais internacionalmente. Descubra como profissionais de sucesso constroem redes poderosas.</p>
                <a href="../php/pagina_webinar.php" class="webinar-link">
                    Garantir Minha Vaga
                </a>
                
                <div class="stats-cards">
                    <div class="stat-card">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Participantes</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">95%</span>
                        <span class="stat-label">Satisfação</span>
                    </div>
                </div>
            </div>
            <div class="webinar-iframe-container">
                <div class="iframe-wrapper">
                    <div class="iframe-label">🎯 Webinar ao Vivo</div>
                    <iframe width="1217" height="480" src="https://www.youtube.com/embed/aOj1rqxhHKI" title="Webinars" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </section>

    <!-- Seção de Oportunidades de Emprego -->
    <section id="job-opportunities" class="job-opportunities">
        <h2>Oportunidades de Emprego</h2>
        <div class="job-container">
            <div class="job-iframe-container">
                <div class="iframe-wrapper">
                    <div class="iframe-label">💼 Encontre Vagas!</div>
                    <iframe width="1217" height="480" src="https://www.youtube.com/embed/t4oM9Tw6eDw" title="Palestra - Sem experiência profissional, como conseguir um emprego?" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                </div>
            </div>
            <div class="job-description">
                <h3>💼 Sua Próxima Oportunidade Te Espera!</h3>
                <p>Explore milhares de vagas em empresas globais e conecte-se diretamente com recrutadores. Nossa plataforma facilita o match perfeito entre seu perfil e as melhores oportunidades do mercado.</p>
                <a href="../php/paginaEmprego.php" class="job-link">
                    Explorar Vagas
                </a>
                
                <div class="stats-cards">
                    <div class="stat-card">
                        <span class="stat-number">2.5k+</span>
                        <span class="stat-label">Vagas Ativas</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">85%</span>
                        <span class="stat-label">Taxa de Match</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call-to-Action Central -->
    <section class="cta-section">
        <div class="cta-content">
            <h2>Pronto para Impulsionar Sua Carreira?</h2>
            <p>Junte-se a milhares de profissionais que já transformaram suas trajetórias conosco!</p>
            <div class="cta-buttons">
                <a href="../php/pagina_webinar.php" class="cta-button primary">
                    🎯 Participar do Webinar
                </a>
                <a href="../php/paginaEmprego.php" class="cta-button">
                    💼 Ver Oportunidades
                </a>
            </div>
        </div>
    </section>

    <!-- Timeline aprimorada -->
    <section class="timeline">
        <h2>Como Funciona Nossa Plataforma</h2>
        <div class="timeline-container">
            <div class="timeline-item">
                <div class="timeline-icon">
                    <img src="../assets/img/lupa.svg" alt="Buscar">
                </div>
                <div class="timeline-content">
                    <h3>🔍 Conecte-se Facilmente</h3>
                    <p>Use nossa plataforma avançada para encontrar exatamente o que procura: profissionais, mentores ou oportunidades em sua área.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-icon">
                    <img src="../assets/img/envelope.svg" alt="Organizar">
                </div>
                <div class="timeline-content">
                    <h3>📌 Salve e Organize</h3>
                    <p>Salve contatos importantes no nosso aplicativo e organize suas oportunidades favoritas em um só lugar.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-icon">
                    <img src="../assets/img/book.svg" alt="Aprender">
                </div>
                <div class="timeline-content">
                    <h3>📚 Aprenda e Cresça</h3>
                    <p>Participe de webinars exclusivos, eventos de networking e mentorias para acelerar seu crescimento profissional.</p>
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
        // Função para lidar com o clique no perfil
        function handleProfileClick(event) {
            event.preventDefault();
            
            <?php if (!isset($_SESSION['usuario_logado'])): ?>
                // Se não estiver logado, abre o modal
                openLoginModal();
            <?php else: ?>
                // Se estiver logado, vai para o perfil
                window.location.href = '../php/perfil.php';
            <?php endif; ?>
        }

        // Função para abrir o modal de login
        function openLoginModal() {
            document.getElementById('loginModal').style.display = 'block';
            document.getElementById('modal-email').focus();
        }

         // Verificar se deve abrir o modal automaticamente
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('openLoginModal') === 'true') {
                openLoginModal();
                
                // Limpar o parâmetro da URL sem recarregar a página
                const url = new URL(window.location);
                url.searchParams.delete('openLoginModal');
                window.history.replaceState({}, '', url);
        }
          });

        // Função para fechar o modal de login
        function closeLoginModal() {
            document.getElementById('loginModal').style.display = 'none';
            clearLoginForm();
        }

        // Função para limpar o formulário
        function clearLoginForm() {
            document.getElementById('form-modal-login').reset();
            const mensagem = document.getElementById('modal-mensagem');
            mensagem.style.display = 'none';
            mensagem.className = 'modal-mensagem';
        }

        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('loginModal');
            if (event.target == modal) {
                closeLoginModal();
            }
        }

        // Event listener para o botão de login do modal
        document.addEventListener('DOMContentLoaded', function() {
            const btnModalLogin = document.getElementById('btnModalLogin');
            const formModalLogin = document.getElementById('form-modal-login');
            
            // Event listener para o botão
            btnModalLogin.addEventListener('click', function() {
                realizarLoginModal();
            });
            
            // Event listener para Enter nos campos
            formModalLogin.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    realizarLoginModal();
                }
            });
        });

        // Função para realizar o login via modal
        function realizarLoginModal() {
            const email = document.getElementById('modal-email').value.trim();
            const senha = document.getElementById('modal-senha').value;
            const btnLogin = document.getElementById('btnModalLogin');
            const mensagem = document.getElementById('modal-mensagem');
            
            // Validações do lado do cliente
            if (!email || !senha) {
                mostrarMensagemModal('Por favor, preencha todos os campos.', 'error');
                return;
            }
            
            if (!isValidEmail(email)) {
                mostrarMensagemModal('Por favor, digite um email válido.', 'error');
                return;
            }
            
            // Desabilitar botão e mostrar loading
            btnLogin.disabled = true;
            btnLogin.innerHTML = '<span class="loading"></span>Entrando...';
            
            // Fazer requisição AJAX
            const formData = new FormData();
            formData.append('email', email);
            formData.append('senha', senha);
            
            fetch('../php/validarLogin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    mostrarMensagemModal(data.mensagem, 'success');
                    setTimeout(() => {
                        // Recarrega a página para atualizar a sessão
                        window.location.reload();
                    }, 1000);
                } else {
                    mostrarMensagemModal(data.mensagem, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarMensagemModal('Erro de conexão. Tente novamente.', 'error');
            })
            .finally(() => {
                // Reabilitar botão
                btnLogin.disabled = false;
                btnLogin.innerHTML = 'Login';
            });
        }
        
        // Função para mostrar mensagens no modal
        function mostrarMensagemModal(texto, tipo) {
            const mensagem = document.getElementById('modal-mensagem');
            mensagem.textContent = texto;
            mensagem.className = `modal-mensagem ${tipo}`;
            mensagem.style.display = 'block';
            
            // Auto-ocultar mensagem de sucesso
            if (tipo === 'success') {
                setTimeout(() => {
                    mensagem.style.display = 'none';
                }, 3000);
            }
        }
        
        // Função para validar email
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

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
                    if (closeMenuOutside) {
                        closeMenuOutside.style.display = 'flex'; // Mostrar botão de fechar fora do menu
                    }
                });
            }
            
            // Função para fechar o menu (botão dentro do menu)
            if (closeMenu) {
                closeMenu.addEventListener('click', function() {
                    menu.classList.remove('active');
                    mobileMenu.style.display = 'block';
                    if (closeMenuOutside) {
                        closeMenuOutside.style.display = 'none';
                    }
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
                        if (closeMenuOutside) {
                            closeMenuOutside.style.display = 'none';
                        }
                    }
                });
            });
            
            // Ajustar visualização em redimensionamento
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) {
                    menu.classList.remove('active');
                    if (mobileMenu) mobileMenu.style.display = 'none';
                    if (closeMenuOutside) closeMenuOutside.style.display = 'none';
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