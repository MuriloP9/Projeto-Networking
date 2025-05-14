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