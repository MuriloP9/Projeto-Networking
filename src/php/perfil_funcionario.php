<?php
// perfil_funcionario.php
session_start();

include('verificar_permissoes.php');
include('../php/conexao.php');

// Verificar se tem permissão para acessar esta página
if (!verificarLogin()) {
    header("Location: ../php/index.php");
    exit();
}

$usuario_logado = getUsuarioLogado();

// CORREÇÃO: Verificar se o usuário tem id_funcionario
if (!isset($usuario_logado['id_funcionario']) || empty($usuario_logado['id_funcionario'])) {
    echo "Erro: Usuário sem ID de funcionário válido.<br>";
    echo "Dados do usuário logado:<br>";
    echo "<pre>";
    print_r($usuario_logado);
    print_r($_SESSION);
    echo "</pre>";
    exit();
}

// Buscar ID do perfil (se não especificado, mostra o próprio perfil)
$id_perfil = isset($_GET['id']) ? (int)$_GET['id'] : (int)$usuario_logado['id_funcionario'];
$pdo = conectar();

// Verificar permissões
$visualiza_proprio = ($id_perfil == $usuario_logado['id_funcionario']);
$pode_editar = $visualiza_proprio || podeAcessar('gerenciar_usuarios');

// Buscar dados do funcionário
try {
    $stmt = $pdo->prepare("
        SELECT 
            f.*,
            criador.nome_completo as criado_por_nome
        FROM Funcionario f
        LEFT JOIN Funcionario criador ON f.criado_por = criador.id_funcionario
        WHERE f.id_funcionario = ?
    ");
    $stmt->execute([$id_perfil]);
    $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$funcionario) {
        echo "Erro: Funcionário não encontrado (ID: $id_perfil)<br>";
        echo "ID procurado: $id_perfil<br>";
        echo "ID do usuário logado: " . $usuario_logado['id_funcionario'] . "<br>";
        echo "<a href='dashboard.php'>Voltar ao Dashboard</a>";
        exit();
    }
    
    // Estatísticas do funcionário
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM Funcionario 
        WHERE criado_por = ? AND ativo = 1
    ");
    $stmt->execute([$id_perfil]);
    $funcionarios_criados = $stmt->fetch()['total'];
    
    // Tempo de conta
    $data_cadastro = new DateTime($funcionario['data_cadastro']);
    $hoje = new DateTime();
    $tempo_conta = $hoje->diff($data_cadastro);
    
} catch (Exception $e) {
    $erro = "Erro ao buscar dados: " . $e->getMessage();
    echo "Erro: " . $e->getMessage() . "<br>";
    echo "SQL State: " . $e->getCode() . "<br>";
    echo "<a href='dashboard.php'>Voltar ao Dashboard</a>";
    exit();
}

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pode_editar) {
    $acao = $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'atualizar_perfil':
            $nome = $_POST['nome'] ?? '';
            $email = $_POST['email'] ?? '';
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE Funcionario 
                    SET nome_completo = ?, email = ? 
                    WHERE id_funcionario = ?
                ");
                $stmt->execute([$nome, $email, $id_perfil]);
                
                // Atualizar sessão se for o próprio usuário
                if ($visualiza_proprio) {
                    $_SESSION['nome_usuario'] = $nome;
                    $_SESSION['email_usuario'] = $email;
                }
                
                $sucesso = "Perfil atualizado com sucesso!";
                
                // Recarregar dados
                $stmt = $pdo->prepare("SELECT * FROM Funcionario WHERE id_funcionario = ?");
                $stmt->execute([$id_perfil]);
                $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (Exception $e) {
                $erro = "Erro ao atualizar perfil: " . $e->getMessage();
            }
            break;
            
        case 'alterar_senha':
            if (!$visualiza_proprio) {
                $erro = "Você só pode alterar sua própria senha!";
                break;
            }
            
            $senha_atual = $_POST['senha_atual'] ?? '';
            $senha_nova = $_POST['senha_nova'] ?? '';
            $senha_confirma = $_POST['senha_confirma'] ?? '';
            
            if ($senha_nova !== $senha_confirma) {
                $erro = "As senhas não coincidem!";
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT senha FROM Funcionario WHERE id_funcionario = ?");
                    $stmt->execute([$id_perfil]);
                    $dados = $stmt->fetch();
                    
                    if (password_verify($senha_atual, $dados['senha'])) {
                        $nova_senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE Funcionario SET senha = ? WHERE id_funcionario = ?");
                        $stmt->execute([$nova_senha_hash, $id_perfil]);
                        $sucesso = "Senha alterada com sucesso!";
                    } else {
                        $erro = "Senha atual incorreta!";
                    }
                } catch (Exception $e) {
                    $erro = "Erro ao alterar senha: " . $e->getMessage();
                }
            }
            break;
    }
}

