<?php
// gerenciar_funcionarios.php
session_start();

include('verificar_permissoes.php');
include('../php/conexao.php');

// Verificar se tem permissão para acessar esta página
//if (!verificarLogin()) {
//    header("Location: ../php/index.php");
  //  exit();
//}

// Verificar se pode gerenciar funcionários (apenas Admin ou para visualizar)
$pode_editar = podeAcessar('gerenciar_usuarios');
$pode_visualizar = podeAcessar('visualizar_funcionarios');

//if (!$pode_visualizar) {
  //  header("Location: acesso_negado.php");
   // exit();
//}

$pdo = conectar();
$usuario = getUsuarioLogado();

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pode_editar) {
    $acao = $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'adicionar':
            $nome = $_POST['nome'] ?? '';
            $email = $_POST['email'] ?? '';
            $senha = password_hash($_POST['senha'] ?? '', PASSWORD_DEFAULT);
            $nivel = (int)($_POST['nivel'] ?? 2);
            $criado_por = $usuario['id_funcionario'];
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO Funcionario (nome_completo, email, senha, nivel_acesso, criado_por) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nome, $email, $senha, $nivel, $criado_por]);
                $sucesso = "Funcionário adicionado com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao adicionar funcionário: " . $e->getMessage();
            }
            break;
            
        case 'editar':
            $id = (int)($_POST['id'] ?? 0);
            $nome = $_POST['nome'] ?? '';
            $email = $_POST['email'] ?? '';
            $nivel = (int)($_POST['nivel'] ?? 2);
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE Funcionario 
                    SET nome_completo = ?, email = ?, nivel_acesso = ? 
                    WHERE id_funcionario = ?
                ");
                $stmt->execute([$nome, $email, $nivel, $id]);
                $sucesso = "Funcionário atualizado com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao atualizar funcionário: " . $e->getMessage();
            }
            break;
            
        case 'desativar':
            $id = (int)($_POST['id'] ?? 0);
            try {
                $stmt = $pdo->prepare("UPDATE Funcionario SET ativo = 0 WHERE id_funcionario = ?");
                $stmt->execute([$id]);
                $sucesso = "Funcionário desativado com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao desativar funcionário: " . $e->getMessage();
            }
            break;
    }
}

