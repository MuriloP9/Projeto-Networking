<?php
// gerenciar_vagas.php
session_start();

include('verificar_permissoes.php');
include('../php/conexao.php');

// Verificar login e permissões
if (!verificarLogin()) {
    header("Location: ../php/index.php");
    exit();
}

// Verificar se pode gerenciar vagas
if (!podeAcessar('gerenciar_vagas')) {
    header("Location: acesso_negado.php");
    exit();
}

$pdo = conectar();
$usuario = getUsuarioLogado();
$mensagem = '';
$tipo_mensagem = '';

// Verificar e criar colunas se necessário
try {
    // Verificar se a coluna 'ativa' existe na tabela Vagas
    $stmt = $pdo->query("
        SELECT COUNT(*) as existe 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'Vagas' AND COLUMN_NAME = 'ativa'
    ");
    $colunaExisteVagas = $stmt->fetch(PDO::FETCH_ASSOC)['existe'];
    
    if (!$colunaExisteVagas) {
        $pdo->exec("ALTER TABLE Vagas ADD ativa BIT DEFAULT 1");
    }
    
    // Verificar se a coluna 'ativa' existe na tabela AreaAtuacao
    $stmt = $pdo->query("
        SELECT COUNT(*) as existe 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'AreaAtuacao' AND COLUMN_NAME = 'ativa'
    ");
    $colunaExisteArea = $stmt->fetch(PDO::FETCH_ASSOC)['existe'];
    
    if (!$colunaExisteArea) {
        $pdo->exec("ALTER TABLE AreaAtuacao ADD ativa BIT DEFAULT 1");
    }
    
} catch (Exception $e) {
    $mensagem = "Erro ao verificar estrutura das tabelas: " . $e->getMessage();
    $tipo_mensagem = "danger";
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    try {
        switch ($acao) {
            case 'criar':
                $stmt = $pdo->prepare("
                    INSERT INTO Vagas (
                        id_funcionario, titulo_vaga, localizacao, tipo_emprego, 
                        descricao, id_area, empresa, salario, requisitos, beneficios,
                        data_encerramento, ativa
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                
                $stmt->execute([
                    $usuario['id_funcionario'],
                    $_POST['titulo_vaga'],
                    $_POST['localizacao'],
                    $_POST['tipo_emprego'],
                    $_POST['descricao'],
                    !empty($_POST['id_area']) ? $_POST['id_area'] : null,
                    $_POST['empresa'],
                    !empty($_POST['salario']) ? $_POST['salario'] : null,
                    $_POST['requisitos'],
                    $_POST['beneficios'],
                    !empty($_POST['data_encerramento']) ? $_POST['data_encerramento'] : null
                ]);
                
                $mensagem = "Vaga criada com sucesso!";
                $tipo_mensagem = "success";
                break;
                
            case 'editar':
                $id_vaga = (int)$_POST['id_vaga'];
                
                $stmt = $pdo->prepare("
                    UPDATE Vagas SET
                        titulo_vaga = ?,
                        localizacao = ?,
                        tipo_emprego = ?,
                        descricao = ?,
                        id_area = ?,
                        empresa = ?,
                        salario = ?,
                        requisitos = ?,
                        beneficios = ?,
                        data_encerramento = ?
                    WHERE id_vaga = ?
                ");
                
                $stmt->execute([
                    $_POST['titulo_vaga'],
                    $_POST['localizacao'],
                    $_POST['tipo_emprego'],
                    $_POST['descricao'],
                    !empty($_POST['id_area']) ? $_POST['id_area'] : null,
                    $_POST['empresa'],
                    !empty($_POST['salario']) ? $_POST['salario'] : null,
                    $_POST['requisitos'],
                    $_POST['beneficios'],
                    !empty($_POST['data_encerramento']) ? $_POST['data_encerramento'] : null,
                    $id_vaga
                ]);
                
                $mensagem = "Vaga atualizada com sucesso!";
                $tipo_mensagem = "success";
                break;
                
            case 'inativar':
                $id_vaga = (int)$_POST['id_vaga'];
                $stmt = $pdo->prepare("UPDATE Vagas SET ativa = 0 WHERE id_vaga = ?");
                $stmt->execute([$id_vaga]);
                
                $mensagem = "Vaga inativada com sucesso!";
                $tipo_mensagem = "warning";
                break;
                
            case 'ativar':
                $id_vaga = (int)$_POST['id_vaga'];
                $stmt = $pdo->prepare("UPDATE Vagas SET ativa = 1 WHERE id_vaga = ?");
                $stmt->execute([$id_vaga]);
                
                $mensagem = "Vaga ativada com sucesso!";
                $tipo_mensagem = "success";
                break;
                
            case 'excluir':
                $id_vaga = (int)$_POST['id_vaga'];
                
                // Verificar se há candidaturas
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM Candidatura WHERE id_vaga = ?");
                $stmt->execute([$id_vaga]);
                $candidaturas = $stmt->fetch()['total'];
                
                if ($candidaturas > 0) {
                    $mensagem = "Não é possível excluir vaga com candidaturas. Considere inativar.";
                    $tipo_mensagem = "danger";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM Vagas WHERE id_vaga = ?");
                    $stmt->execute([$id_vaga]);
                    
                    $mensagem = "Vaga excluída com sucesso!";
                    $tipo_mensagem = "success";
                }
                break;
        }
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Buscar vagas
$filtro = $_GET['filtro'] ?? 'todas';
$busca = $_GET['busca'] ?? '';

// Construir query de forma segura para Vagas
$sql = "
    SELECT 
        v.*,
        f.nome_completo as criado_por,
        a.nome_area,
        (SELECT COUNT(*) FROM Candidatura WHERE id_vaga = v.id_vaga) as total_candidaturas
    FROM Vagas v
    LEFT JOIN Funcionario f ON v.id_funcionario = f.id_funcionario
    LEFT JOIN AreaAtuacao a ON v.id_area = a.id_area
    WHERE 1=1
";

$params = [];

// Filtros - usar try/catch para caso a coluna ainda não exista
if ($filtro === 'ativas') {
    $sql .= " AND v.ativa = 1";
} elseif ($filtro === 'inativas') {
    $sql .= " AND v.ativa = 0";
}

// Busca
if (!empty($busca)) {
    $sql .= " AND (v.titulo_vaga LIKE ? OR v.empresa LIKE ? OR v.localizacao LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

$sql .= " ORDER BY v.data_publicacao DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vagas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar áreas de atuação - query segura
try {
    $stmt = $pdo->query("SELECT * FROM AreaAtuacao WHERE ativa = 1 ORDER BY nome_area");
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Se der erro, buscar sem o filtro ativa
    $stmt = $pdo->query("SELECT * FROM AreaAtuacao ORDER BY nome_area");
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Estatísticas - query segura
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN ativa = 1 THEN 1 ELSE 0 END) as ativas,
            SUM(CASE WHEN ativa = 0 THEN 1 ELSE 0 END) as inativas
        FROM Vagas
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Garantir que os valores não sejam nulos
    if (!$stats) {
        $stats = ['total' => 0, 'ativas' => 0, 'inativas' => 0];
    } else {
        $stats['ativas'] = $stats['ativas'] ?? 0;
        $stats['inativas'] = $stats['inativas'] ?? 0;
    }
} catch (Exception $e) {
    // Fallback se ainda houver erro
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Vagas");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $stats = [
        'total' => $total,
        'ativas' => $total,
        'inativas' => 0
    ];
}

// Adicionar campo ativa padrão para vagas que não têm o campo
foreach ($vagas as &$vaga) {
    if (!array_key_exists('ativa', $vaga)) {
        $vaga['ativa'] = 1; // Valor padrão
    }
}
unset($vaga); // Quebrar a referência
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Vagas</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .vaga-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s;
        }
        .vaga-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .vaga-card.inativa {
            border-left-color: #6c757d;
            opacity: 0.7;
        }
        .badge-tipo {
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .stat-card h3 {
            font-size: 2.5rem;
            margin: 0;
        }
        .modal-lg {
            max-width: 900px;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1><i class="fas fa-briefcase"></i> Gerenciar Vagas</h1>
                <p class="text-muted">Cadastre, edite e gerencie vagas de emprego</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalVaga">
                    <i class="fas fa-plus"></i> Nova Vaga
                </button>
            </div>
        </div>

        <!-- Alertas -->
        <?php if ($mensagem): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $tipo_mensagem === 'success' ? 'check' : 'exclamation'; ?>-circle"></i>
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <p class="mb-1">Total de Vagas</p>
                    <h3><?php echo $stats['total']; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <p class="mb-1">Vagas Ativas</p>
                    <h3><?php echo $stats['ativas']; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                    <p class="mb-1">Vagas Inativas</p>
                    <h3><?php echo $stats['inativas']; ?></h3>
                </div>
            </div>
        </div>

        <!-- Filtros e Busca -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Buscar:</label>
                        <input type="text" class="form-control" name="busca" 
                               placeholder="Título, empresa ou localização..." 
                               value="<?php echo htmlspecialchars($busca); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Filtrar por:</label>
                        <select name="filtro" class="form-select">
                            <option value="todas" <?php echo $filtro === 'todas' ? 'selected' : ''; ?>>Todas</option>
                            <option value="ativas" <?php echo $filtro === 'ativas' ? 'selected' : ''; ?>>Apenas Ativas</option>
                            <option value="inativas" <?php echo $filtro === 'inativas' ? 'selected' : ''; ?>>Apenas Inativas</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Vagas -->
        <div class="row">
            <?php if (empty($vagas)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <h4>Nenhuma vaga encontrada</h4>
                    <p>Comece criando sua primeira vaga!</p>
                </div>
            </div>
            <?php else: ?>
                <?php foreach ($vagas as $vaga): ?>
                <div class="col-md-6 mb-4">
                    <div class="card vaga-card <?php echo !$vaga['ativa'] ? 'inativa' : ''; ?> h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <?php echo htmlspecialchars($vaga['titulo_vaga']); ?>
                                    </h5>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-building"></i> 
                                        <?php echo htmlspecialchars($vaga['empresa']); ?>
                                    </p>
                                </div>
                                <span class="badge bg-<?php echo $vaga['ativa'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $vaga['ativa'] ? 'Ativa' : 'Inativa'; ?>
                                </span>
                            </div>

                            <div class="mb-3">
                                <span class="badge badge-tipo bg-primary me-2">
                                    <i class="fas fa-briefcase"></i>
                                    <?php 
                                    $tipos = [
                                        'full-time' => 'Tempo Integral',
                                        'part-time' => 'Meio Período',
                                        'internship' => 'Estágio'
                                    ];
                                    echo $tipos[$vaga['tipo_emprego']] ?? $vaga['tipo_emprego'];
                                    ?>
                                </span>
                                
                                <?php if ($vaga['localizacao']): ?>
                                <span class="badge bg-info">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($vaga['localizacao']); ?>
                                </span>
                                <?php endif; ?>
                                
                                <?php if ($vaga['nome_area']): ?>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-tag"></i>
                                    <?php echo htmlspecialchars($vaga['nome_area']); ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($vaga['descricao']): ?>
                            <p class="card-text text-muted">
                                <?php echo substr(htmlspecialchars($vaga['descricao']), 0, 150); ?>...
                            </p>
                            <?php endif; ?>

                            <div class="row text-center mb-3">
                                <?php if ($vaga['salario']): ?>
                                <div class="col-4">
                                    <small class="text-muted d-block">Salário</small>
                                    <strong>R$ <?php echo number_format($vaga['salario'], 2, ',', '.'); ?></strong>
                                </div>
                                <?php endif; ?>
                                
                                <div class="col-4">
                                    <small class="text-muted d-block">Candidaturas</small>
                                    <strong><?php echo $vaga['total_candidaturas']; ?></strong>
                                </div>
                                
                                <div class="col-4">
                                    <small class="text-muted d-block">Publicada</small>
                                    <strong><?php echo date('d/m/Y', strtotime($vaga['data_publicacao'])); ?></strong>
                                </div>
                            </div>

                            <div class="btn-group w-100" role="group">
                                <button class="btn btn-outline-primary btn-sm" 
                                        onclick="editarVaga(<?php echo htmlspecialchars(json_encode($vaga)); ?>)">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                
                                <button class="btn btn-outline-info btn-sm" 
                                        onclick="verDetalhes(<?php echo htmlspecialchars(json_encode($vaga)); ?>)">
                                    <i class="fas fa-eye"></i> Ver
                                </button>
                                
                                <?php if ($vaga['ativa']): ?>
                                <button class="btn btn-outline-warning btn-sm" 
                                        onclick="confirmarAcao(<?php echo $vaga['id_vaga']; ?>, 'inativar')">
                                    <i class="fas fa-ban"></i> Inativar
                                </button>
                                <?php else: ?>
                                <button class="btn btn-outline-success btn-sm" 
                                        onclick="confirmarAcao(<?php echo $vaga['id_vaga']; ?>, 'ativar')">
                                    <i class="fas fa-check"></i> Ativar
                                </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="confirmarAcao(<?php echo $vaga['id_vaga']; ?>, 'excluir')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Criar/Editar Vaga -->
    <div class="modal fade" id="modalVaga" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVagaTitle">Nova Vaga</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formVaga">
                    <div class="modal-body">
                        <input type="hidden" name="acao" id="acao" value="criar">
                        <input type="hidden" name="id_vaga" id="id_vaga">
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Título da Vaga *</label>
                                <input type="text" class="form-control" name="titulo_vaga" id="titulo_vaga" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo *</label>
                                <select class="form-select" name="tipo_emprego" id="tipo_emprego" required>
                                    <option value="full-time">Tempo Integral</option>
                                    <option value="part-time">Meio Período</option>
                                    <option value="internship">Estágio</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Empresa *</label>
                                <input type="text" class="form-control" name="empresa" id="empresa" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Localização</label>
                                <input type="text" class="form-control" name="localizacao" id="localizacao" 
                                       placeholder="Ex: São Paulo, SP">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Área de Atuação</label>
                                <select class="form-select" name="id_area" id="id_area">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($areas as $area): ?>
                                    <option value="<?php echo $area['id_area']; ?>">
                                        <?php echo htmlspecialchars($area['nome_area']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Salário</label>
                                <input type="number" class="form-control" name="salario" id="salario" 
                                       step="0.01" placeholder="0.00">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Encerramento</label>
                                <input type="date" class="form-control" name="data_encerramento" id="data_encerramento">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea class="form-control" name="descricao" id="descricao" rows="4" 
                                      placeholder="Descreva a vaga, responsabilidades, etc."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Requisitos</label>
                            <textarea class="form-control" name="requisitos" id="requisitos" rows="3" 
                                      placeholder="Liste os requisitos necessários"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Benefícios</label>
                            <textarea class="form-control" name="beneficios" id="beneficios" rows="3" 
                                      placeholder="Liste os benefícios oferecidos"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detalhes -->
    <div class="modal fade" id="modalDetalhes" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detalhesTitulo"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalhesConteudo">
                </div>
            </div>
        </div>
    </div>

    <!-- Form oculto para ações -->
    <form method="POST" id="formAcao" style="display: none;">
        <input type="hidden" name="acao" id="acaoForm">
        <input type="hidden" name="id_vaga" id="idVagaAcao">
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarVaga(vaga) {
            document.getElementById('modalVagaTitle').textContent = 'Editar Vaga';
            document.getElementById('acao').value = 'editar';
            document.getElementById('id_vaga').value = vaga.id_vaga;
            document.getElementById('titulo_vaga').value = vaga.titulo_vaga;
            document.getElementById('tipo_emprego').value = vaga.tipo_emprego;
            document.getElementById('empresa').value = vaga.empresa;
            document.getElementById('localizacao').value = vaga.localizacao || '';
            document.getElementById('id_area').value = vaga.id_area || '';
            document.getElementById('salario').value = vaga.salario || '';
            document.getElementById('descricao').value = vaga.descricao || '';
            document.getElementById('requisitos').value = vaga.requisitos || '';
            document.getElementById('beneficios').value = vaga.beneficios || '';
            document.getElementById('data_encerramento').value = vaga.data_encerramento ? vaga.data_encerramento.split('T')[0] : '';
            
            new bootstrap.Modal(document.getElementById('modalVaga')).show();
        }

        function verDetalhes(vaga) {
            const tipos = {
                'full-time': 'Tempo Integral',
                'part-time': 'Meio Período',
                'internship': 'Estágio'
            };

            let html = `
                <div class="row mb-3">
                    <div class="col-md-8">
                        <h4>${vaga.titulo_vaga}</h4>
                        <p class="text-muted"><i class="fas fa-building"></i> ${vaga.empresa}</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-${vaga.ativa ? 'success' : 'secondary'} mb-2">
                            ${vaga.ativa ? 'Ativa' : 'Inativa'}
                        </span><br>
                        <span class="badge bg-primary">${tipos[vaga.tipo_emprego] || vaga.tipo_emprego}</span>
                    </div>
                </div>

                <div class="row mb-3">
                    ${vaga.localizacao ? `<div class="col-md-6"><strong>Localização:</strong><br>${vaga.localizacao}</div>` : ''}
                    ${vaga.salario ? `<div class="col-md-6"><strong>Salário:</strong><br>R$ ${parseFloat(vaga.salario).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>` : ''}
                </div>

                ${vaga.descricao ? `
                <div class="mb-3">
                    <h6><i class="fas fa-align-left"></i> Descrição:</h6>
                    <p>${vaga.descricao}</p>
                </div>` : ''}

                ${vaga.requisitos ? `
                <div class="mb-3">
                    <h6><i class="fas fa-list-check"></i> Requisitos:</h6>
                    <p>${vaga.requisitos}</p>
                </div>` : ''}

                ${vaga.beneficios ? `
                <div class="mb-3">
                    <h6><i class="fas fa-gift"></i> Benefícios:</h6>
                    <p>${vaga.beneficios}</p>
                </div>` : ''}

                <hr>

                <div class="row text-center">
                    <div class="col-md-4">
                        <small class="text-muted">Publicada em</small><br>
                        <strong>${new Date(vaga.data_publicacao).toLocaleDateString('pt-BR')}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Candidaturas</small><br>
                        <strong>${vaga.total_candidaturas}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Criada por</small><br>
                        <strong>${vaga.criado_por}</strong>
                    </div>
                </div>
            `;

            document.getElementById('detalhesTitulo').textContent = vaga.titulo_vaga;
            document.getElementById('detalhesConteudo').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalDetalhes')).show();
        }

        function confirmarAcao(id, acao) {
            const mensagens = {
                'inativar': 'Tem certeza que deseja INATIVAR esta vaga?',
                'ativar': 'Tem certeza que deseja ATIVAR esta vaga?',
                'excluir': 'Tem certeza que deseja EXCLUIR esta vaga? Esta ação não pode ser desfeita!'
            };

            if (confirm(mensagens[acao])) {
                document.getElementById('acaoForm').value = acao;
                document.getElementById('idVagaAcao').value = id;
                document.getElementById('formAcao').submit();
            }
        }

        // Limpar form ao abrir modal para nova vaga
        document.getElementById('modalVaga').addEventListener('hidden.bs.modal', function() {
            document.getElementById('formVaga').reset();
            document.getElementById('modalVagaTitle').textContent = 'Nova Vaga';
            document.getElementById('acao').value = 'criar';
            document.getElementById('id_vaga').value = '';
        });

        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>