<?php
// aprovar_candidaturas.php
session_start();

include('verificar_permissoes.php');
include('../php/conexao.php');

// Verificar se tem permissão para acessar esta página
if (!verificarLogin()) {
    header("Location: ../php/index.php");
    exit();
}

// Verificar permissões (Admin ou Gerente podem aprovar)
$pode_aprovar = podeAcessar('aprovar_candidaturas');

if (!$pode_aprovar) {
    header("Location: acesso_negado.php");
    exit();
}

$pdo = conectar();
$usuario_logado = getUsuarioLogado();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'aprovar':
            $id_candidatura = (int)($_POST['id_candidatura'] ?? 0);
            try {
                $stmt = $pdo->prepare("
                    UPDATE Candidatura 
                    SET status = 'Aprovado', data_atualizacao_status = GETDATE() 
                    WHERE id_candidatura = ?
                ");
                $stmt->execute([$id_candidatura]);
                $sucesso = "Candidatura aprovada com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao aprovar candidatura: " . $e->getMessage();
            }
            break;
            
        case 'reprovar':
            $id_candidatura = (int)($_POST['id_candidatura'] ?? 0);
            try {
                $stmt = $pdo->prepare("
                    UPDATE Candidatura 
                    SET status = 'Reprovado', data_atualizacao_status = GETDATE() 
                    WHERE id_candidatura = ?
                ");
                $stmt->execute([$id_candidatura]);
                $sucesso = "Candidatura reprovada.";
            } catch (Exception $e) {
                $erro = "Erro ao reprovar candidatura: " . $e->getMessage();
            }
            break;
            
        case 'desativar':
            $id_candidatura = (int)($_POST['id_candidatura'] ?? 0);
            try {
                $stmt = $pdo->prepare("UPDATE Candidatura SET ativo = 0 WHERE id_candidatura = ?");
                $stmt->execute([$id_candidatura]);
                $sucesso = "Candidatura desativada.";
            } catch (Exception $e) {
                $erro = "Erro ao desativar candidatura: " . $e->getMessage();
            }
            break;
    }
}

// Filtros
$filtro_status = $_GET['status'] ?? 'Pendente';
$busca = $_GET['busca'] ?? '';