// Buscar funcionários
try {
    $sql = "
        SELECT 
            f.*,
            c.nome_completo as criado_por_nome
        FROM Funcionario f
        LEFT JOIN Funcionario c ON f.criado_por = c.id_funcionario
        ORDER BY f.nivel_acesso, f.nome_completo
    ";
    $stmt = $pdo->query($sql);
    $funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $erro = "Erro ao buscar funcionários: " . $e->getMessage();
    $funcionarios = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Funcionários - Sistema de Gestão</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .nivel-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        .nivel-0 { background: #dc3545; color: white; }
        .nivel-1 { background: #fd7e14; color: white; }
        .nivel-2 { background: #198754; color: white; }
        .restricted-section {
            opacity: 0.5;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-users"></i> Gerenciar Funcionários</h2>
                        <p class="text-muted">
                            Logado como: <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong> 
                            <?php if ($usuario['tipo'] === 'funcionario'): ?>
                            - <span class="nivel-badge nivel-<?php echo $usuario['nivel_acesso']; ?>">
                                <?php echo $usuario['nivel_nome']; ?>
                            </span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                        <?php if ($pode_editar): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdicionar">
                            <i class="fas fa-plus"></i> Adicionar Funcionário
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

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

                <!-- Alertas de permissão -->
                <?php if (!$pode_editar): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Modo apenas leitura:</strong> Você pode visualizar os funcionários mas não pode editá-los. 
                    Apenas administradores podem gerenciar funcionários.
                </div>
                <?php endif; ?>

                <!-- Tabela de Funcionários -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Lista de Funcionários</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Nível de Acesso</th>
                                        <th>Status</th>
                                        <th>Criado Por</th>
                                        <th>Último Acesso</th>
                                        <?php if ($pode_editar): ?>
                                        <th>Ações</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($funcionarios as $func): ?>
                                    <tr>
                                        <td><?php echo $func['id_funcionario']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($func['nome_completo']); ?></strong>
                                            <?php if ($func['id_funcionario'] == $usuario['id_funcionario']): ?>
                                            <small class="text-primary">(Você)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($func['email']); ?></td>
                                        <td>
                                            <span class="nivel-badge nivel-<?php echo $func['nivel_acesso']; ?>">
                                                <?php echo getNivelAcessoNome($func['nivel_acesso']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($func['ativo']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                            <?php else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $func['criado_por_nome'] ? htmlspecialchars($func['criado_por_nome']) : 'Sistema'; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($func['ultimo_acesso']) {
                                                echo date('d/m/Y H:i', strtotime($func['ultimo_acesso']));
                                            } else {
                                                echo '<small class="text-muted">Nunca</small>';
                                            }
                                            ?>
                                        </td>
                                        <?php if ($pode_editar): ?>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary btn-sm" 
                                                        onclick="editarFuncionario(<?php echo htmlspecialchars(json_encode($func)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($func['ativo'] && $func['id_funcionario'] != $usuario['id_funcionario']): ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="desativarFuncionario(<?php echo $func['id_funcionario']; ?>, '<?php echo htmlspecialchars($func['nome_completo']); ?>')">
                                                    <i class="fas fa-user-times"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($pode_editar): ?>
    <!-- Modal para adicionar funcionário -->
    <div class="modal fade" id="modalAdicionar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Adicionar Funcionário</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="adicionar">
                        <div class="mb-3">
                            <label class="form-label">Nome Completo:</label>
                            <input type="text" class="form-control" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email:</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha:</label>
                            <input type="password" class="form-control" name="senha" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nível de Acesso:</label>
                            <select class="form-control" name="nivel" required>
                                <option value="2">Supervisor</option>
                                <option value="1">Gerente</option>
                                <option value="0">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar funcionário -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="formEditar">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Funcionário</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="editar">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nome Completo:</label>
                            <input type="text" class="form-control" name="nome" id="edit_nome" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email:</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nível de Acesso:</label>
                            <select class="form-control" name="nivel" id="edit_nivel" required>
                                <option value="2">Supervisor</option>
                                <option value="1">Gerente</option>
                                <option value="0">Administrador</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <small><i class="fas fa-info-circle"></i> A senha não será alterada. Para alterar a senha, o funcionário deve usar a opção "Esqueci minha senha".</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Form oculto para desativar -->
    <form method="POST" id="formDesativar" style="display: none;">
        <input type="hidden" name="acao" value="desativar">
        <input type="hidden" name="id" id="desativar_id">
    </form>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($pode_editar): ?>
        function editarFuncionario(funcionario) {
            document.getElementById('edit_id').value = funcionario.id_funcionario;
            document.getElementById('edit_nome').value = funcionario.nome_completo;
            document.getElementById('edit_email').value = funcionario.email;
            document.getElementById('edit_nivel').value = funcionario.nivel_acesso;
            
            new bootstrap.Modal(document.getElementById('modalEditar')).show();
        }

        function desativarFuncionario(id, nome) {
            if (confirm('Tem certeza que deseja desativar o funcionário "' + nome + '"?\n\nEsta ação impedirá o acesso ao sistema.')) {
                document.getElementById('desativar_id').value = id;
                document.getElementById('formDesativar').submit();
            }
        }
        <?php else: ?>
        // Modo apenas leitura
        console.log('Modo apenas leitura ativado');
        <?php endif; ?>

        // Informações do usuário logado
        const usuarioLogado = <?php echo json_encode($usuario); ?>;
        console.log('Usuário:', usuarioLogado);
    </script>
</body>
</html>