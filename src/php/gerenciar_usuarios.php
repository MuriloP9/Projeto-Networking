<?php
// gerenciar_usuarios.php
session_start();

include('verificar_permissoes.php');
include('../php/conexao.php');

// Verificar se tem permissão para acessar esta página
if (!verificarLogin()) {
    header("Location: ../php/index.php");
    exit();
}

// Verificar se pode gerenciar usuários (apenas Admin ou Gerente)
$pode_editar = podeAcessar('gerenciar_usuarios');
$pode_visualizar = podeAcessar('visualizar_usuarios');

if (!$pode_visualizar) {
    header("Location: acesso_negado.php");
    exit();
}

$pdo = conectar();
$usuario_logado = getUsuarioLogado();

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pode_editar) {
    $acao = $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'adicionar':
            $nome = $_POST['nome'] ?? '';
            $email = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';
            $dataNascimento = $_POST['dataNascimento'] ?? null;
            $telefone = $_POST['telefone'] ?? null;
            $ip_registro = $_SERVER['REMOTE_ADDR'];
            
            // Criptografar senha como VARBINARY
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $senha_binary = pack('H*', bin2hex($senha_hash));
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO Usuario (nome, email, senha, dataNascimento, telefone, IP_registro) 
                    VALUES (?, ?, CONVERT(VARBINARY(MAX), ?), ?, ?, ?)
                ");
                $stmt->execute([$nome, $email, $senha_hash, $dataNascimento, $telefone, $ip_registro]);
                $sucesso = "Usuário adicionado com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao adicionar usuário: " . $e->getMessage();
            }
            break;
            
        case 'editar':
            $id = (int)($_POST['id'] ?? 0);
            $nome = $_POST['nome'] ?? '';
            $email = $_POST['email'] ?? '';
            $dataNascimento = $_POST['dataNascimento'] ?? null;
            $telefone = $_POST['telefone'] ?? null;
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE Usuario 
                    SET nome = ?, email = ?, dataNascimento = ?, telefone = ? 
                    WHERE id_usuario = ?
                ");
                $stmt->execute([$nome, $email, $dataNascimento, $telefone, $id]);
                $sucesso = "Usuário atualizado com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao atualizar usuário: " . $e->getMessage();
            }
            break;
            
        case 'desativar':
            $id = (int)($_POST['id'] ?? 0);
            try {
                $stmt = $pdo->prepare("UPDATE Usuario SET ativo = 0 WHERE id_usuario = ?");
                $stmt->execute([$id]);
                $sucesso = "Usuário desativado com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao desativar usuário: " . $e->getMessage();
            }
            break;
            
        case 'ativar':
            $id = (int)($_POST['id'] ?? 0);
            try {
                $stmt = $pdo->prepare("UPDATE Usuario SET ativo = 1 WHERE id_usuario = ?");
                $stmt->execute([$id]);
                $sucesso = "Usuário ativado com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao ativar usuário: " . $e->getMessage();
            }
            break;
            
        case 'resetar_senha':
            $id = (int)($_POST['id'] ?? 0);
            $nova_senha = bin2hex(random_bytes(4)); // Gera senha temporária
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("UPDATE Usuario SET senha = CONVERT(VARBINARY(MAX), ?) WHERE id_usuario = ?");
                $stmt->execute([$senha_hash, $id]);
                $sucesso = "Senha resetada com sucesso! Nova senha temporária: <strong>" . $nova_senha . "</strong>";
            } catch (Exception $e) {
                $erro = "Erro ao resetar senha: " . $e->getMessage();
            }
            break;
            
        case 'aceitar_lgpd':
            $id = (int)($_POST['id'] ?? 0);
            try {
                $stmt = $pdo->prepare("UPDATE Usuario SET statusLGPD = 1 WHERE id_usuario = ?");
                $stmt->execute([$id]);
                $sucesso = "Status LGPD atualizado com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao atualizar LGPD: " . $e->getMessage();
            }
            break;
    }
}

// Buscar usuários
$filtro_status = $_GET['status'] ?? 'todos';
$busca = $_GET['busca'] ?? '';

