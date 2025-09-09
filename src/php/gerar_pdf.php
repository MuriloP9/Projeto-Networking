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
$template = isset($_GET['template']) ? $_GET['template'] : 'classico';

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

    // Função para gerar HTML baseado no template
    function gerarHTML($usuario, $template) {
        switch($template) {
            case 'moderno':
                return gerarTemplateModerno($usuario);
            case 'minimalista':
                return gerarTemplateMinimalista($usuario);
            case 'executivo':
                return gerarTemplateExecutivo($usuario);
            default:
                return gerarTemplateClassico($usuario);
        }
    }

    // Template Clássico (original)
    function gerarTemplateClassico($usuario) {
        return '
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
                <p>Gerado por Prolink em ' . date('d/m/Y H:i') . '</p>
            </div>
        </body>
        </html>';
    }

    // Template Moderno
    function gerarTemplateModerno($usuario) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Currículo - ' . htmlspecialchars($usuario['nome']) . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f8f9fa; }
                .container { width: 100%; min-height: 100vh; }
                .header { background: #4a90e2; color: white; padding: 30px; text-align: center; }
                .header-name { font-size: 28px; font-weight: bold; margin-bottom: 8px; }
                .header-title { font-size: 16px; opacity: 0.9; }
                .content { display: table; width: 100%; }
                .sidebar { display: table-cell; background: #2c3e50; color: white; width: 280px; padding: 30px 25px; vertical-align: top; }
                .main-content { display: table-cell; background: white; padding: 30px; vertical-align: top; }
                .section { margin-bottom: 25px; }
                .section-title { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; color: #4a90e2; border-bottom: 2px solid #4a90e2; padding-bottom: 5px; }
                .sidebar .section-title { color: white; border-bottom-color: #34495e; }
                .contact-item { margin-bottom: 12px; font-size: 13px; line-height: 1.4; }
                .contact-label { font-weight: bold; display: block; margin-bottom: 3px; }
                .skill-item { margin-bottom: 15px; }
                .skill-name { font-size: 13px; margin-bottom: 5px; font-weight: bold; }
                .skill-description { font-size: 12px; line-height: 1.5; color: #bdc3c7; }
                .experience-item { margin-bottom: 20px; }
                .experience-description { font-size: 13px; line-height: 1.6; color: #555; }
                .info-item { margin-bottom: 15px; }
                .info-value { font-size: 13px; color: #2c3e50; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="header-name">' . strtoupper(htmlspecialchars($usuario['nome'])) . '</div>
                    <div class="header-title">Perfil Profissional</div>
                </div>
                
                <div class="content">
                    <div class="sidebar">
                        <div class="section">
                            <div class="section-title">Informações de Contato</div>
                            <div class="contact-item">
                                <div class="contact-label">Email:</div>
                                ' . htmlspecialchars($usuario['email']) . '
                            </div>
                            <div class="contact-item">
                                <div class="contact-label">Telefone:</div>
                                ' . htmlspecialchars($usuario['telefone']) . '
                            </div>
                            <div class="contact-item">
                                <div class="contact-label">Endereço:</div>
                                ' . htmlspecialchars($usuario['endereco']) . '
                            </div>
                            ' . ($usuario['idade'] ? '<div class="contact-item">
                                <div class="contact-label">Idade:</div>
                                ' . htmlspecialchars($usuario['idade']) . ' anos
                            </div>' : '') . '
                            ' . ($usuario['dataNascimento'] ? '<div class="contact-item">
                                <div class="contact-label">Nascimento:</div>
                                ' . htmlspecialchars($usuario['dataNascimento']) . '
                            </div>' : '') . '
                        </div>
                        
                        <div class="section">
                            <div class="section-title">Principais Habilidades</div>
                            <div class="skill-item">
                                <div class="skill-description">' . nl2br(htmlspecialchars($usuario['habilidades'])) . '</div>
                            </div>
                        </div>
                        
                        <div class="section">
                            <div class="section-title">Áreas de Interesse</div>
                            <div class="skill-item">
                                <div class="skill-description">' . nl2br(htmlspecialchars($usuario['interesses'])) . '</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="main-content">
                        <div class="section">
                            <div class="section-title">Experiência Profissional</div>
                            <div class="experience-item">
                                <div class="experience-description">' . nl2br(htmlspecialchars($usuario['experiencia_profissional'])) . '</div>
                            </div>
                        </div>
                        
                        <div class="section">
                            <div class="section-title">Formação Acadêmica</div>
                            <div class="experience-item">
                                <div class="experience-description">' . nl2br(htmlspecialchars($usuario['formacao'])) . '</div>
                            </div>
                        </div>
                        
                        <div class="section">
                            <div class="section-title">Projetos e Especializações</div>
                            <div class="experience-item">
                                <div class="experience-description">' . nl2br(htmlspecialchars($usuario['projetos_especializacoes'])) . '</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>';
    }

    // Template Minimalista
    function gerarTemplateMinimalista($usuario) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>CV - ' . htmlspecialchars($usuario['nome']) . '</title>
            <style>
                body { font-family: "Helvetica", Arial, sans-serif; line-height: 1.8; color: #333; max-width: 700px; margin: 0 auto; padding: 60px 40px; background: white; }
                .header { text-align: center; margin-bottom: 60px; border-bottom: 1px solid #eee; padding-bottom: 40px; }
                .name { font-size: 32px; font-weight: 300; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 10px; color: #2c3e50; }
                .contact-line { font-size: 14px; color: #7f8c8d; margin-bottom: 5px; }
                .section { margin-bottom: 50px; }
                .section-title { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; color: #34495e; margin-bottom: 25px; border-bottom: 1px solid #ecf0f1; padding-bottom: 10px; }
                .content { font-size: 14px; line-height: 1.8; color: #5d6d7e; }
                .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
                .info-item { }
                .info-label { font-size: 12px; color: #95a5a6; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
                .info-value { font-size: 14px; color: #2c3e50; }
                .divider { height: 1px; background: #ecf0f1; margin: 40px 0; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="name">' . htmlspecialchars($usuario['nome']) . '</div>
                <div class="contact-line">' . htmlspecialchars($usuario['email']) . ' | ' . htmlspecialchars($usuario['telefone']) . '</div>
                <div class="contact-line">' . htmlspecialchars($usuario['endereco']) . '</div>
            </div>
            
            <div class="section">
                <div class="section-title">Informações Pessoais</div>
                <div class="info-grid">
                    ' . ($usuario['idade'] ? '                    <div class="info-item"><div class="info-label">Idade</div><div class="info-value">' . htmlspecialchars($usuario['idade']) . ' anos</div></div>' : '') . '
                    <div class="info-item"><div class="info-label">Data de Nascimento</div><div class="info-value">' . htmlspecialchars($usuario['dataNascimento']) . '</div></div>
                </div>
            </div>
            
            <div class="divider"></div>
            
            <div class="section">
                <div class="section-title">Experiência Profissional</div>
                <div class="content">' . nl2br(htmlspecialchars($usuario['experiencia_profissional'])) . '</div>
            </div>
            
            <div class="section">
                <div class="section-title">Educação</div>
                <div class="content">' . nl2br(htmlspecialchars($usuario['formacao'])) . '</div>
            </div>
            
            <div class="section">
                <div class="section-title">Competências</div>
                <div class="content">' . nl2br(htmlspecialchars($usuario['habilidades'])) . '</div>
            </div>
            
            <div class="section">
                <div class="section-title">Projetos</div>
                <div class="content">' . nl2br(htmlspecialchars($usuario['projetos_especializacoes'])) . '</div>
            </div>
            
            <div class="section">
                <div class="section-title">Interesses</div>
                <div class="content">' . nl2br(htmlspecialchars($usuario['interesses'])) . '</div>
            </div>
        </body>
        </html>';
    }

    // Template Executivo
    function gerarTemplateExecutivo($usuario) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Currículo Executivo - ' . htmlspecialchars($usuario['nome']) . '</title>
            <style>
                body { font-family: "Times New Roman", serif; line-height: 1.6; color: #2c3e50; max-width: 800px; margin: 0 auto; padding: 40px; background: white; }
                .header { background: #f8f9fa; margin: -40px -40px 40px -40px; padding: 40px; text-align: center; border-bottom: 4px solid #34495e; }
                .executive-name { font-size: 36px; font-weight: bold; color: #2c3e50; margin-bottom: 10px; letter-spacing: 1px; }
                .executive-title { font-size: 18px; color: #7f8c8d; font-style: italic; margin-bottom: 20px; }
                .contact-info { font-size: 14px; color: #5d6d7e; }
                .contact-info span { margin: 0 15px; }
                .section { margin-bottom: 35px; }
                .section-title { font-size: 16px; font-weight: bold; color: #34495e; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; border-left: 4px solid #3498db; padding-left: 15px; }
                .two-column { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
                .executive-summary { background: #ecf0f1; padding: 25px; border-radius: 5px; margin-bottom: 30px; border-left: 4px solid #3498db; }
                .summary-text { font-size: 15px; line-height: 1.7; font-style: italic; color: #2c3e50; }
                .experience-block { margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #bdc3c7; }
                .position-title { font-size: 16px; font-weight: bold; color: #2c3e50; }
                .company-name { font-size: 14px; color: #3498db; margin: 5px 0; }
                .date-range { font-size: 13px; color: #7f8c8d; margin-bottom: 10px; }
                .description { font-size: 14px; line-height: 1.6; }
                .skills-list { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                .skill-category { margin-bottom: 15px; }
                .skill-category-title { font-size: 13px; font-weight: bold; color: #34495e; margin-bottom: 8px; text-transform: uppercase; }
                .skill-items { font-size: 13px; color: #5d6d7e; line-height: 1.5; }
                .education-item { margin-bottom: 15px; }
                .degree { font-weight: bold; color: #2c3e50; }
                .institution { color: #3498db; font-size: 14px; }
                .footer-note { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #bdc3c7; font-size: 12px; color: #95a5a6; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="executive-name">' . strtoupper(htmlspecialchars($usuario['nome'])) . '</div>
                <div class="executive-title">Executivo | Líder Estratégico</div>
                <div class="contact-info">
                    <span>' . htmlspecialchars($usuario['email']) . '</span>
                    <span>' . htmlspecialchars($usuario['telefone']) . '</span>
                    <span>' . htmlspecialchars($usuario['endereco']) . '</span>
                </div>
            </div>
            
            <div class="executive-summary">
                <div class="summary-text">
                    Profissional experiente com sólida formação e expertise comprovada. 
                    Comprometido com a excelência operacional e o desenvolvimento estratégico organizacional.
                </div>
            </div>
            
            <div class="two-column">
                <div class="left-column">
                    <div class="section">
                        <div class="section-title">Experiência Profissional</div>
                        <div class="experience-block">
                            <div class="description">' . nl2br(htmlspecialchars($usuario['experiencia_profissional'])) . '</div>
                        </div>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">Formação Acadêmica</div>
                        <div class="education-item">
                            <div class="description">' . nl2br(htmlspecialchars($usuario['formacao'])) . '</div>
                        </div>
                    </div>
                </div>
                
                <div class="right-column">
                    <div class="section">
                        <div class="section-title">Competências Principais</div>
                        <div class="skill-category">
                            <div class="skill-items">' . nl2br(htmlspecialchars($usuario['habilidades'])) . '</div>
                        </div>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">Projetos Estratégicos</div>
                        <div class="description">' . nl2br(htmlspecialchars($usuario['projetos_especializacoes'])) . '</div>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">Áreas de Interesse</div>
                        <div class="description">' . nl2br(htmlspecialchars($usuario['interesses'])) . '</div>
                    </div>
                    
                    ' . ($usuario['idade'] ? '<div class="section">
                        <div class="section-title">Informações Adicionais</div>
                        <div class="description">Idade: ' . htmlspecialchars($usuario['idade']) . ' anos<br>
                        Data de Nascimento: ' . htmlspecialchars($usuario['dataNascimento']) . '</div>
                    </div>' : '') . '
                </div>
            </div>
            
            <div class="footer-note">
                Documento gerado via ProLink em ' . date('d/m/Y') . '
            </div>
        </body>
        </html>';
    }

    // Gerar o HTML baseado no template escolhido
    $html = gerarHTML($usuario, $template);

    // Configurações do DOMPDF
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Nome do arquivo baseado no template
    $templateNames = [
        'classico' => 'Clássico',
        'moderno' => 'Moderno', 
        'minimalista' => 'Minimalista',
        'executivo' => 'Executivo'
    ];
    
    $templateName = isset($templateNames[$template]) ? $templateNames[$template] : 'Clássico';
    $fileName = 'CV_' . str_replace(' ', '_', $usuario['nome']) . '_' . $templateName . '.pdf';
    
    // Saída do PDF para o navegador
    $dompdf->stream($fileName, [
        'Attachment' => true
    ]);

} catch (Exception $erro) {
    echo "Erro ao gerar PDF: " . $erro->getMessage();
    exit();
}
?>