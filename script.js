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
$(document).ready(function() {
    function verificarCredenciais() {
        const usuario = $('input[name="usuario"]').val();
        const senha = $('input[name="senha"]').val();
        
        if (usuario === "admin" && senha === "1234") {
            alert("Inscrito!");
            window.location.href = "perfil.html"; // Redireciona para a página de perfil
        } else {
            alert("Não inscrito!");
        }
    }

    $('#btn-inscrever').on('click', verificarCredenciais);
});
