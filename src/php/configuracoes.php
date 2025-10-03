<?php
// configuracoes.php
session_start();

include('verificar_permissoes.php');
include('../php/conexao.php');

// Verificar se tem permissão para acessar esta página
if (!verificarLogin()) {
    header("Location: ../php/index.php");
    exit();
}

// Apenas Admin pode acessar configurações
if (!podeAcessar('configuracoes_sistema')) {
    header("Location: acesso_negado.php");
    exit();
}

$pdo = conectar();
$usuario = getUsuarioLogado();

// Processar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'alterar_senha':
            $senha_atual = $_POST['senha_atual'] ?? '';
            $senha_nova = $_POST['senha_nova'] ?? '';
            $senha_confirma = $_POST['senha_confirma'] ?? '';
            
            if ($senha_nova !== $senha_confirma) {
                $erro = "As senhas não coincidem!";
            } else {
                try {
                    // Buscar senha atual
                    $stmt = $pdo->prepare("SELECT senha FROM Funcionario WHERE id_funcionario = ?");
                    $stmt->execute([$usuario['id_funcionario']]);
                    $dados = $stmt->fetch();
                    
                    if (password_verify($senha_atual, $dados['senha'])) {
                        $nova_senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE Funcionario SET senha = ? WHERE id_funcionario = ?");
                        $stmt->execute([$nova_senha_hash, $usuario['id_funcionario']]);
                        $sucesso = "Senha alterada com sucesso!";
                    } else {
                        $erro = "Senha atual incorreta!";
                    }
                } catch (Exception $e) {
                    $erro = "Erro ao alterar senha: " . $e->getMessage();
                }
            }
            break;
            
        case 'atualizar_perfil':
            $nome = $_POST['nome'] ?? '';
            $email = $_POST['email'] ?? '';
            
            try {
                $stmt = $pdo->prepare("UPDATE Funcionario SET nome_completo = ?, email = ? WHERE id_funcionario = ?");
                $stmt->execute([$nome, $email, $usuario['id_funcionario']]);
                $sucesso = "Perfil atualizado com sucesso!";
                
                // Atualizar sessão
                $_SESSION['usuario']['nome'] = $nome;
                $_SESSION['usuario']['email'] = $email;
                $usuario = getUsuarioLogado();
            } catch (Exception $e) {
                $erro = "Erro ao atualizar perfil: " . $e->getMessage();
            }
            break;
            
        case 'limpar_logs':
            try {
                // Exemplo: limpar logs antigos (se existir tabela de logs)
                $stmt = $pdo->prepare("DELETE FROM Logs WHERE data_log < DATEADD(day, -30, GETDATE())");
                $stmt->execute();
                $sucesso = "Logs antigos limpos com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao limpar logs: " . $e->getMessage();
            }
            break;
    }
}

