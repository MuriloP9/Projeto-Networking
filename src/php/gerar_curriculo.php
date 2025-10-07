<?php
// gerar_curriculo.php
session_start();

include('verificar_permissoes.php');
include('../php/conexao.php');
require_once '../php/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Verificar se tem permissão
if (!verificarLogin()) {
    header("Location: ../php/index.php");
    exit();
}

if (!podeAcessar('aprovar_candidaturas')) {
    header("Location: acesso_negado.php");
    exit();
}

$id_candidatura = (int)($_GET['id_candidatura'] ?? 0);

if ($id_candidatura <= 0) {
    die("ID de candidatura inválido!");
}

$pdo = conectar();

// Buscar dados completos da candidatura
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.nome,
            u.email,
            u.telefone,
            u.dataNascimento,
            p.idade,
            p.endereco,
            p.formacao,
            p.experiencia_profissional,
            p.interesses,
            p.projetos_especializacoes,
            p.habilidades,
            v.*,
            c.status,
            c.data_candidatura
        FROM Candidatura c
        INNER JOIN Perfil p ON c.id_perfil = p.id_perfil
        INNER JOIN Usuario u ON p.id_usuario = u.id_usuario
        LEFT JOIN Vagas v ON c.id_vaga = v.id_vaga
        WHERE c.id_candidatura = ?
    ");
    $stmt->execute([$id_candidatura]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        die("Candidatura não encontrada!");
    }
    
    // Mapear colunas da vaga
    $usuario['vaga_titulo'] = $usuario['titulo'] ?? $usuario['nome_vaga'] ?? $usuario['nome'] ?? 'Vaga não especificada';
    $usuario['vaga_empresa'] = $usuario['empresa'] ?? $usuario['nome_empresa'] ?? 'Empresa não especificada';
    
} catch (Exception $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}

// Função para gerar o template HTML
function gerarTemplateClassico($usuario) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Perfil de ' . htmlspecialchars($usuario['nome']) . '</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                max-width: 800px; 
                margin: 0 auto; 
                padding: 20px; 
            }
            .header { 
                margin-bottom: 30px; 
                border-bottom: 2px solid #2c3e50; 
                padding-bottom: 20px; 
            }
            .nome-usuario { 
                font-size: 24px; 
                font-weight: bold; 
                color: #2c3e50; 
                margin: 0; 
            }
            .section { 
                margin-bottom: 20px; 
            }
            .section h2 { 
                color: #2c3e50; 
                border-bottom: 1px solid #eee; 
                padding-bottom: 5px; 
                font-size: 18px;
            }
            .info-item { 
                margin-bottom: 10px; 
            }
            .info-item strong { 
                display: inline-block; 
                width: 180px; 
            }
            .footer { 
                margin-top: 30px; 
                text-align: center; 
                font-size: 12px; 
                color: #777; 
                border-top: 1px solid #eee;
                padding-top: 20px;
            }
            .vaga-info {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1 class="nome-usuario">' . htmlspecialchars($usuario['nome']) . '</h1>
            <p>Perfil profissional</p>
        </div>
        
        <div class="vaga-info">
            <strong>Candidatura para:</strong> ' . htmlspecialchars($usuario['vaga_titulo']) . '<br>
            <strong>Empresa:</strong> ' . htmlspecialchars($usuario['vaga_empresa']) . '<br>
            <strong>Data da candidatura:</strong> ' . date('d/m/Y', strtotime($usuario['data_candidatura'])) . '<br>
            <strong>Status:</strong> ' . htmlspecialchars($usuario['status']) . '
        </div>
        
        <div class="section">
            <h2>Informações Pessoais</h2>
            <div class="info-item"><strong>Nome:</strong> ' . htmlspecialchars($usuario['nome']) . '</div>
            ' . ($usuario['idade'] ? '<div class="info-item"><strong>Idade:</strong> ' . htmlspecialchars($usuario['idade']) . ' anos</div>' : '') . '
            ' . ($usuario['endereco'] ? '<div class="info-item"><strong>Endereço:</strong> ' . htmlspecialchars($usuario['endereco']) . '</div>' : '') . '
            ' . ($usuario['dataNascimento'] ? '<div class="info-item"><strong>Data de Nascimento:</strong> ' . date('d/m/Y', strtotime($usuario['dataNascimento'])) . '</div>' : '') . '
            ' . ($usuario['telefone'] ? '<div class="info-item"><strong>Telefone:</strong> ' . htmlspecialchars($usuario['telefone']) . '</div>' : '') . '
            <div class="info-item"><strong>E-mail:</strong> ' . htmlspecialchars($usuario['email']) . '</div>
        </div>
        
        ' . ($usuario['formacao'] ? '
        <div class="section">
            <h2>Formação Acadêmica</h2>
            <p>' . nl2br(htmlspecialchars($usuario['formacao'])) . '</p>
        </div>
        ' : '') . '
        
        ' . ($usuario['experiencia_profissional'] ? '
        <div class="section">
            <h2>Experiência Profissional</h2>
            <p>' . nl2br(htmlspecialchars($usuario['experiencia_profissional'])) . '</p>
        </div>
        ' : '') . '
        
        ' . ($usuario['habilidades'] ? '
        <div class="section">
            <h2>Habilidades</h2>
            <p>' . nl2br(htmlspecialchars($usuario['habilidades'])) . '</p>
        </div>
        ' : '') . '
        
        ' . ($usuario['projetos_especializacoes'] ? '
        <div class="section">
            <h2>Projetos e Especializações</h2>
            <p>' . nl2br(htmlspecialchars($usuario['projetos_especializacoes'])) . '</p>
        </div>
        ' : '') . '
        
        ' . ($usuario['interesses'] ? '
        <div class="section">
            <h2>Interesses</h2>
            <p>' . nl2br(htmlspecialchars($usuario['interesses'])) . '</p>
        </div>
        ' : '') . '
        
        <div class="footer">
            <p>Currículo gerado pelo Sistema Prolink em ' . date('d/m/Y H:i') . '</p>
        </div>
    </body>
    </html>';
}

// Gerar o HTML
$html = gerarTemplateClassico($usuario);

// Configurar o Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Nome do arquivo
$nome_arquivo = 'Curriculo_' . preg_replace('/[^A-Za-z0-9_]/', '_', $usuario['nome']) . '_' . date('YmdHis') . '.pdf';

// Enviar o PDF para o navegador
$dompdf->stream($nome_arquivo, array('Attachment' => 0)); // 0 = visualizar no navegador, 1 = download
?>