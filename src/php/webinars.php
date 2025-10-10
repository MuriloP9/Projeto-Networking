<?php
// gerenciar_webinars.php
session_start();

include('verificar_permissoes.php');
include('../php/conexao.php');

// Verificar login e permissões
if (!verificarLogin()) {
    header("Location: ../php/index.php");
    exit();
}

// Verificar se pode gerenciar webinars
if (!podeAcessar('gerenciar_webinars')) {
    header("Location: acesso_negado.php");
    exit();
}

$pdo = conectar();
$usuario = getUsuarioLogado();
$mensagem = '';
$tipo_mensagem = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    try {
        switch ($acao) {
            case 'criar':
                $stmt = $pdo->prepare("
                    INSERT INTO Webinar (
                        tema, data_hora, palestrante, link, descricao, ativo
                    ) VALUES (?, ?, ?, ?, ?, 1)
                ");
                
                $stmt->execute([
                    $_POST['tema'],
                    $_POST['data_hora'],
                    $_POST['palestrante'],
                    $_POST['link'],
                    $_POST['descricao']
                ]);
                
                $mensagem = "Webinar criado com sucesso!";
                $tipo_mensagem = "success";
                break;
                
            case 'editar':
                $id_webinar = (int)$_POST['id_webinar'];
                
                $stmt = $pdo->prepare("
                    UPDATE Webinar SET
                        tema = ?,
                        data_hora = ?,
                        palestrante = ?,
                        link = ?,
                        descricao = ?
                    WHERE id_webinar = ?
                ");
                
                $stmt->execute([
                    $_POST['tema'],
                    $_POST['data_hora'],
                    $_POST['palestrante'],
                    $_POST['link'],
                    $_POST['descricao'],
                    $id_webinar
                ]);
                
                $mensagem = "Webinar atualizado com sucesso!";
                $tipo_mensagem = "success";
                break;
                
            case 'inativar':
                $id_webinar = (int)$_POST['id_webinar'];
                $stmt = $pdo->prepare("UPDATE Webinar SET ativo = 0 WHERE id_webinar = ?");
                $stmt->execute([$id_webinar]);
                
                $mensagem = "Webinar inativado com sucesso!";
                $tipo_mensagem = "warning";
                break;
                
            case 'ativar':
                $id_webinar = (int)$_POST['id_webinar'];
                $stmt = $pdo->prepare("UPDATE Webinar SET ativo = 1 WHERE id_webinar = ?");
                $stmt->execute([$id_webinar]);
                
                $mensagem = "Webinar ativado com sucesso!";
                $tipo_mensagem = "success";
                break;
                
            case 'excluir':
                $id_webinar = (int)$_POST['id_webinar'];
                $stmt = $pdo->prepare("DELETE FROM Webinar WHERE id_webinar = ?");
                $stmt->execute([$id_webinar]);
                
                $mensagem = "Webinar excluído com sucesso!";
                $tipo_mensagem = "success";
                break;
        }
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Buscar webinars
$filtro = $_GET['filtro'] ?? 'todos';
$busca = $_GET['busca'] ?? '';

$sql = "
    SELECT *
    FROM Webinar
    WHERE 1=1
";

$params = [];

// Filtros
if ($filtro === 'ativos') {
    $sql .= " AND ativo = 1";
} elseif ($filtro === 'inativos') {
    $sql .= " AND ativo = 0";
} elseif ($filtro === 'proximos') {
    $sql .= " AND data_hora >= GETDATE() AND ativo = 1";
} elseif ($filtro === 'passados') {
    $sql .= " AND data_hora < GETDATE()";
}

// Busca
if (!empty($busca)) {
    $sql .= " AND (tema LIKE ? OR palestrante LIKE ? OR descricao LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

$sql .= " ORDER BY data_hora DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$webinars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as ativos,
        SUM(CASE WHEN ativo = 0 THEN 1 ELSE 0 END) as inativos,
        SUM(CASE WHEN data_hora >= GETDATE() AND ativo = 1 THEN 1 ELSE 0 END) as proximos
    FROM Webinar
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Webinars</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .webinar-card {
            border-left: 4px solid #7c3aed;
            transition: all 0.3s;
        }
        .webinar-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .webinar-card.inativo {
            border-left-color: #6c757d;
            opacity: 0.7;
        }
        .webinar-card.proximo {
            border-left-color: #10b981;
        }
        .webinar-card.passado {
            border-left-color: #ef4444;
        }
        .stat-card {
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
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
        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1><i class="fas fa-video"></i> Gerenciar Webinars</h1>
                <p class="text-muted">Cadastre, edite e gerencie webinars e eventos online</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalWebinar">
                    <i class="fas fa-plus"></i> Novo Webinar
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
            <div class="col-md-3">
                <div class="stat-card">
                    <p class="mb-1">Total de Webinars</p>
                    <h3><?php echo $stats['total']; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <p class="mb-1">Próximos</p>
                    <h3><?php echo $stats['proximos']; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                    <p class="mb-1">Ativos</p>
                    <h3><?php echo $stats['ativos']; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                    <p class="mb-1">Inativos</p>
                    <h3><?php echo $stats['inativos']; ?></h3>
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
                               placeholder="Tema, palestrante ou descrição..." 
                               value="<?php echo htmlspecialchars($busca); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Filtrar por:</label>
                        <select name="filtro" class="form-select">
                            <option value="todos" <?php echo $filtro === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="proximos" <?php echo $filtro === 'proximos' ? 'selected' : ''; ?>>Próximos</option>
                            <option value="passados" <?php echo $filtro === 'passados' ? 'selected' : ''; ?>>Passados</option>
                            <option value="ativos" <?php echo $filtro === 'ativos' ? 'selected' : ''; ?>>Apenas Ativos</option>
                            <option value="inativos" <?php echo $filtro === 'inativos' ? 'selected' : ''; ?>>Apenas Inativos</option>
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

        <!-- Lista de Webinars -->
        <div class="row">
            <?php if (empty($webinars)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <h4>Nenhum webinar encontrado</h4>
                    <p>Comece criando seu primeiro webinar!</p>
                </div>
            </div>
            <?php else: ?>
                <?php foreach ($webinars as $webinar): 
                    $dataWebinar = strtotime($webinar['data_hora']);
                    $agora = time();
                    $ehProximo = $dataWebinar >= $agora && $webinar['ativo'];
                    $ehPassado = $dataWebinar < $agora;
                    $classStatus = $ehProximo ? 'proximo' : ($ehPassado ? 'passado' : '');
                ?>
                <div class="col-md-6 mb-4">
                    <div class="card webinar-card <?php echo !$webinar['ativo'] ? 'inativo' : $classStatus; ?> h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">
                                        <?php echo htmlspecialchars($webinar['tema']); ?>
                                    </h5>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-user"></i> 
                                        <?php echo htmlspecialchars($webinar['palestrante']); ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?php echo $webinar['ativo'] ? 'success' : 'secondary'; ?> d-block mb-1">
                                        <?php echo $webinar['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                    <?php if ($ehProximo): ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock"></i> Próximo
                                    </span>
                                    <?php elseif ($ehPassado): ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-history"></i> Realizado
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex align-items-center text-primary mb-2">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    <strong><?php echo date('d/m/Y', $dataWebinar); ?></strong>
                                    <i class="fas fa-clock ms-3 me-2"></i>
                                    <strong><?php echo date('H:i', $dataWebinar); ?></strong>
                                </div>
                                
                                <?php if ($webinar['link']): ?>
                                <div class="d-flex align-items-center text-success">
                                    <i class="fas fa-link me-2"></i>
                                    <a href="<?php echo htmlspecialchars($webinar['link']); ?>" 
                                       target="_blank" class="text-decoration-none">
                                        Link de acesso
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($webinar['descricao']): ?>
                            <p class="card-text text-muted">
                                <?php 
                                $descricao = htmlspecialchars($webinar['descricao']);
                                echo strlen($descricao) > 150 ? substr($descricao, 0, 150) . '...' : $descricao; 
                                ?>
                            </p>
                            <?php endif; ?>

                            <div class="btn-group w-100 mt-3" role="group">
                                <button class="btn btn-outline-primary btn-sm" 
                                        onclick="editarWebinar(<?php echo htmlspecialchars(json_encode($webinar)); ?>)">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                
                                <button class="btn btn-outline-info btn-sm" 
                                        onclick="verDetalhes(<?php echo htmlspecialchars(json_encode($webinar)); ?>)">
                                    <i class="fas fa-eye"></i> Ver
                                </button>
                                
                                <?php if ($webinar['ativo']): ?>
                                <button class="btn btn-outline-warning btn-sm" 
                                        onclick="confirmarAcao(<?php echo $webinar['id_webinar']; ?>, 'inativar')">
                                    <i class="fas fa-ban"></i> Inativar
                                </button>
                                <?php else: ?>
                                <button class="btn btn-outline-success btn-sm" 
                                        onclick="confirmarAcao(<?php echo $webinar['id_webinar']; ?>, 'ativar')">
                                    <i class="fas fa-check"></i> Ativar
                                </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="confirmarAcao(<?php echo $webinar['id_webinar']; ?>, 'excluir')">
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

    <!-- Modal Criar/Editar Webinar -->
    <div class="modal fade" id="modalWebinar" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalWebinarTitle">Novo Webinar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formWebinar">
                    <div class="modal-body">
                        <input type="hidden" name="acao" id="acao" value="criar">
                        <input type="hidden" name="id_webinar" id="id_webinar">
                        
                        <div class="mb-3">
                            <label class="form-label">Tema do Webinar *</label>
                            <input type="text" class="form-control" name="tema" id="tema" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data *</label>
                                <input type="date" class="form-control" name="data" id="data" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Horário *</label>
                                <input type="time" class="form-control" name="hora" id="hora" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Palestrante *</label>
                            <input type="text" class="form-control" name="palestrante" id="palestrante" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Link de Acesso</label>
                            <input type="url" class="form-control" name="link" id="link" 
                                   placeholder="https://exemplo.com/webinar">
                            <small class="text-muted">Link da plataforma onde o webinar será realizado (Zoom, Teams, etc.)</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea class="form-control" name="descricao" id="descricao" rows="5" 
                                      placeholder="Descreva o conteúdo do webinar, tópicos que serão abordados, público-alvo, etc."></textarea>
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
        <input type="hidden" name="id_webinar" id="idWebinarAcao">
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Processar data/hora antes de enviar o formulário
        document.getElementById('formWebinar').addEventListener('submit', function(e) {
            const data = document.getElementById('data').value;
            const hora = document.getElementById('hora').value;
            
            if (data && hora) {
                const dataHora = data + ' ' + hora + ':00';
                
                // Criar campo oculto para data_hora
                let inputDataHora = document.getElementById('data_hora_hidden');
                if (!inputDataHora) {
                    inputDataHora = document.createElement('input');
                    inputDataHora.type = 'hidden';
                    inputDataHora.name = 'data_hora';
                    inputDataHora.id = 'data_hora_hidden';
                    this.appendChild(inputDataHora);
                }
                inputDataHora.value = dataHora;
            }
        });

        function editarWebinar(webinar) {
            document.getElementById('modalWebinarTitle').textContent = 'Editar Webinar';
            document.getElementById('acao').value = 'editar';
            document.getElementById('id_webinar').value = webinar.id_webinar;
            document.getElementById('tema').value = webinar.tema || '';
            document.getElementById('palestrante').value = webinar.palestrante || '';
            document.getElementById('link').value = webinar.link || '';
            document.getElementById('descricao').value = webinar.descricao || '';
            
            // Separar data e hora
            if (webinar.data_hora) {
                const dataHora = new Date(webinar.data_hora);
                const ano = dataHora.getFullYear();
                const mes = String(dataHora.getMonth() + 1).padStart(2, '0');
                const dia = String(dataHora.getDate()).padStart(2, '0');
                const horas = String(dataHora.getHours()).padStart(2, '0');
                const minutos = String(dataHora.getMinutes()).padStart(2, '0');
                
                document.getElementById('data').value = `${ano}-${mes}-${dia}`;
                document.getElementById('hora').value = `${horas}:${minutos}`;
            }
            
            new bootstrap.Modal(document.getElementById('modalWebinar')).show();
        }

        function verDetalhes(webinar) {
            const dataHora = new Date(webinar.data_hora);
            const agora = new Date();
            const ehProximo = dataHora >= agora && webinar.ativo;
            const ehPassado = dataHora < agora;

            let statusBadge = '';
            if (ehProximo) {
                statusBadge = '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Próximo</span>';
            } else if (ehPassado) {
                statusBadge = '<span class="badge bg-danger"><i class="fas fa-history"></i> Realizado</span>';
            }

            let html = `
                <div class="row mb-3">
                    <div class="col-md-8">
                        <h4>${webinar.tema}</h4>
                        <p class="text-muted"><i class="fas fa-user"></i> ${webinar.palestrante}</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-${webinar.ativo ? 'success' : 'secondary'} mb-2">
                            ${webinar.ativo ? 'Ativo' : 'Inativo'}
                        </span><br>
                        ${statusBadge}
                    </div>
                </div>

                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-6">
                                <i class="fas fa-calendar-alt fa-2x text-primary mb-2"></i>
                                <h5>${dataHora.toLocaleDateString('pt-BR', { 
                                    weekday: 'long', 
                                    year: 'numeric', 
                                    month: 'long', 
                                    day: 'numeric' 
                                })}</h5>
                            </div>
                            <div class="col-md-6">
                                <i class="fas fa-clock fa-2x text-primary mb-2"></i>
                                <h5>${dataHora.toLocaleTimeString('pt-BR', { 
                                    hour: '2-digit', 
                                    minute: '2-digit' 
                                })}</h5>
                            </div>
                        </div>
                    </div>
                </div>

                ${webinar.link ? `
                <div class="alert alert-success">
                    <i class="fas fa-link"></i> <strong>Link de Acesso:</strong><br>
                    <a href="${webinar.link}" target="_blank" class="text-decoration-none">
                        ${webinar.link}
                    </a>
                </div>` : ''}

                ${webinar.descricao ? `
                <div class="mb-3">
                    <h6><i class="fas fa-align-left"></i> Descrição:</h6>
                    <p>${webinar.descricao.replace(/\n/g, '<br>')}</p>
                </div>` : ''}

                <hr>

                <div class="row text-center">
                    <div class="col-md-12">
                        <small class="text-muted">Cadastrado em</small><br>
                        <strong>${new Date(webinar.data_cadastro).toLocaleDateString('pt-BR')} às 
                        ${new Date(webinar.data_cadastro).toLocaleTimeString('pt-BR')}</strong>
                    </div>
                </div>
            `;

            document.getElementById('detalhesTitulo').textContent = webinar.tema;
            document.getElementById('detalhesConteudo').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalDetalhes')).show();
        }

        function confirmarAcao(id, acao) {
            const mensagens = {
                'inativar': 'Tem certeza que deseja INATIVAR este webinar?',
                'ativar': 'Tem certeza que deseja ATIVAR este webinar?',
                'excluir': 'Tem certeza que deseja EXCLUIR este webinar? Esta ação não pode ser desfeita!'
            };

            if (confirm(mensagens[acao])) {
                document.getElementById('acaoForm').value = acao;
                document.getElementById('idWebinarAcao').value = id;
                document.getElementById('formAcao').submit();
            }
        }

        // Limpar form ao abrir modal para novo webinar
        document.getElementById('modalWebinar').addEventListener('hidden.bs.modal', function() {
            document.getElementById('formWebinar').reset();
            document.getElementById('modalWebinarTitle').textContent = 'Novo Webinar';
            document.getElementById('acao').value = 'criar';
            document.getElementById('id_webinar').value = '';
            
            // Remover campo oculto data_hora se existir
            const hiddenInput = document.getElementById('data_hora_hidden');
            if (hiddenInput) {
                hiddenInput.remove();
            }
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