// Buscar estatísticas do sistema
try {
    $stats = [];
    
    // Total de usuários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Usuario");
    $stats['total_usuarios'] = $stmt->fetch()['total'];
    
    // Total de funcionários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Funcionario");
    $stats['total_funcionarios'] = $stmt->fetch()['total'];
    
    // Usuários ativos hoje
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Usuario WHERE CAST(ultimo_acesso AS DATE) = CAST(GETDATE() AS DATE)");
    $stats['usuarios_ativos_hoje'] = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    $erro = "Erro ao buscar estatísticas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Sistema de Gestão</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .config-section {
            border-left: 4px solid #0d6efd;
            transition: all 0.3s;
        }
        .config-section:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-box h3 {
            font-size: 2.5rem;
            margin: 0;
        }
        .stat-box p {
            margin: 0;
            opacity: 0.9;
        }
        .icon-box {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
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
                        <h2><i class="fas fa-cog"></i> Configurações do Sistema</h2>
                        <p class="text-muted">
                            Logado como: <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                            - <?php echo $usuario['nivel_nome']; ?>
                        </p>
                    </div>
                    <div>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
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

        <div class="row">
            <!-- Coluna Esquerda -->
            <div class="col-md-8">
                <!-- Meu Perfil -->
                <div class="card config-section mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user"></i> Meu Perfil</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="acao" value="atualizar_perfil">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nome Completo:</label>
                                    <input type="text" class="form-control" name="nome" 
                                           value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email:</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nível de Acesso:</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo $usuario['nivel_nome']; ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ID do Funcionário:</label>
                                    <input type="text" class="form-control" 
                                           value="#<?php echo $usuario['id_funcionario']; ?>" readonly>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Alterações
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Alterar Senha -->
                <div class="card config-section mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-key"></i> Alterar Senha</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="acao" value="alterar_senha">
                            <div class="mb-3">
                                <label class="form-label">Senha Atual:</label>
                                <input type="password" class="form-control" name="senha_atual" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nova Senha:</label>
                                    <input type="password" class="form-control" name="senha_nova" 
                                           required minlength="6" id="senha_nova">
                                    <small class="text-muted">Mínimo 6 caracteres</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirmar Nova Senha:</label>
                                    <input type="password" class="form-control" name="senha_confirma" 
                                           required minlength="6" id="senha_confirma">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-lock"></i> Alterar Senha
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Manutenção do Sistema -->
                <div class="card config-section mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-tools"></i> Manutenção do Sistema</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Atenção:</strong> As ações abaixo podem afetar o funcionamento do sistema.
                        </div>
                        
                        <form method="POST" onsubmit="return confirm('Tem certeza que deseja limpar os logs antigos (mais de 30 dias)?');">
                            <input type="hidden" name="acao" value="limpar_logs">
                            <div class="d-flex justify-content-between align-items-center p-3 border rounded mb-3">
                                <div>
                                    <h6 class="mb-1"><i class="fas fa-broom"></i> Limpar Logs Antigos</h6>
                                    <small class="text-muted">Remove logs com mais de 30 dias</small>
                                </div>
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> Limpar
                                </button>
                            </div>
                        </form>

                        <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                            <div>
                                <h6 class="mb-1"><i class="fas fa-database"></i> Backup do Banco</h6>
                                <small class="text-muted">Criar backup manual do banco de dados</small>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" disabled>
                                <i class="fas fa-download"></i> Em breve
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita -->
            <div class="col-md-4">
                <!-- Estatísticas -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Estatísticas</h5>
                    </div>
                    <div class="card-body">
                        <div class="stat-box mb-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-1">Total de Usuários</p>
                                    <h3><?php echo $stats['total_usuarios'] ?? 0; ?></h3>
                                </div>
                                <div class="icon-box">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>

                        <div class="stat-box mb-3" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-1">Total de Funcionários</p>
                                    <h3><?php echo $stats['total_funcionarios'] ?? 0; ?></h3>
                                </div>
                                <div class="icon-box">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            </div>
                        </div>

                        <div class="stat-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-1">Usuários Ativos Hoje</p>
                                    <h3><?php echo $stats['usuarios_ativos_hoje'] ?? 0; ?></h3>
                                </div>
                                <div class="icon-box">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informações do Sistema -->
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informações</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Versão do Sistema:</small>
                            <p class="mb-0"><strong>1.0.0</strong></p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Último Acesso:</small>
                            <p class="mb-0">
                                <strong>
                                    <?php 
                                    if ($usuario['ultimo_acesso']) {
                                        echo date('d/m/Y H:i', strtotime($usuario['ultimo_acesso']));
                                    } else {
                                        echo 'Este é seu primeiro acesso';
                                    }
                                    ?>
                                </strong>
                            </p>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Data de Cadastro:</small>
                            <p class="mb-0">
                                <strong>
                                    <?php echo date('d/m/Y', strtotime($usuario['data_cadastro'])); ?>
                                </strong>
                            </p>
                        </div>
                        <div>
                            <small class="text-muted">Status da Conta:</small>
                            <p class="mb-0">
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle"></i> Ativa
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Atalhos Rápidos -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Atalhos Rápidos</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="gerenciar_usuarios.php" class="btn btn-outline-primary">
                                <i class="fas fa-users"></i> Gerenciar Usuários
                            </a>
                            <a href="gerenciar_funcionarios.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-tie"></i> Gerenciar Funcionários
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-chart-bar"></i> Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validar senhas iguais
        document.getElementById('senha_confirma').addEventListener('input', function() {
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