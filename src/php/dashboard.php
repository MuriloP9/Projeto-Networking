<?php
// dashboard.php
session_start();

// Incluir arquivo de permissões
include('verificar_permissoes.php');

$usuario = getUsuarioLogado();

//Verificar se está logado
if (!verificarLogin()) {
    header("Location: ../php/index.php");
    exit();
}

if ($usuario['tipo'] === 'usuario') {
    header("Location: ../php/index.php");
    exit();
}

// Obter dados do usuário
$menu_items = gerarMenuPermitido();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Gestão</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin: 0.25rem;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .user-info {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .card-stats {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .card-stats:hover {
            transform: translateY(-5px);
        }
        .nivel-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        .nivel-0 { background: #dc3545; color: white; }
        .nivel-1 { background: #fd7e14; color: white; }
        .nivel-2 { background: #198754; color: white; }
        .restricted-content {
            opacity: 0.5;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <!-- Informações do Usuário -->
                    <div class="user-info text-white text-center">
                        <i class="fas fa-user-circle fa-3x mb-2"></i>
                        <h6><?php echo htmlspecialchars($usuario['nome']); ?></h6>
                        <small><?php echo ucfirst($usuario['tipo']); ?></small>
                        <?php if ($usuario['tipo'] === 'funcionario'): ?>
                            <div class="mt-2">
                                <span class="nivel-badge nivel-<?php echo $usuario['nivel_acesso']; ?>">
                                    <?php echo $usuario['nivel_nome']; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Menu -->
                    <ul class="nav flex-column">
                        <?php foreach ($menu_items as $item): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $item['url']; ?>">
                                    <i class="fas <?php echo $item['icone']; ?> me-2"></i>
                                    <?php echo $item['nome']; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        
                        <hr class="text-white">
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Sair
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="badge bg-primary">
                                <?php echo date('d/m/Y H:i'); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Cards de estatísticas -->
                <div class="row">
                    <!-- Card sempre visível -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats bg-primary text-white">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase mb-0">Dashboard</h5>
                                        <span class="h2 font-weight-bold mb-0">Ativo</span>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tachometer-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card apenas para Gerente ou superior -->
                    <?php if (podeAcessar('gerenciar_equipe')): ?>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats bg-warning text-white">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase mb-0">Minha Equipe</h5>
                                        <span class="h2 font-weight-bold mb-0">12</span>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Card apenas para Admin -->
                    <?php if (podeAcessar('gerenciar_usuarios')): ?>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats bg-success text-white">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase mb-0">Total Usuários</h5>
                                        <span class="h2 font-weight-bold mb-0">156</span>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-cog fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Card para todos -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats bg-info text-white">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase mb-0">Meu Perfil</h5>
                                        <span class="h6 font-weight-bold mb-0">Ativo</span>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção de Ações Rápidas -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Ações Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Botões baseados em permissões -->
                                    <?php if (podeAcessar('gerenciar_usuarios')): ?>
                                    <div class="col-md-3 mb-3">
                                        <a href="gerenciar_usuarios.php" class="btn btn-outline-primary btn-lg w-100">
                                            <i class="fas fa-users-cog d-block mb-2"></i>
                                            Gerenciar Usuários
                                        </a>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (podeAcessar('gerenciar_equipe')): ?>
                                    <div class="col-md-3 mb-3">
                                        <a href="gerenciar_equipe.php" class="btn btn-outline-warning btn-lg w-100">
                                            <i class="fas fa-users d-block mb-2"></i>
                                            Minha Equipe
                                        </a>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (podeAcessar('visualizar_relatorios')): ?>
                                    <div class="col-md-3 mb-3">
                                        <a href="relatorios.php" class="btn btn-outline-success btn-lg w-100">
                                            <i class="fas fa-chart-bar d-block mb-2"></i>
                                            Relatórios
                                        </a>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Botão sempre disponível -->
                                    <div class="col-md-3 mb-3">
                                        <a href="perfil.php" class="btn btn-outline-info btn-lg w-100">
                                            <i class="fas fa-user-edit d-block mb-2"></i>
                                            Meu Perfil
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção de Informações Recentes -->
                <div class="row mt-4">
                    <?php if (podeAcessar('visualizar_relatorios')): ?>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <h5 class="mb-0">Atividades Recentes</h5>
                                <small class="text-muted">Últimas 24 horas</small>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-user-plus text-success me-2"></i>
                                            <strong>Novo usuário cadastrado:</strong> João Silva
                                        </div>
                                        <small class="text-muted">2 horas atrás</small>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-edit text-warning me-2"></i>
                                            <strong>Perfil atualizado:</strong> Maria Santos
                                        </div>
                                        <small class="text-muted">5 horas atrás</small>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-chart-line text-info me-2"></i>
                                            <strong>Relatório gerado:</strong> Relatório Mensal
                                        </div>
                                        <small class="text-muted">8 horas atrás</small>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="atividades.php" class="btn btn-sm btn-outline-primary">
                                        Ver todas as atividades
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="col-md-<?php echo podeAcessar('visualizar_relatorios') ? '4' : '12'; ?>">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Suas Informações</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p><strong>Nome:</strong></p>
                                        <p class="text-muted"><?php echo htmlspecialchars($usuario['nome']); ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p><strong>Email:</strong></p>
                                        <p class="text-muted"><?php echo htmlspecialchars($usuario['email']); ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p><strong>Tipo:</strong></p>
                                        <p class="text-muted"><?php echo ucfirst($usuario['tipo']); ?></p>
                                    </div>
                                    <?php if ($usuario['tipo'] === 'funcionario'): ?>
                                    <div class="col-sm-6">
                                        <p><strong>Nível de Acesso:</strong></p>
                                        <p class="text-muted">
                                            <span class="nivel-badge nivel-<?php echo $usuario['nivel_acesso']; ?>">
                                                <?php echo $usuario['nivel_nome']; ?>
                                            </span>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-sm-6">
                                        <p><strong>Último Login:</strong></p>
                                        <p class="text-muted"><?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_login'])); ?></p>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="perfil.php" class="btn btn-sm btn-outline-primary">
                                        Editar Perfil
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <footer class="text-center text-muted mt-5 py-3">
                    <small>&copy; <?php echo date('Y'); ?> Sistema de Gestão. Todos os direitos reservados.</small>
                </footer>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-atualizar a data/hora a cada minuto
        setInterval(function() {
            const agora = new Date();
            const dataHora = agora.toLocaleDateString('pt-BR') + ' ' + 
                           agora.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
            document.querySelector('.badge.bg-primary').textContent = dataHora;
        }, 60000);

        // Adicionar animação aos cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card-stats');
            cards.forEach(function(card, index) {
                card.style.animationDelay = (index * 0.1) + 's';
                card.classList.add('animate__animated', 'animate__fadeInUp');
            });
        });
    </script>

    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate__fadeInUp {
            animation: fadeInUp 0.6s ease-out forwards;
        }
    </style>
</body>
</html>