//Container com imagens rodando
$(document).ready(function() {
    let img = 0;
    const itens = $('.carousel-item');
    const totalItens = itens.length;

    function alternarItens() {
        itens.css('opacity', '0'); // Esconde todas as imagens
        itens.eq(img).css('opacity', '1'); // Mostra a imagem atual
    }

    function proximoItem() {
        img = (img + 1) % totalItens;  // Alterna para o próximo índice ou volta ao 0
        alternarItens();
    }

    setInterval(proximoItem, 3000);  // Muda de imagem a cada 3 segundos
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
$(document).ready(function () {
    $('#submitBtn').click(function () {
        var nome = $('#nome').val();
        var email = $('#email').val();
        var senha = $('#senha').val();
        var dataNascimento = $('#dataNascimento').val();
        var telefone = $('#telefone').val();

      
        if (nome && email && senha && dataNascimento && telefone) {
            var formData = new FormData();
            formData.append('nome', nome);
            formData.append('email', email);
            formData.append('senha', senha);
            formData.append('dataNascimento', dataNascimento);
            formData.append('telefone', telefone);

           
            $.ajax({
                url: 'cadastro.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    // Redireciona diretamente para a página de sucesso
                    window.location.href = 'inclusaoCadastro.html';
                },
                error: function () {
                    alert('Erro ao processar o cadastro.'); // Mensagem de erro genérica
                }
            });
        } else {
            alert('Por favor, preencha todos os campos.'); // Mensagem de validação
        }
    });
});


//ajax da página de Vagas (oportunidades de emprego)
$(document).ready(function () {
    $('.search-btn').click(function (event) {
        event.preventDefault(); 

        var tituloVaga = $('.search-bar').val();
        var localizacao = $('select[name="location"]').val();
        var tipoEmprego = $('select[name="job-type"]').val();

       
        if (tituloVaga && tipoEmprego) {
            var formData = new FormData();
            formData.append('titulo_vaga', tituloVaga);
            formData.append('localizacao', localizacao);
            formData.append('tipo_emprego', tipoEmprego);

            // Envia os dados para o arquivo PHP de backend
            $.ajax({
                url: 'cadastroVagas.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    if (response.trim() === 'Vaga cadastrada com sucesso!') {
                        alert('Vaga cadastrada com sucesso!');
                    } else {
                        alert(response); //  mensagem de erro pelo PHP
                    }
                },
                error: function () {
                    alert('Erro ao cadastrar a vaga.');
                }
            });
        } else {
            alert('Por favor, preencha o título da vaga e o tipo de emprego.');
        }
    });
});


 // Verifica se o usuário está logado
$(document).ready(function() {
    function checkLoginStatus() {
        $.ajax({
            url: 'checkLoginStatus.php',
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
            url: 'logout.php',
            type: 'POST',
            success: function(response) {
                window.location.href = 'index.php';
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
