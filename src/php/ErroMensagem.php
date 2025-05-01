<?php

class MensagemErro {

    function exibirMensagemErro($mensagem, $ambiente) {
        // Definir os caminhos base conforme o ambiente
        $basePath = ($ambiente == "restrito") ? "../" : "./";
        
        // HTML da mensagem de erro com estilização moderna
        die(<<<END
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Erro - ProLink</title>
            <link rel="icon" type="image/x-icon" href="{$basePath}src/imgs/icons/logo-ico.ico">
            <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
            <style>
                body {
                    margin: 0;
                    font-family: 'Montserrat', sans-serif;
                    background: #201b2c;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    color: #f0ffffde;
                }
                
                .error-container {
                    width: 90%;
                    max-width: 500px;
                    background: #2f2841;
                    padding: 40px;
                    border-radius: 20px;
                    box-shadow: 0px 10px 40px #00000056;
                    text-align: center;
                }
                
                .error-title {
                    color: #ff5555;
                    margin-bottom: 30px;
                    font-size: 1.8rem;
                }
                
                .error-icon {
                    width: 100px;
                    height: 100px;
                    margin: 0 auto 20px;
                }
                
                .error-message {
                    margin-bottom: 30px;
                    line-height: 1.6;
                }
                
                .error-image {
                    max-width: 100%;
                    height: auto;
                    margin-bottom: 20px;
                }
                
                .btn-return {
                    display: inline-block;
                    padding: 12px 24px;
                    background: hsl(187, 76%, 53%);
                    color: #2b124b;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: bold;
                    transition: all 0.3s ease;
                }
                
                .btn-return:hover {
                    transform: translateY(-2px);
                    box-shadow: 0px 5px 15px rgba(23, 209, 212, 0.4);
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1 class="error-title">ERRO</h1>
                <div class="error-icon">
                    <img src="{$basePath}src/img/icons/error.png" alt="Ícone de erro" class="error-image">
                </div>
                <p class="error-message">$mensagem</p>
                <a href="{$basePath}../php/index.php" class="btn-return">Voltar ao Início</a>
            </div>
        </body>
        </html>
        END);
    }
}
?>