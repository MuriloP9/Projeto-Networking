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

        // Verifica se os campos obrigatórios estão preenchidos
        if (nome && email && senha && dataNascimento && telefone) {
            var formData = new FormData();
            formData.append('nome', nome);
            formData.append('email', email);
            formData.append('senha', senha);
            formData.append('dataNascimento', dataNascimento);
            formData.append('telefone', telefone);

            // Envia os dados para o cadastro.php
            $.ajax({
                url: 'cadastro.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    if (response.trim() === 'sucesso') {
                        window.location.href = 'inclusaoCadastro.html'; // Redireciona em caso de sucesso
                    } else {
                        alert(response); // Mostra mensagem de erro enviada pelo PHP
                    }
                },
                error: function () {
                    alert('Erro ao processar o cadastro.');
                }
            });
        } else {
            alert('Por favor, preencha todos os campos.');
        }
    });
});