function getNivelAcessoNome($nivel) {
    switch ($nivel) {
        case 0: return 'Administrador';
        case 1: return 'Gerente';
        case 2: return 'Supervisor';
        default: return 'Desconhecido';
    }
}

function getNivelCor($nivel) {
    switch ($nivel) {
        case 0: return '#dc3545';
        case 1: return '#fd7e14';
        case 2: return '#198754';
        default: return '#6c757d';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - <?php echo htmlspecialchars($funcionario['nome_completo']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            font-weight: bold;
            color: #667eea;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            margin: 0 auto;
        }
        .nivel-badge-large {
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: bold;
            display: inline-block;
            margin-top: 1rem;
        }
        .info-card {
            border-left: 4px solid;
            transition: all 0.3s;
        }
        .info-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .stat-item {
            text-align: center;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .stat-item h3 {
            color: #667eea;
            margin: 0;
            font-size: 2.5rem;
        }
        .stat-item p {
            margin: 0;
            color: #6c757d;
        }
        .activity-item {
            padding: 1rem;
            border-left: 3px solid #667eea;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 0 8px 8px 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Header do Perfil -->
        <div class="profile-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($funcionario['nome_completo'], 0, 2)); ?>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h1 class="mb-2"><?php echo htmlspecialchars($funcionario['nome_completo']); ?></h1>
                        <p class="mb-2">
                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($funcionario['email']); ?>
                        </p>
                        <span class="nivel-badge-large" style="background: <?php echo getNivelCor($funcionario['nivel_acesso']); ?>;">
                            <i class="fas fa-shield-alt"></i> <?php echo getNivelAcessoNome($funcionario['nivel_acesso']); ?>
                        </span>
                        <?php if (!$funcionario['ativo']): ?>
                        <span class="badge bg-danger ms-2">
                            <i class="fas fa-ban"></i> INATIVO
                        </span>
                        <?php endif; ?>
                        <?php if ($visualiza_proprio): ?>
                        <span class="badge bg-info ms-2">
                            <i class="fas fa-user"></i> Seu Perfil
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de Navegação -->
        <div class="mb-4">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
            </a>
            <?php if ($usuario_logado['nivel_acesso'] == 0): ?>
            <a href="gerenciar_funcionarios.php" class="btn btn-outline-primary">
                <i class="fas fa-users"></i> Gerenciar Funcionários
            </a>
            <?php endif; ?>
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

        <div class="row">
            <!-- Coluna Esquerda -->
            <div class="col-md-4">
                <!-- Estatísticas Rápidas -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Estatísticas</h5>
                    </div>
                    <div class="card-body">
                        <div class="stat-item">
                            <h3><?php echo $tempo_conta->days; ?></h3>
                            <p>Dias na plataforma</p>
                        </div>
                        <div class="stat-item">
                            <h3><?php echo $funcionarios_criados; ?></h3>
                            <p>Funcionários cadastrados</p>
                        </div>
                        <div class="stat-item">
                            <h3><?php echo $funcionario['ativo'] ? 'Ativo' : 'Inativo'; ?></h3>
                            <p>Status da conta</p>
                        </div>
                    </div>
                </div>

                <!-- Informações da Conta -->
                <div class="card info-card mb-4" style="border-color: #667eea;">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informações da Conta</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">ID do Funcionário:</small>
                            <p class="mb-0"><strong>#<?php echo $funcionario['id_funcionario']; ?></strong></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Data de Cadastro:</small>
                            <p class="mb-0">
                                <strong><?php echo date('d/m/Y', strtotime($funcionario['data_cadastro'])); ?></strong>
                            </p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Último Acesso:</small>
                            <p class="mb-0">
                                <strong>
                                    <?php 
                                    if ($funcionario['ultimo_acesso']) {
                                        echo date('d/m/Y H:i', strtotime($funcionario['ultimo_acesso']));
                                    } else {
                                        echo 'Nunca acessou';
                                    }
                                    ?>
                                </strong>
                            </p>
                        </div>
                        <?php if ($funcionario['criado_por']): ?>
                        <div class="mb-3">
                            <small class="text-muted">Cadastrado por:</small>
                            <p class="mb-0">
                                <strong><?php echo htmlspecialchars($funcionario['criado_por_nome']); ?></strong>
                            </p>
                        </div>
                        <?php else: ?>
                        <div class="mb-3">
                            <small class="text-muted">Tipo de Conta:</small>
                            <p class="mb-0">
                                <span class="badge bg-success">
                                    <i class="fas fa-crown"></i> Admin Master
                                </span>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Permissões -->
                <div class="card info-card mb-4" style="border-color: #198754;">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-lock"></i> Permissões</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $permissoes = [
                            0 => [
                                'Acesso total ao sistema',
                                'Gerenciar funcionários',
                                'Gerenciar usuários',
                                'Aprovar candidaturas',
                                'Configurações do sistema'
                            ],
                            1 => [
                                'Visualizar dashboard',
                                'Gerenciar usuários',
                                'Aprovar candidaturas',
                                'Visualizar relatórios'
                            ],
                            2 => [
                                'Visualizar dashboard',
                                'Visualizar usuários',
                                'Visualizar candidaturas'
                            ]
                        ];
                        
                        $nivel = $funcionario['nivel_acesso'];
                        ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($permissoes[$nivel] as $perm): ?>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i> <?php echo $perm; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita -->
            <div class="col-md-8">
                <!-- Editar Perfil -->
                <?php if ($pode_editar): ?>
                <div class="card info-card mb-4" style="border-color: #0d6efd;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-edit"></i> Editar Perfil</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="acao" value="atualizar_perfil">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nome Completo:</label>
                                    <input type="text" class="form-control" name="nome" 
                                           value="<?php echo htmlspecialchars($funcionario['nome_completo']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email:</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($funcionario['email']); ?>" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Alterações
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Alterar Senha -->
                <?php if ($visualiza_proprio): ?>
                <div class="card info-card mb-4" style="border-color: #ffc107;">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-key"></i> Alterar Senha</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formSenha">
                            <input type="hidden" name="acao" value="alterar_senha">
                            <div class="mb-3">
                                <label class="form-label">Senha Atual:</label>
                                <input type="password" class="form-control" name="senha_atual" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nova Senha:</label>
                                    <input type="password" class="form-control" name="senha_nova" 
                                           id="senha_nova" required minlength="6">
                                    <small class="text-muted">Mínimo 6 caracteres</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirmar Nova Senha:</label>
                                    <input type="password" class="form-control" name="senha_confirma" 
                                           id="senha_confirma" required minlength="6">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-lock"></i> Alterar Senha
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Atividade Recente -->
                <div class="card info-card" style="border-color: #6c757d;">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Atividade Recente</h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <i class="fas fa-sign-in-alt text-primary"></i>
                                    <strong>Último acesso ao sistema</strong>
                                </div>
                                <small class="text-muted">
                                    <?php 
                                    if ($funcionario['ultimo_acesso']) {
                                        echo date('d/m/Y H:i', strtotime($funcionario['ultimo_acesso']));
                                    } else {
                                        echo 'Primeiro acesso';
                                    }
                                    ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <i class="fas fa-user-plus text-success"></i>
                                    <strong>Conta criada</strong>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y', strtotime($funcionario['data_cadastro'])); ?>
                                </small>
                            </div>
                        </div>

                        <?php if ($funcionarios_criados > 0): ?>
                        <div class="activity-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <i class="fas fa-users text-info"></i>
                                    <strong><?php echo $funcionarios_criados; ?> funcionário(s) cadastrado(s)</strong>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validar senhas iguais
        document.getElementById('senha_confirma')?.addEventListener('input', function() {
            const senha = document.getElementById('senha_nova').value;
            const confirma = this.value;
            
            if (senha !== confirma) {
                this.setCustomValidity('As senhas não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });

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