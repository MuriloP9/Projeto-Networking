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

// Criar menu manualmente com apenas os itens permitidos
$menu_permitido = array(
    array(
        'nome' => 'Dashboard',
        'url' => 'dashboard.php',
        'icone' => 'fa-tachometer-alt'
    ),
    array(
        'nome' => 'Gerenciar Usuários',
        'url' => 'gerenciar_usuarios.php',
        'icone' => 'fa-users-cog'
    ),
    array(
        'nome' => 'Configurações Sistema',
        'url' => 'configuracoes.php',
        'icone' => 'fa-cog'
    ),
    array(
        'nome' => 'Minha Equipe',
        'url' => 'minha_equipe.php',
        'icone' => 'fa-users'
    ),
    array(
        'nome' => 'Aprovações',
        'url' => 'aprovacoes.php',
        'icone' => 'fa-check-circle'
    ),
    array(
        'nome' => 'Funcionários',
        'url' => 'gerenciar_funcionarios.php',
        'icone' => 'fa-user-tie'
    ),
    array(
        'nome' => 'Relatórios Básicos',
        'url' => 'relatorios_basicos.php',
        'icone' => 'fa-chart-bar'
    ),
    array(
        'nome' => 'Vagas',
        'url' => 'vagas.php',
        'icone' => 'fa-briefcase'
    ),
    array(
        'nome' => 'Webinars',
        'url' => 'webinars.php',
        'icone' => 'fa-video'
    ),
    array(
        'nome' => 'Meu Perfil',
        'url' => 'perfil.php',
        'icone' => 'fa-user-edit'
    )
);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Gestão</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fb;
            color: #333;
            display: flex;
        }
        
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px 0;
            overflow-y: auto;
        }
        
        .user-info {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 8px;
            margin: 0 15px 30px;
            text-align: center;
        }
        
        .user-info i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }
        
        .user-info h6 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .user-info small {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .nivel-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }
        
        .nivel-0 { background: #dc3545; color: white; }
        .nivel-1 { background: #fd7e14; color: white; }
        .nivel-2 { background: #198754; color: white; }
        
        .nav {
            list-style: none;
            padding: 0 15px;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 15px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.2);
            margin: 15px 0;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 25px;
        }
        
        .badge {
            background: #007bff;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            transition: transform 0.2s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-stats .content {
            flex: 1;
        }
        
        .card-stats .icon {
            font-size: 32px;
            opacity: 0.8;
        }
        
        .card-title {
            text-transform: uppercase;
            font-size: 14px;
            margin-bottom: 10px;
            color: #6c757d;
        }
        
        .card-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .bg-primary { background: #007bff; color: white; }
        .bg-warning { background: #ffc107; color: #212529; }
        .bg-success { background: #28a745; color: white; }
        .bg-info { background: #17a2b8; color: white; }
        
        .section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .section-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .section-body {
            padding: 20px;
        }
        
        .btn-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            text-align: center;
        }
        
        .btn:hover {
            border-color: #007bff;
            color: #007bff;
        }
        
        .btn i {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .list-group {
            list-style: none;
        }
        
        .list-group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .list-group-item:last-child {
            border-bottom: none;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-item strong {
            display: block;
            margin-bottom: 5px;
            color: #6c757d;
            font-size: 14px;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-3 {
            margin-top: 15px;
        }
        
        .btn-sm {
            display: inline-block;
            padding: 8px 15px;
            border: 1px solid #007bff;
            border-radius: 5px;
            color: #007bff;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-sm:hover {
            background: #007bff;
            color: white;
        }
        
        footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            margin-top: 40px;
        }
        
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
        
        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease-out forwards;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Informações do Usuário -->
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
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
        <ul class="nav">
            <?php foreach ($menu_permitido as $item): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $item['url']; ?>">
                        <i class="fas <?php echo $item['icone']; ?>"></i>
                        <?php echo $item['nome']; ?>
                    </a>
                </li>
            <?php endforeach; ?>
            
            <li class="nav-divider"></li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Sair
                </a>
            </li>
        </ul>
    </div>

    <!-- Main content -->
    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <div class="badge">
                <?php echo date('d/m/Y H:i'); ?>
            </div>
        </div>

        <!-- Cards de estatísticas -->
        <div class="cards-container">
            <!-- Card sempre visível -->
            <div class="card card-stats bg-primary">
                <div class="content">
                    <div class="card-title">Dashboard</div>
                    <div class="card-value">Ativo</div>
                </div>
                <div class="icon">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
            </div>

            <!-- Card apenas para Gerente ou superior -->
            <?php if (podeAcessar('gerenciar_equipe')): ?>
            <div class="card card-stats bg-warning">
                <div class="content">
                    <div class="card-title">Minha Equipe</div>
                    <div class="card-value">12</div>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <?php endif; ?>

            <!-- Card apenas para Admin -->
            <?php if (podeAcessar('gerenciar_usuarios')): ?>
            <div class="card card-stats bg-success">
                <div class="content">
                    <div class="card-title">Total Usuários</div>
                    <div class="card-value">156</div>
                </div>
                <div class="icon">
                    <i class="fas fa-user-cog"></i>
                </div>
            </div>
            <?php endif; ?>

            <!-- Card para todos -->
            <div class="card card-stats bg-info">
                <div class="content">
                    <div class="card-title">Meu Perfil</div>
                    <div class="card-value">Ativo</div>
                </div>
                <div class="icon">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>

        <!-- Seção de Ações Rápidas -->
        <div class="section">
            <div class="section-header">
                <h2>Ações Rápidas</h2>
            </div>
            <div class="section-body">
                <div class="btn-container">
                    <!-- Botões baseados em permissões -->
                    <?php if (podeAcessar('gerenciar_usuarios')): ?>
                    <a href="gerenciar_usuarios.php" class="btn">
                        <i class="fas fa-users-cog"></i>
                        Gerenciar Usuários
                    </a>
                    <?php endif; ?>

                    <?php if (podeAcessar('gerenciar_equipe')): ?>
                    <a href="gerenciar_equipe.php" class="btn">
                        <i class="fas fa-users"></i>
                        Minha Equipe
                    </a>
                    <?php endif; ?>

                    <?php if (podeAcessar('visualizar_relatorios')): ?>
                    <a href="relatorios.php" class="btn">
                        <i class="fas fa-chart-bar"></i>
                        Relatórios
                    </a>
                    <?php endif; ?>

                    <?php if (podeAcessar('gerenciar_vagas')): ?>
                    <a href="vagas.php" class="btn">
                        <i class="fas fa-briefcase"></i>
                        Vagas
                    </a>
                    <?php endif; ?>

                    <?php if (podeAcessar('gerenciar_webinars')): ?>
                    <a href="webinars.php" class="btn">
                        <i class="fas fa-video"></i>
                        Webinars
                    </a>
                    <?php endif; ?>

                    <!-- Botão sempre disponível -->
                    <a href="perfil.php" class="btn">
                        <i class="fas fa-user-edit"></i>
                        Meu Perfil
                    </a>
                </div>
            </div>
        </div>

        <!-- Seção de Informações Recentes -->
        <div style="display: flex; gap: 20px;">
            <?php if (podeAcessar('visualizar_relatorios')): ?>
            <div style="flex: 2;">
                <div class="section">
                    <div class="section-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>Atividades Recentes</h2>
                        <small class="text-muted">Últimas 24 horas</small>
                    </div>
                    <div class="section-body">
                        <ul class="list-group">
                            <li class="list-group-item">
                                <div>
                                    <i class="fas fa-user-plus" style="color: #28a745; margin-right: 10px;"></i>
                                    <strong>Novo usuário cadastrado:</strong> João Silva
                                </div>
                                <small class="text-muted">2 horas atrás</small>
                            </li>
                            <li class="list-group-item">
                                <div>
                                    <i class="fas fa-edit" style="color: #ffc107; margin-right: 10px;"></i>
                                    <strong>Perfil atualizado:</strong> Maria Santos
                                </div>
                                <small class="text-muted">5 horas atrás</small>
                            </li>
                            <li class="list-group-item">
                                <div>
                                    <i class="fas fa-chart-line" style="color: #17a2b8; margin-right: 10px;"></i>
                                    <strong>Relatório gerado:</strong> Relatório Mensal
                                </div>
                                <small class="text-muted">8 horas atrás</small>
                            </li>
                            <li class="list-group-item">
                                <div>
                                    <i class="fas fa-briefcase" style="color: #6f42c1; margin-right: 10px;"></i>
                                    <strong>Nova vaga publicada:</strong> Desenvolvedor PHP
                                </div>
                                <small class="text-muted">10 horas atrás</small>
                            </li>
                            <li class="list-group-item">
                                <div>
                                    <i class="fas fa-video" style="color: #e83e8c; margin-right: 10px;"></i>
                                    <strong>Webinar agendado:</strong> Introdução ao Networking
                                </div>
                                <small class="text-muted">12 horas atrás</small>
                            </li>
                        </ul>
                        <div class="text-center mt-3">
                            <a href="atividades.php" class="btn-sm">
                                Ver todas as atividades
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div style="flex: 1;">
                <div class="section">
                    <div class="section-header">
                        <h2>Suas Informações</h2>
                    </div>
                    <div class="section-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <strong>Nome:</strong>
                                <div class="text-muted"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                            </div>
                            <div class="info-item">
                                <strong>Email:</strong>
                                <div class="text-muted"><?php echo htmlspecialchars($usuario['email']); ?></div>
                            </div>
                            <div class="info-item">
                                <strong>Tipo:</strong>
                                <div class="text-muted"><?php echo ucfirst($usuario['tipo']); ?></div>
                            </div>
                            <?php if ($usuario['tipo'] === 'funcionario'): ?>
                            <div class="info-item">
                                <strong>Nível de Acesso:</strong>
                                <div class="text-muted">
                                    <span class="nivel-badge nivel-<?php echo $usuario['nivel_acesso']; ?>">
                                        <?php echo $usuario['nivel_nome']; ?>
                                    </span>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <strong>Último Login:</strong>
                                <div class="text-muted">
                                    <?php 
                                    if (isset($usuario['ultimo_login']) && !empty($usuario['ultimo_login'])) {
                                        echo date('d/m/Y H:i', strtotime($usuario['ultimo_login']));
                                    } else {
                                        echo 'Primeiro acesso';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <a href="perfil.php" class="btn-sm">
                                Editar Perfil
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer>
            <small>&copy; <?php echo date('Y'); ?> Sistema de Gestão. Todos os direitos reservados.</small>
        </footer>
    </div>

    <script>
        // Auto-atualizar a data/hora a cada minuto
        setInterval(function() {
            const agora = new Date();
            const dataHora = agora.toLocaleDateString('pt-BR') + ' ' + 
                           agora.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
            document.querySelector('.badge').textContent = dataHora;
        }, 60000);

        // Adicionar animação aos cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach(function(card, index) {
                card.style.animationDelay = (index * 0.1) + 's';
                card.classList.add('animate-fadeInUp');
            });
        });
    </script>
</body>
</html>