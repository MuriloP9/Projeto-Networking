<?php
session_start();
include("conexao.php");
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: ../pages/login.html");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

try {
    $pdo = conectar();

    // Busca os dados do usuário
    $sql = $pdo->prepare("
        SELECT u.nome, u.email, u.dataNascimento, u.telefone, 
               COALESCE(p.idade, NULL) as idade, 
               COALESCE(p.endereco, 'Não informado') as endereco, 
               COALESCE(p.formacao, 'Não informado') as formacao, 
               COALESCE(p.experiencia_profissional, 'Nenhuma informação') as experiencia_profissional, 
               COALESCE(p.interesses, 'Nenhuma informação') as interesses, 
               COALESCE(p.projetos_especializacoes, 'Nenhuma informação') as projetos_especializacoes, 
               COALESCE(p.habilidades, 'Nenhuma informação') as habilidades
        FROM Usuario u
        LEFT JOIN Perfil p ON u.id_usuario = p.id_usuario
        WHERE u.id_usuario = :id_usuario
    ");
    $sql->bindValue(":id_usuario", $id_usuario);
    $sql->execute();
    $usuario = $sql->fetch(PDO::FETCH_ASSOC);

    // HTML do PDF (sem imagem de perfil e sem logo)
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Perfil de ' . htmlspecialchars($usuario['nome']) . '</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; padding: 20px; }
            .header { margin-bottom: 30px; border-bottom: 2px solid #2c3e50; padding-bottom: 20px; }
            .nome-usuario { font-size: 24px; font-weight: bold; color: #2c3e50; margin: 0; }
            .section { margin-bottom: 20px; }
            .section h2 { color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 5px; }
            .info-item { margin-bottom: 10px; }
            .info-item strong { display: inline-block; width: 180px; }
            .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1 class="nome-usuario">' . htmlspecialchars($usuario['nome']) . '</h1>
            <p>Perfil profissional</p>
        </div>
        
        <div class="section">
            <h2>Informações Pessoais</h2>
            <div class="info-item"><strong>Nome:</strong> ' . htmlspecialchars($usuario['nome']) . '</div>
            ' . ($usuario['idade'] ? '<div class="info-item"><strong>Idade:</strong> ' . htmlspecialchars($usuario['idade']) . '</div>' : '') . '
            <div class="info-item"><strong>Endereço:</strong> ' . htmlspecialchars($usuario['endereco']) . '</div>
            <div class="info-item"><strong>Data de Nascimento:</strong> ' . htmlspecialchars($usuario['dataNascimento']) . '</div>
            <div class="info-item"><strong>Telefone:</strong> ' . htmlspecialchars($usuario['telefone']) . '</div>
            <div class="info-item"><strong>E-mail:</strong> ' . htmlspecialchars($usuario['email']) . '</div>
        </div>
        
        <div class="section">
            <h2>Formação Acadêmica</h2>
            <p>' . nl2br(htmlspecialchars($usuario['formacao'])) . '</p>
        </div>
        
        <div class="section">
            <h2>Experiência Profissional</h2>
            <p>' . nl2br(htmlspecialchars($usuario['experiencia_profissional'])) . '</p>
        </div>
        
        <div class="section">
            <h2>Habilidades</h2>
            <p>' . nl2br(htmlspecialchars($usuario['habilidades'])) . '</p>
        </div>
        
        <div class="section">
            <h2>Projetos e Especializações</h2>
            <p>' . nl2br(htmlspecialchars($usuario['projetos_especializacoes'])) . '</p>
        </div>
        
        <div class="section">
            <h2>Interesses</h2>
            <p>' . nl2br(htmlspecialchars($usuario['interesses'])) . '</p>
        </div>
        
        <div class="footer">
            <p>Gerado em ' . date('d/m/Y H:i') . '</p>
        </div>
    </body>
    </html>';

    // Configurações do DOMPDF
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Saída do PDF para o navegador
    $dompdf->stream('perfil_' . str_replace(' ', '_', $usuario['nome']) . '.pdf', [
        'Attachment' => true
    ]);

} catch (Exception $erro) {
    echo "Erro ao gerar PDF: " . $erro->getMessage();
    exit();
}
?>