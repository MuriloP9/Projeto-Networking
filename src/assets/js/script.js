//Container com imagens rodando
$(document).ready(function() {
    let img = 0;
    const itens = $('.carousel-item');
    const totalItens = itens.length;
    startCarousel();
    function startCarousel() {
        setInterval(proximoItem, 1800);
      }
    function alternarItens() {
        itens.css('opacity', '0'); // Esconde todas as imagens
        itens.eq(img).css('opacity', '1'); // Mostra a imagem atual
    }

    function proximoItem() {
        img = (img + 1) % totalItens;  // Alterna para o próximo índice ou volta ao 0
        alternarItens();
    }

});

// Seção de Dúvidas
$(document).ready(function() {
    $('.faq-question').on('click', function() {
        const itemDuvida = $(this).closest('.faq-item');
        const respostaDuvida = itemDuvida.find('.faq-answer');

        // Alterna a visibilidade da resposta com uma animação
        respostaDuvida.slideToggle(300); // 300ms para a animação
        itemDuvida.toggleClass('ativo'); // Alterna a classe 'ativo' no item
    });
});



//Ajax para o cadastro.php
$(document).ready(function() {
    $('#cadastroForm').on('submit', function(event) {
        event.preventDefault(); // Impede o envio padrão do formulário

        const nome = $('#nome').val();
        const email = $('#email').val();
        const senha = $('#senha').val();
        const dataNascimento = $('#dataNascimento').val();
        const telefone = $('#telefone').val();

        if (nome.trim() === '' || email.trim() === '' || senha.trim() === '' || dataNascimento.trim() === '' || telefone.trim() === '') {
            alert('Por favor, preencha todos os campos!');
            return;
        }

        $.ajax({
            url: '../php/cadastro.php',
            type: 'POST',
            data: {
                nome: nome,
                email: email,
                senha: senha,
                dataNascimento: dataNascimento,
                telefone: telefone
            },
            success: function(response) {
                alert('Cadastro realizado com sucesso!');
                window.location.href = 'inclusaoCadastro.html'; // Redireciona para a página de sucesso
            },
            error: function() {
                alert('Ocorreu um erro ao cadastrar!');
            }
        });
    });
});

 // Verifica se o usuário está logado
$(document).ready(function() {
    function checkLoginStatus() {
        $.ajax({
            url: '../php/checkLoginStatus.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.loggedIn) {
                    $('#loginBtn').hide();
                    $('#signupBtn').hide();
                    $('#userGreeting').show();
                    $('#userName').text(response.userName);
                } else {
                    $('#loginBtn').show();
                    $('#signupBtn').show();
                    $('#userGreeting').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição:', error);
            }
        });
    }

    // Verifica o status do login ao carregar a página
    checkLoginStatus();

    // Logout (opcional)
    $('#userGreeting').on('click', function() {
        $.ajax({
            url: '../php/logout.php',
            type: 'POST',
            success: function(response) {
                window.location.href = '../php/index.php';
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição:', error);
            }
        });
    });
});


document.addEventListener('DOMContentLoaded', function () {
    const mobileMenu = document.getElementById('mobile-menu');
    const closeMenu = document.getElementById('close-menu');
    const menu = document.getElementById('menu');

    mobileMenu.addEventListener('click', function () {
        menu.classList.toggle('active');
        mobileMenu.style.display = 'none'; // Esconde o botão do menu
    });

    closeMenu.addEventListener('click', function () {
        menu.classList.remove('active');
        mobileMenu.style.display = 'block'; // Mostra o botão de menu novamente
    });
});


$(document).ready(function () {
    const $menu = $('#menu');
    const $mobileMenu = $('#mobile-menu');
    const $closeMenu = $('#close-menu');
    const $menuLinks = $('.menu li a'); // Seleciona todos os links do menu

    // Abrir menu
    $mobileMenu.on('click', function () {
        $menu.addClass('active');
        $mobileMenu.hide(); // Esconde o botão ao abrir o menu
    });

    $closeMenu.on('click', function () {
        $menu.removeClass('active');

        // Só exibe o botão de menu novamente se a tela for menor que 1024px
        if ($(window).width() < 1024) {
            $mobileMenu.show();
        }
    });

    // Fechar menu ao clicar em qualquer item da lista
    $menuLinks.on('click', function () {
        $menu.removeClass('active');

        // Só exibe o botão de menu novamente se a tela for menor que 1024px
        if ($(window).width() < 1024) {
            $mobileMenu.show();
        }
    });
});


document.addEventListener('DOMContentLoaded', function() {
    // Controle do menu mobile
    const mobileMenu = document.getElementById('mobile-menu');
    const closeMenu = document.getElementById('close-menu');
    const menu = document.getElementById('menu');
    
    if (mobileMenu) {
        mobileMenu.addEventListener('click', function() {
            menu.classList.add('active');
        });
    }
    
    if (closeMenu) {
        closeMenu.addEventListener('click', function() {
            menu.classList.remove('active');
        });
    }
    
    // Fechar o menu ao clicar em um link
    const menuLinks = document.querySelectorAll('#menu a');
    menuLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                menu.classList.remove('active');
            }
        });
    });
    
    // Rotação automática do carousel
    let currentSlide = 0;
    const slides = document.querySelectorAll('.carousel-item');
    
    if (slides.length > 0) {
        // Função para mostrar o slide atual
        function showSlide(index) {
            // Esconde todos os slides
            slides.forEach(slide => {
                slide.style.opacity = 0;
                slide.style.zIndex = 0;
            });
            
            // Mostra o slide atual
            slides[index].style.opacity = 1;
            slides[index].style.zIndex = 1;
        }
        
        // Função para avançar para o próximo slide
        function nextSlide() {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        }
        
        // Inicializa o primeiro slide
        showSlide(currentSlide);
        
        // Configura a rotação automática
        setInterval(nextSlide, 5000); // Muda a cada 5 segundos
    }
    
    // FAQ acordeon
    const faqButtons = document.querySelectorAll('.faq-question');
    
    faqButtons.forEach(button => {
        button.addEventListener('click', function() {
            const faqItem = this.parentElement;
            const wasActive = faqItem.classList.contains('active');
            
            // Fecha todos os itens primeiro
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Se o item clicado não estava ativo, abre-o
            if (!wasActive) {
                faqItem.classList.add('active');
            }
        });
    });
    
    // Ajuste de altura do iframe do mapa para manter proporção em telas menores
    function adjustMapHeight() {
        const mapIframe = document.querySelector('.map-container iframe');
        if (mapIframe) {
            if (window.innerWidth <= 767) {
                const width = mapIframe.offsetWidth;
                mapIframe.style.height = (width * 0.75) + 'px';
            } else {
                mapIframe.style.height = '300px';
            }
        }
    }
    
    // Ajusta a altura do mapa inicialmente e quando a janela for redimensionada
    adjustMapHeight();
    window.addEventListener('resize', adjustMapHeight);
    
    // Efeito suave na rolagem para links internos
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                const headerOffset = 80;
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
});