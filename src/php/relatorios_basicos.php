<?php
// relatorios_basicos.php
session_start();

include('verificar_permissoes.php');
include('../php/conexao.php');

// Verificar login e permissões
if (!verificarLogin()) {
    header("Location: ../php/index.php");
    exit();
}

// Verificar se pode acessar relatórios
if (!podeAcessar('relatorios_basicos')) {
    header("Location: acesso_negado.php");
    exit();
}

$pdo = conectar();
$usuario = getUsuarioLogado();

// Período padrão: últimos 30 dias
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

// Relatório de Vagas
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_vagas,
        SUM(CASE WHEN ativa = 1 THEN 1 ELSE 0 END) as vagas_ativas,
        SUM(CASE WHEN ativa = 0 THEN 1 ELSE 0 END) as vagas_inativas,
        SUM(CASE WHEN data_publicacao BETWEEN ? AND ? THEN 1 ELSE 0 END) as vagas_periodo
    FROM Vagas
");
$stmt->execute([$data_inicio, $data_fim]);
$relatorio_vagas = $stmt->fetch(PDO::FETCH_ASSOC);

// Top 5 Vagas com mais candidaturas
$stmt = $pdo->prepare("
    SELECT TOP 5
        v.titulo_vaga,
        v.empresa,
        v.tipo_emprego,
        COUNT(c.id_candidatura) as total_candidaturas
    FROM Vagas v
    LEFT JOIN Candidatura c ON v.id_vaga = c.id_vaga
    WHERE v.data_publicacao BETWEEN ? AND ?
    GROUP BY v.id_vaga, v.titulo_vaga, v.empresa, v.tipo_emprego
    ORDER BY total_candidaturas DESC
");
$stmt->execute([$data_inicio, $data_fim]);
$top_vagas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vagas por Área de Atuação
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(a.nome_area, 'Sem Área') as area,
        COUNT(v.id_vaga) as total_vagas,
        SUM(CASE WHEN v.ativa = 1 THEN 1 ELSE 0 END) as ativas
    FROM Vagas v
    LEFT JOIN AreaAtuacao a ON v.id_area = a.id_area
    WHERE v.data_publicacao BETWEEN ? AND ?
    GROUP BY a.nome_area
    ORDER BY total_vagas DESC
");
$stmt->execute([$data_inicio, $data_fim]);
$vagas_por_area = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vagas por Tipo de Emprego
$stmt = $pdo->prepare("
    SELECT 
        tipo_emprego,
        COUNT(*) as total
    FROM Vagas
    WHERE data_publicacao BETWEEN ? AND ?
    GROUP BY tipo_emprego
    ORDER BY total DESC
");
$stmt->execute([$data_inicio, $data_fim]);
$vagas_por_tipo = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar estrutura da tabela Candidatura primeiro
try {
    $stmt = $pdo->query("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'Candidatura'
    ");
    $colunas_candidatura = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Verificar se existe coluna relacionada a candidato
    $tem_id_usuario = in_array('id_usuario', $colunas_candidatura);
    $tem_cpf_candidato = in_array('cpf_candidato', $colunas_candidatura);
    
} catch (Exception $e) {
    $tem_id_usuario = false;
    $tem_cpf_candidato = false;
}

// Relatório de Candidaturas
if ($tem_id_usuario) {
    $coluna_candidato = 'id_usuario';
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_candidaturas,
            COUNT(DISTINCT {$coluna_candidato}) as candidatos_unicos,
            COUNT(DISTINCT id_vaga) as vagas_com_candidaturas
        FROM Candidatura
        WHERE data_candidatura BETWEEN ? AND ?
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $relatorio_candidaturas = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($tem_cpf_candidato) {
    $coluna_candidato = 'cpf_candidato';
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_candidaturas,
            COUNT(DISTINCT {$coluna_candidato}) as candidatos_unicos,
            COUNT(DISTINCT id_vaga) as vagas_com_candidaturas
        FROM Candidatura
        WHERE data_candidatura BETWEEN ? AND ?
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $relatorio_candidaturas = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Fallback se não encontrar a coluna
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_candidaturas,
            0 as candidatos_unicos,
            COUNT(DISTINCT id_vaga) as vagas_com_candidaturas
        FROM Candidatura
        WHERE data_candidatura BETWEEN ? AND ?
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $relatorio_candidaturas = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Candidaturas por Status
$stmt = $pdo->prepare("
    SELECT 
        status,
        COUNT(*) as total
    FROM Candidatura
    WHERE data_candidatura BETWEEN ? AND ?
    GROUP BY status
    ORDER BY total DESC
");
$stmt->execute([$data_inicio, $data_fim]);
$candidaturas_por_status = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top 5 Candidatos mais ativos
if ($tem_id_usuario) {
    $stmt = $pdo->prepare("
        SELECT TOP 5
            u.nome,
            u.email,
            COUNT(c.id_candidatura) as total_candidaturas,
            MAX(c.data_candidatura) as ultima_candidatura
        FROM Usuario u
        INNER JOIN Candidatura c ON u.id_usuario = c.id_usuario
        WHERE c.data_candidatura BETWEEN ? AND ?
        GROUP BY u.id_usuario, u.nome, u.email
        ORDER BY total_candidaturas DESC
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $top_candidatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($tem_cpf_candidato) {
    $stmt = $pdo->prepare("
        SELECT TOP 5
            c.cpf_candidato,
            COUNT(c.id_candidatura) as total_candidaturas,
            MAX(c.data_candidatura) as ultima_candidatura
        FROM Candidatura c
        WHERE c.data_candidatura BETWEEN ? AND ?
        AND c.cpf_candidato IS NOT NULL
        GROUP BY c.cpf_candidato
        ORDER BY total_candidaturas DESC
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $top_candidatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $top_candidatos = [];
}

// Relatório de Webinars
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_webinars,
        SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as webinars_ativos,
        SUM(CASE WHEN data_hora BETWEEN ? AND ? THEN 1 ELSE 0 END) as webinars_periodo,
        SUM(CASE WHEN data_hora >= GETDATE() AND ativo = 1 THEN 1 ELSE 0 END) as proximos
    FROM Webinar
");
$stmt->execute([$data_inicio, $data_fim]);
$relatorio_webinars = $stmt->fetch(PDO::FETCH_ASSOC);

// Próximos Webinars
$stmt = $pdo->query("
    SELECT TOP 5
        tema,
        palestrante,
        data_hora,
        ativo
    FROM Webinar
    WHERE data_hora >= GETDATE()
    ORDER BY data_hora ASC
");
$proximos_webinars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas Gerais do Sistema
$stmt = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM Usuario WHERE ativo = 1) as total_candidatos,
        (SELECT COUNT(*) FROM Funcionario WHERE ativo = 1) as total_funcionarios,
        (SELECT COUNT(*) FROM AreaAtuacao WHERE ativa = 1) as total_areas,
        (SELECT COUNT(*) FROM Vagas WHERE ativa = 1) as vagas_ativas_total
");
$stats_gerais = $stmt->fetch(PDO::FETCH_ASSOC);

// Mapeamento de status legível
$status_labels = [
    'pendente' => 'Pendente',
    'em_analise' => 'Em Análise',
    'aprovado' => 'Aprovado',
    'rejeitado' => 'Rejeitado'
];

$tipo_emprego_labels = [
    'full-time' => 'Tempo Integral',
    'part-time' => 'Meio Período',
    'internship' => 'Estágio'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Básicos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stat-card {
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            color: white;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h3 {
            font-size: 2.5rem;
            margin: 0;
        }
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .progress-bar-custom {
            height: 30px;
            font-size: 14px;
            line-height: 30px;
        }
        .badge-large {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .stat-card {
                page-break-inside: avoid;
            }
            .chart-container {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <div>
                <h1><i class="fas fa-chart-bar"></i> Relatórios Básicos</h1>
                <p class="text-muted">Visualize estatísticas e indicadores do sistema</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>

        <!-- Filtro de Período -->
        <div class="card mb-4 no-print">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Data Início:</label>
                        <input type="date" class="form-control" name="data_inicio" 
                               value="<?php echo $data_inicio; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Data Fim:</label>
                        <input type="date" class="form-control" name="data_fim" 
                               value="<?php echo $data_fim; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filtrar Período
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Período Selecionado -->
        <div class="alert alert-info">
            <i class="fas fa-calendar-alt"></i> 
            <strong>Período:</strong> <?php echo date('d/m/Y', strtotime($data_inicio)); ?> até <?php echo date('d/m/Y', strtotime($data_fim)); ?>
        </div>

        <!-- Estatísticas Gerais -->
        <h3 class="mb-3"><i class="fas fa-chart-pie"></i> Visão Geral do Sistema</h3>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <p class="mb-1"><i class="fas fa-briefcase"></i> Vagas Ativas</p>
                    <h3><?php echo $stats_gerais['vagas_ativas_total']; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <p class="mb-1"><i class="fas fa-users"></i> Candidatos</p>
                    <h3><?php echo $stats_gerais['total_candidatos']; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <p class="mb-1"><i class="fas fa-user-tie"></i> Funcionários</p>
                    <h3><?php echo $stats_gerais['total_funcionarios']; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <p class="mb-1"><i class="fas fa-tags"></i> Áreas Ativas</p>
                    <h3><?php echo $stats_gerais['total_areas']; ?></h3>
                </div>
            </div>
        </div>

        <!-- Relatório de Vagas -->
        <h3 class="mb-3"><i class="fas fa-briefcase"></i> Relatório de Vagas</h3>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <p class="mb-1">Total de Vagas</p>
                    <h3><?php echo $relatorio_vagas['total_vagas']; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <p class="mb-1">Vagas Ativas</p>
                    <h3><?php echo $relatorio_vagas['vagas_ativas']; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                    <p class="mb-1">Vagas Inativas</p>
                    <h3><?php echo $relatorio_vagas['vagas_inativas']; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                    <p class="mb-1">Vagas no Período</p>
                    <h3><?php echo $relatorio_vagas['vagas_periodo']; ?></h3>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <!-- Vagas por Tipo -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-pie"></i> Vagas por Tipo de Emprego</h5>
                    <hr>
                    <?php if (empty($vagas_por_tipo)): ?>
                        <p class="text-muted text-center">Nenhuma vaga no período selecionado</p>
                    <?php else: ?>
                        <?php 
                        $total_tipo = array_sum(array_column($vagas_por_tipo, 'total'));
                        foreach ($vagas_por_tipo as $tipo): 
                            $percentual = ($tipo['total'] / $total_tipo) * 100;
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><?php echo $tipo_emprego_labels[$tipo['tipo_emprego']] ?? $tipo['tipo_emprego']; ?></span>
                                <strong><?php echo $tipo['total']; ?> (<?php echo number_format($percentual, 1); ?>%)</strong>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" style="width: <?php echo $percentual; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Vagas por Área -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-tags"></i> Vagas por Área de Atuação</h5>
                    <hr>
                    <?php if (empty($vagas_por_area)): ?>
                        <p class="text-muted text-center">Nenhuma vaga no período selecionado</p>
                    <?php else: ?>
                        <?php 
                        $total_area = array_sum(array_column($vagas_por_area, 'total_vagas'));
                        foreach (array_slice($vagas_por_area, 0, 5) as $area): 
                            $percentual = ($area['total_vagas'] / $total_area) * 100;
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><?php echo htmlspecialchars($area['area']); ?></span>
                                <strong><?php echo $area['total_vagas']; ?> (<?php echo number_format($percentual, 1); ?>%)</strong>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: <?php echo $percentual; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top 5 Vagas -->
        <div class="table-container mb-4">
            <h5><i class="fas fa-trophy"></i> Top 5 Vagas com Mais Candidaturas</h5>
            <hr>
            <?php if (empty($top_vagas)): ?>
                <p class="text-muted text-center">Nenhuma vaga com candidaturas no período</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Título da Vaga</th>
                                <th>Empresa</th>
                                <th>Tipo</th>
                                <th class="text-center">Candidaturas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_vagas as $index => $vaga): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?php echo $index + 1; ?>º</span></td>
                                <td><?php echo htmlspecialchars($vaga['titulo_vaga']); ?></td>
                                <td><?php echo htmlspecialchars($vaga['empresa']); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo $tipo_emprego_labels[$vaga['tipo_emprego']] ?? $vaga['tipo_emprego']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success badge-large">
                                        <?php echo $vaga['total_candidaturas']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Relatório de Candidaturas -->
        <h3 class="mb-3"><i class="fas fa-users"></i> Relatório de Candidaturas</h3>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <p class="mb-1">Total de Candidaturas</p>
                    <h3><?php echo $relatorio_candidaturas['total_candidaturas']; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <p class="mb-1">Candidatos Únicos</p>
                    <h3><?php echo $relatorio_candidaturas['candidatos_unicos']; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <p class="mb-1">Vagas com Candidaturas</p>
                    <h3><?php echo $relatorio_candidaturas['vagas_com_candidaturas']; ?></h3>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <!-- Candidaturas por Status -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-bar"></i> Candidaturas por Status</h5>
                    <hr>
                    <?php if (empty($candidaturas_por_status)): ?>
                        <p class="text-muted text-center">Nenhuma candidatura no período</p>
                    <?php else: ?>
                        <?php 
                        $total_status = array_sum(array_column($candidaturas_por_status, 'total'));
                        $cores_status = [
                            'pendente' => 'warning',
                            'em_analise' => 'info',
                            'aprovado' => 'success',
                            'rejeitado' => 'danger'
                        ];
                        foreach ($candidaturas_por_status as $status): 
                            $percentual = ($status['total'] / $total_status) * 100;
                            $cor = $cores_status[$status['status']] ?? 'secondary';
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><?php echo $status_labels[$status['status']] ?? $status['status']; ?></span>
                                <strong><?php echo $status['total']; ?> (<?php echo number_format($percentual, 1); ?>%)</strong>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-<?php echo $cor; ?>" style="width: <?php echo $percentual; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Candidatos -->
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-star"></i> Top 5 Candidatos Mais Ativos</h5>
                    <hr>
                    <?php if (empty($top_candidatos)): ?>
                        <p class="text-muted text-center">Nenhum candidato no período</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Candidato</th>
                                        <th class="text-center">Candidaturas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_candidatos as $index => $candidato): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?>º</td>
                                        <td>
                                            <?php if (isset($candidato['nome'])): ?>
                                                <?php echo htmlspecialchars($candidato['nome']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($candidato['email']); ?></small>
                                            <?php else: ?>
                                                <strong>CPF:</strong> <?php echo htmlspecialchars($candidato['cpf_candidato']); ?><br>
                                                <small class="text-muted">Última candidatura: <?php echo date('d/m/Y', strtotime($candidato['ultima_candidatura'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?php echo $candidato['total_candidaturas']; ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Relatório de Webinars -->
        <h3 class="mb-3"><i class="fas fa-video"></i> Relatório de Webinars</h3>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);">
                    <p class="mb-1">Total de Webinars</p>
                    <h3><?php echo $relatorio_webinars['total_webinars']; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <p class="mb-1">Webinars Ativos</p>
                    <h3><?php echo $relatorio_webinars['webinars_ativos']; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <p class="mb-1">Próximos Webinars</p>
                    <h3><?php echo $relatorio_webinars['proximos']; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                    <p class="mb-1">No Período</p>
                    <h3><?php echo $relatorio_webinars['webinars_periodo']; ?></h3>
                </div>
            </div>
        </div>

        <!-- Próximos Webinars -->
        <div class="table-container mb-4">
            <h5><i class="fas fa-calendar-check"></i> Próximos Webinars Agendados</h5>
            <hr>
            <?php if (empty($proximos_webinars)): ?>
                <p class="text-muted text-center">Nenhum webinar agendado</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Tema</th>
                                <th>Palestrante</th>
                                <th>Data e Horário</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proximos_webinars as $webinar): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($webinar['tema']); ?></td>
                                <td><?php echo htmlspecialchars($webinar['palestrante']); ?></td>
                                <td>
                                    <i class="fas fa-calendar-alt text-primary"></i>
                                    <?php echo date('d/m/Y', strtotime($webinar['data_hora'])); ?>
                                    <i class="fas fa-clock text-primary ms-2"></i>
                                    <?php echo date('H:i', strtotime($webinar['data_hora'])); ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo $webinar['ativo'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $webinar['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Rodapé -->
        <div class="text-center text-muted mt-5 mb-3">
            <p>Relatório gerado em <?php echo date('d/m/Y H:i:s'); ?></p>
            <p>Usuário: <?php echo htmlspecialchars($usuario['nome_completo']); ?></p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>