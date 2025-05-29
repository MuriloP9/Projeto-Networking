$(document).ready(function () {
    // Proteção contra alteração de campos pelo F12
    const originalFormHTML = document.getElementById('form-login').innerHTML;
    
    // Verificar se campos obrigatórios foram alterados
    function verificarIntegridade() {
        const email = document.getElementById('email');
        const senha = document.getElementById('senha');
        
        // Verificar se os tipos dos inputs foram alterados
        if (email.type !== 'email' || senha.type !== 'password') {
            alert('Tentativa de alteração detectada! A página será recarregada.');
            location.reload();
            return false;
        }
        
        // Verificar se required foi removido
        if (!email.required || !senha.required) {
            alert('Tentativa de alteração detectada! A página será recarregada.');
            location.reload();
            return false;
        }
        
        // Verificar se name foi alterado
        if (email.name !== 'email' || senha.name !== 'senha') {
            alert('Tentativa de alteração detectada! A página será recarregada.');
            location.reload();
            return false;
        }
        
        return true;
    }
    
    function realizarLogin() {
        // Verificar integridade antes de prosseguir
        if (!verificarIntegridade()) {
            return;
        }
        
        const email = $('#email').val();
        const senha = $('#senha').val();

        if (email.trim() === '' || senha.trim() === '') {
            $('#mensagem').text('Por favor, preencha todos os campos!');
            return;
        }

        // Validação de email no frontend
        if (!validateEmail(email)) {
            $('#mensagem').text('Por favor, insira um email válido!');
            return;
        }

        // Validação adicional de segurança
        if (email.length > 100) {
            $('#mensagem').text('Email muito longo!');
            return;
        }

        if (senha.length > 255 || senha.length < 1) {
            $('#mensagem').text('Senha inválida!');
            return;
        }

        $('#btnLogin').prop('disabled', true).text('Conectando...'); // Desabilita botão e muda texto

        $.ajax({
            url: '../php/validarLogin.php',
            type: 'POST',
            data: { email: email, senha: senha },
            dataType: 'json',
            success: function (response) {
                if (response.sucesso) {
                    $('#mensagem').css('color', 'green').text('Login realizado com sucesso! Redirecionando...');
                    setTimeout(function() {
                        window.location.href = '../php/index.php';
                    }, 1500);
                } else {
                    $('#mensagem').css('color', 'red').text(response.mensagem);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição:', error);
                $('#mensagem').css('color', 'red').text('Erro na comunicação com o servidor.');
            },
            complete: function () {
                // Reabilita o botão depois da requisição, independentemente do resultado
                setTimeout(function() {
                    $('#btnLogin').prop('disabled', false).text('Login');
                }, 2000);
            }
        });
    }

    // Função para validar o formato do email
    function validateEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    // Clique no botão
    $('#btnLogin').on('click', function () {
        realizarLogin();
    });

    // Pressionar Enter no input
    $('#form-login input').on('keypress', function (e) {
        if (e.which === 13) { // Tecla Enter
            realizarLogin();
        }
    });
    
    // Monitorar mudanças no DOM
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes') {
                const target = mutation.target;
                if (target.id === 'email' || target.id === 'senha') {
                    if (!verificarIntegridade()) {
                        return;
                    }
                }
            }
        });
    });
    
    // Observar mudanças nos campos
    if (document.getElementById('email') && document.getElementById('senha')) {
        observer.observe(document.getElementById('email'), { attributes: true });
        observer.observe(document.getElementById('senha'), { attributes: true });
    }
    
    // Verificar periodicamente
    setInterval(verificarIntegridade, 3000);
    
    // Proteção adicional contra console
    let devtools = {open: false, orientation: null};
    const threshold = 160;
    
    // Detectar se as ferramentas de desenvolvedor estão abertas
    setInterval(function() {
        if (window.outerHeight - window.innerHeight > threshold || 
            window.outerWidth - window.innerWidth > threshold) {
            if (!devtools.open) {
                devtools.open = true;
                console.clear();
                console.warn('🚨 Acesso restrito detectado!');
            }
        } else {
            devtools.open = false;
        }
    }, 500);
});