try {
    $sql = "SELECT * FROM Usuario WHERE 1=1";
    $params = [];
    
    if ($filtro_status === 'ativos') {
        $sql .= " AND ativo = 1";
    } elseif ($filtro_status === 'inativos') {
        $sql .= " AND ativo = 0";
    }
    
    if (!empty($busca)) {
        $sql .= " AND (nome LIKE ? OR email LIKE ?)";
        $params[] = "%$busca%";
        $params[] = "%$busca%";
    }
    
    $sql .= " ORDER BY data_criacao DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estatísticas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Usuario");
    $total_usuarios = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Usuario WHERE ativo = 1");
    $usuarios_ativos = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Usuario WHERE statusLGPD = 1");
    $usuarios_lgpd = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    $erro = "Erro ao buscar usuários: " . $e->getMessage();
    $usuarios = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Sistema de Gestão</title>
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
        .stats-card.total { border-color: #0d6efd; }
        .stats-card.ativos { border-color: #198754; }
        .stats-card.inativos { border-color: #dc3545; }
        .stats-card.lgpd { border-color: #0dcaf0; }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .qr-status {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .qr-gerado { background: #198754; }
        .qr-pendente { background: #ffc107; }
        
        .table-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
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
                        <h2><i class="fas fa-users-cog"></i> Gerenciar Usuários</h2>
                        <p class="text-muted">
                            Logado como: <strong><?php echo htmlspecialchars($usuario_logado['nome']); ?></strong>
                            <?php if ($usuario_logado['tipo'] === 'funcionario'): ?>
                            - <?php echo $usuario_logado['nivel_nome']; ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                        <?php if ($pode_editar): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdicionar">
                            <i class="fas fa-user-plus"></i> Adicionar Usuário
                        </button>
                        <?php endif; ?>
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

        <?php if (!$pode_editar): ?>
        <div class="alert alert-warning">
            <i class="fas fa-info-circle"></i> 
            <strong>Modo apenas leitura:</strong> Você pode visualizar os usuários mas não pode editá-los.
        </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card total">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total de Usuários</h6>
                                <h3 class="mb-0"><?php echo $total_usuarios ?? 0; ?></h3>
                            </div>
                            <div class="text-primary" style="font-size: 2rem;">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card ativos">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Usuários Ativos</h6>
                                <h3 class="mb-0"><?php echo $usuarios_ativos ?? 0; ?></h3>
                            </div>
                            <div class="text-success" style="font-size: 2rem;">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card inativos">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Usuários Inativos</h6>
                                <h3 class="mb-0"><?php echo ($total_usuarios - $usuarios_ativos) ?? 0; ?></h3>
                            </div>
                            <div class="text-danger" style="font-size: 2rem;">
                                <i class="fas fa-user-times"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card lgpd">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">LGPD Aceito</h6>
                                <h3 class="mb-0"><?php echo $usuarios_lgpd ?? 0; ?></h3>
                            </div>
                            <div class="text-info" style="font-size: 2rem;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros e Busca -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Buscar:</label>
                        <input type="text" class="form-control" name="busca" 
                               value="<?php echo htmlspecialchars($busca); ?>" 
                               placeholder="Nome ou email...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status:</label>
                        <select class="form-select" name="status">
                            <option value="todos" <?php echo $filtro_status === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="ativos" <?php echo $filtro_status === 'ativos' ? 'selected' : ''; ?>>Ativos</option>
                            <option value="inativos" <?php echo $filtro_status === 'inativos' ? 'selected' : ''; ?>>Inativos</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <a href="gerenciar_usuarios.php" class="btn btn-secondary w-100">
                            <i class="fas fa-redo"></i> Limpar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabela de Usuários -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lista de Usuários</h5>
                <span class="badge bg-primary"><?php echo count($usuarios); ?> usuário(s)</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>Contato</th>
                                <th>QR Code</th>
                                <th>Status</th>
                                <th>LGPD</th>
                                <th>Último Acesso</th>
                                <th>Cadastro</th>
                                <?php if ($pode_editar): ?>
                                <th>Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>Nenhum usuário encontrado</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($usuarios as $user): ?>
                            <tr>
                                <td><?php echo $user['id_usuario']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2">
                                            <?php echo strtoupper(substr($user['nome'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['nome']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($user['telefone']): ?>
                                    <i class="fas fa-phone text-success"></i> <?php echo htmlspecialchars($user['telefone']); ?>
                                    <?php else: ?>
                                    <small class="text-muted">Não informado</small>
                                    <?php endif; ?>
                                    <br>
                                    <?php if ($user['dataNascimento']): ?>
                                    <small><i class="fas fa-birthday-cake"></i> <?php echo date('d/m/Y', strtotime($user['dataNascimento'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['qr_code']): ?>
                                    <span class="qr-gerado"></span> Gerado
                                    <br>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($user['data_geracao_qr'])); ?>
                                    </small>
                                    <?php else: ?>
                                    <span class="qr-pendente"></span> Pendente
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['ativo']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['statusLGPD']): ?>
                                    <span class="badge bg-info">
                                        <i class="fas fa-check"></i> Aceito
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock"></i> Pendente
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($user['ultimo_acesso']) {
                                        echo '<small>' . date('d/m/Y H:i', strtotime($user['ultimo_acesso'])) . '</small>';
                                    } else {
                                        echo '<small class="text-muted">Nunca</small>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <small><?php echo date('d/m/Y', strtotime($user['data_criacao'])); ?></small>
                                    <?php if ($user['IP_registro']): ?>
                                    <br><small class="text-muted">IP: <?php echo $user['IP_registro']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <?php if ($pode_editar): ?>
                                <td>
                                    <div class="btn-group btn-group-sm table-actions">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick='editarUsuario(<?php echo json_encode($user); ?>)'
                                                title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <?php if ($user['ativo']): ?>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="alterarStatus(<?php echo $user['id_usuario']; ?>, '<?php echo htmlspecialchars($user['nome']); ?>', 'desativar')"
                                                title="Desativar">
                                            <i class="fas fa-user-times"></i>
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-outline-success" 
                                                onclick="alterarStatus(<?php echo $user['id_usuario']; ?>, '<?php echo htmlspecialchars($user['nome']); ?>', 'ativar')"
                                                title="Ativar">
                                            <i class="fas fa-user-check"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-outline-warning" 
                                                onclick="resetarSenha(<?php echo $user['id_usuario']; ?>, '<?php echo htmlspecialchars($user['nome']); ?>')"
                                                title="Resetar Senha">
                                            <i class="fas fa-key"></i>
                                        </button>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if ($pode_editar): ?>
    <!-- Modal Adicionar Usuário -->
    <div class="modal fade" id="modalAdicionar" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-user-plus"></i> Adicionar Usuário</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="adicionar">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Completo: *</label>
                                <input type="text" class="form-control" name="nome" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email: *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Senha: *</label>
                                <input type="password" class="form-control" name="senha" required 
                                       minlength="6" placeholder="Mínimo 6 caracteres">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefone:</label>
                                <input type="tel" class="form-control" name="telefone" 
                                       placeholder="(00) 00000-0000">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data de Nascimento:</label>
                                <input type="date" class="form-control" name="dataNascimento">
                            </div>
                        </div>
                        <div class="alert alert-info mb-0">
                            <small><i class="fas fa-info-circle"></i> Os campos marcados com * são obrigatórios.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Adicionar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuário -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Usuário</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="editar">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Completo:</label>
                                <input type="text" class="form-control" name="nome" id="edit_nome" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email:</label>
                                <input type="email" class="form-control" name="email" id="edit_email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefone:</label>
                                <input type="tel" class="form-control" name="telefone" id="edit_telefone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data de Nascimento:</label>
                                <input type="date" class="form-control" name="dataNascimento" id="edit_dataNascimento">
                            </div>
                        </div>
                        <div class="alert alert-warning">
                            <small><i class="fas fa-exclamation-triangle"></i> A senha não será alterada. Use o botão "Resetar Senha" para gerar uma nova senha.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Forms ocultos -->
    <form method="POST" id="formStatus" style="display: none;">
        <input type="hidden" name="acao" id="status_acao">
        <input type="hidden" name="id" id="status_id">
    </form>

    <form method="POST" id="formResetSenha" style="display: none;">
        <input type="hidden" name="acao" value="resetar_senha">
        <input type="hidden" name="id" id="reset_id">
    </form>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($pode_editar): ?>
        function editarUsuario(usuario) {
            document.getElementById('edit_id').value = usuario.id_usuario;
            document.getElementById('edit_nome').value = usuario.nome;
            document.getElementById('edit_email').value = usuario.email;
            document.getElementById('edit_telefone').value = usuario.telefone || '';
            document.getElementById('edit_dataNascimento').value = usuario.dataNascimento || '';
            
            new bootstrap.Modal(document.getElementById('modalEditar')).show();
        }

        function alterarStatus(id, nome, acao) {
            const msg = acao === 'desativar' 
                ? `Tem certeza que deseja DESATIVAR o usuário "${nome}"?\n\nO usuário não poderá mais acessar o sistema.`
                : `Tem certeza que deseja ATIVAR o usuário "${nome}"?`;
            
            if (confirm(msg)) {
                document.getElementById('status_acao').value = acao;
                document.getElementById('status_id').value = id;
                document.getElementById('formStatus').submit();
            }
        }

        function resetarSenha(id, nome) {
            if (confirm(`Resetar a senha do usuário "${nome}"?\n\nUma nova senha temporária será gerada e exibida na tela.`)) {
                document.getElementById('reset_id').value = id;
                document.getElementById('formResetSenha').submit();
            }
        }
        <?php else: ?>
        // Modo apenas leitura
        console.log('Modo apenas leitura ativado');
        <?php endif; ?>

        // Auto-hide alerts após 5 segundos
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('alert-success')) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            });
        }, 5000);
    </script>
</body>
</html>