// Buscar candidaturas
try {
    // Primeiro, vamos descobrir as colunas da tabela Vagas
    $sql = "
        SELECT 
            c.*,
            u.nome as nome_candidato,
            u.email as email_candidato,
            u.telefone,
            p.formacao,
            p.experiencia_profissional,
            p.habilidades,
            v.* 
        FROM Candidatura c
        INNER JOIN Perfil p ON c.id_perfil = p.id_perfil
        INNER JOIN Usuario u ON p.id_usuario = u.id_usuario
        LEFT JOIN Vagas v ON c.id_vaga = v.id_vaga
        WHERE c.ativo = 1
    ";
    
    $params = [];
    
    if ($filtro_status !== 'Todos') {
        $sql .= " AND c.status = ?";
        $params[] = $filtro_status;
    }
    
    if (!empty($busca)) {
        $sql .= " AND u.nome LIKE ?";
        $params[] = "%$busca%";
    }
    
    $sql .= " ORDER BY c.data_candidatura DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $candidaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Identificar nomes das colunas da vaga
    if (!empty($candidaturas)) {
        $primeira_vaga = $candidaturas[0];
        // Mapear possíveis nomes de colunas
        foreach ($candidaturas as &$cand) {
            $cand['titulo_vaga'] = $cand['titulo'] ?? $cand['nome_vaga'] ?? $cand['nome'] ?? 'Vaga não especificada';
            $cand['empresa'] = $cand['empresa'] ?? $cand['nome_empresa'] ?? 'Empresa não especificada';
        }
    }
    
    // Estatísticas - com valores padrão
    $pendentes = 0;
    $aprovados = 0;
    $reprovados = 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Candidatura WHERE ativo = 1 AND status = 'Pendente'");
    $result = $stmt->fetch();
    $pendentes = $result ? $result['total'] : 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Candidatura WHERE ativo = 1 AND status = 'Aprovado'");
    $result = $stmt->fetch();
    $aprovados = $result ? $result['total'] : 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Candidatura WHERE ativo = 1 AND status = 'Reprovado'");
    $result = $stmt->fetch();
    $reprovados = $result ? $result['total'] : 0;
    
} catch (Exception $e) {
    $erro = "Erro ao buscar candidaturas: " . $e->getMessage();
    $candidaturas = [];
    $pendentes = 0;
    $aprovados = 0;
    $reprovados = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprovar Candidaturas - Sistema de Gestão</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stats-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .stats-card.pendente { border-color: #ffc107; }
        .stats-card.aprovado { border-color: #198754; }
        .stats-card.reprovado { border-color: #dc3545; }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .status-pendente { background: #fff3cd; color: #856404; }
        .status-aprovado { background: #d1e7dd; color: #0f5132; }
        .status-reprovado { background: #f8d7da; color: #842029; }
        
        .candidatura-card {
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .candidatura-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .truncate-text {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-clipboard-check"></i> Aprovar Candidaturas</h2>
                        <p class="text-muted">
                            Logado como: <strong><?php echo htmlspecialchars($usuario_logado['nome']); ?></strong>
                            - <?php echo $usuario_logado['nivel_nome']; ?>
                        </p>
                    </div>
                    <div>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (isset($sucesso)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $sucesso; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($erro)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $erro; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card pendente">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Pendentes</h6>
                                <h3 class="mb-0"><?php echo $pendentes; ?></h3>
                            </div>
                            <div style="font-size: 2rem; color: #ffc107;">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card aprovado">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Aprovados</h6>
                                <h3 class="mb-0"><?php echo $aprovados; ?></h3>
                            </div>
                            <div style="font-size: 2rem; color: #198754;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card reprovado">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Reprovados</h6>
                                <h3 class="mb-0"><?php echo $reprovados; ?></h3>
                            </div>
                            <div style="font-size: 2rem; color: #dc3545;">
                                <i class="fas fa-times-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Buscar:</label>
                        <input type="text" class="form-control" name="busca" 
                               value="<?php echo htmlspecialchars($busca); ?>" 
                               placeholder="Nome do candidato, vaga ou empresa...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status:</label>
                        <select class="form-select" name="status">
                            <option value="Pendente" <?php echo $filtro_status === 'Pendente' ? 'selected' : ''; ?>>Pendentes</option>
                            <option value="Aprovado" <?php echo $filtro_status === 'Aprovado' ? 'selected' : ''; ?>>Aprovados</option>
                            <option value="Reprovado" <?php echo $filtro_status === 'Reprovado' ? 'selected' : ''; ?>>Reprovados</option>
                            <option value="Todos" <?php echo $filtro_status === 'Todos' ? 'selected' : ''; ?>>Todos</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="aprovar_candidaturas.php" class="btn btn-secondary w-100">
                            <i class="fas fa-redo"></i> Limpar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Candidaturas -->
        <div class="row">
            <?php if (empty($candidaturas)): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhuma candidatura encontrada</h5>
                        <p class="text-muted">Tente ajustar os filtros de busca</p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($candidaturas as $cand): ?>
            <div class="col-md-6 mb-4">
                <div class="card candidatura-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($cand['nome_candidato']); ?>
                            </h5>
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i> 
                                <?php echo date('d/m/Y H:i', strtotime($cand['data_candidatura'])); ?>
                            </small>
                        </div>
                        <span class="status-badge status-<?php echo strtolower($cand['status']); ?>">
                            <?php echo $cand['status']; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <!-- Vaga -->
                        <div class="mb-3">
                            <span class="info-label">VAGA:</span>
                            <div class="mt-1">
                                <strong><?php echo htmlspecialchars($cand['titulo_vaga']); ?></strong>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($cand['empresa']); ?>
                                </small>
                            </div>
                        </div>

                        <!-- Contato -->
                        <div class="mb-3">
                            <span class="info-label">CONTATO:</span>
                            <div class="mt-1">
                                <small>
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($cand['email_candidato']); ?>
                                </small>
                                <?php if ($cand['telefone']): ?>
                                <br>
                                <small>
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($cand['telefone']); ?>
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Formação -->
                        <?php if ($cand['formacao']): ?>
                        <div class="mb-3">
                            <span class="info-label">FORMAÇÃO:</span>
                            <div class="mt-1 truncate-text">
                                <small><?php echo nl2br(htmlspecialchars($cand['formacao'])); ?></small>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Experiência -->
                        <?php if ($cand['experiencia_profissional']): ?>
                        <div class="mb-3">
                            <span class="info-label">EXPERIÊNCIA:</span>
                            <div class="mt-1 truncate-text">
                                <small><?php echo nl2br(htmlspecialchars($cand['experiencia_profissional'])); ?></small>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Habilidades -->
                        <?php if ($cand['habilidades']): ?>
                        <div class="mb-3">
                            <span class="info-label">HABILIDADES:</span>
                            <div class="mt-1 truncate-text">
                                <small><?php echo nl2br(htmlspecialchars($cand['habilidades'])); ?></small>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($cand['data_atualizacao_status']): ?>
                        <div class="mt-3 pt-3 border-top">
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> Status atualizado em: 
                                <?php echo date('d/m/Y H:i', strtotime($cand['data_atualizacao_status'])); ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="gerar_curriculo.php?id_candidatura=<?php echo $cand['id_candidatura']; ?>" 
                               class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="fas fa-file-pdf"></i> Ver Currículo (PDF)
                            </a>
                            
                            <?php if ($cand['status'] === 'Pendente'): ?>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-success" 
                                        onclick="alterarStatus(<?php echo $cand['id_candidatura']; ?>, 'aprovar', '<?php echo htmlspecialchars($cand['nome_candidato']); ?>')">
                                    <i class="fas fa-check"></i> Aprovar
                                </button>
                                <button type="button" class="btn btn-danger" 
                                        onclick="alterarStatus(<?php echo $cand['id_candidatura']; ?>, 'reprovar', '<?php echo htmlspecialchars($cand['nome_candidato']); ?>')">
                                    <i class="fas fa-times"></i> Reprovar
                                </button>
                            </div>
                            <?php else: ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                    onclick="desativarCandidatura(<?php echo $cand['id_candidatura']; ?>)">
                                <i class="fas fa-trash"></i> Desativar
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Forms ocultos -->
    <form method="POST" id="formAcao" style="display: none;">
        <input type="hidden" name="acao" id="form_acao">
        <input type="hidden" name="id_candidatura" id="form_id">
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function alterarStatus(id, acao, nome) {
            const mensagens = {
                'aprovar': `Aprovar a candidatura de "${nome}"?`,
                'reprovar': `Reprovar a candidatura de "${nome}"?`
            };
            
            if (confirm(mensagens[acao])) {
                document.getElementById('form_acao').value = acao;
                document.getElementById('form_id').value = id;
                document.getElementById('formAcao').submit();
            }
        }

        function desativarCandidatura(id) {
            if (confirm('Desativar esta candidatura? Ela não será mais exibida na lista.')) {
                document.getElementById('form_acao').value = 'desativar';
                document.getElementById('form_id').value = id;
                document.getElementById('formAcao').submit();
            }
        }

        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-success');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>