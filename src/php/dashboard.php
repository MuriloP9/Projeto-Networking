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

// Conexão com banco de dados usando PDO
include('conexao.php');
$pdo = conectar();

// Buscar estatísticas reais
$stats = array(
    'total_usuarios' => 0,
    'minha_equipe' => 0,
    'atividades_recentes' => array()
);

try {
    // Total de usuários ativos
    $sql_usuarios = "SELECT COUNT(*) as total FROM Usuario WHERE ativo = 1";
    $stmt = $pdo->query($sql_usuarios);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $stats['total_usuarios'] = $row['total'];
    }
    
    // Minha equipe (se for gerente ou superior)
    if (podeAcessar('gerenciar_equipe') && isset($usuario['id_funcionario'])) {
        $sql_equipe = "SELECT COUNT(*) as total FROM Funcionario WHERE ativo = 1 AND criado_por = ?";
        $stmt = $pdo->prepare($sql_equipe);
        $stmt->execute([$usuario['id_funcionario']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stats['minha_equipe'] = $row['total'];
        }
    }
    
    // Atividades recentes (últimas 24 horas)
    if (podeAcessar('visualizar_relatorios')) {
        $sql_atividades = "
            SELECT TOP 5 
                'usuario' as tipo,
                u.nome as descricao,
                u.data_criacao as data_acao,
                'Novo usuário cadastrado' as acao
            FROM Usuario u
            WHERE u.data_criacao >= DATEADD(day, -1, GETDATE())
            
            UNION ALL
            
            SELECT TOP 5
                'vaga' as tipo,
                v.titulo_vaga as descricao,
                v.data_publicacao as data_acao,
                'Nova vaga publicada' as acao
            FROM Vagas v
            WHERE v.data_publicacao >= DATEADD(day, -1, GETDATE())
            
            UNION ALL
            
            SELECT TOP 5
                'webinar' as tipo,
                w.tema as descricao,
                w.data_cadastro as data_acao,
                'Webinar agendado' as acao
            FROM Webinar w
            WHERE w.data_cadastro >= DATEADD(day, -1, GETDATE())
            
            UNION ALL
            
            SELECT TOP 5
                'candidatura' as tipo,
                CONCAT('Candidatura para ', v.titulo_vaga) as descricao,
                c.data_candidatura as data_acao,
                CASE 
                    WHEN c.status = 'Aprovado' THEN 'Candidatura aprovada'
                    WHEN c.status = 'Reprovado' THEN 'Candidatura recusada'
                    ELSE 'Nova candidatura'
                END as acao
            FROM Candidatura c
            INNER JOIN Vagas v ON c.id_vaga = v.id_vaga
            WHERE c.data_candidatura >= DATEADD(day, -1, GETDATE())
            
            ORDER BY data_acao DESC
        ";
        
        $stmt = $pdo->query($sql_atividades);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['atividades_recentes'][] = $row;
        }
    }
    
} catch (PDOException $e) {
    // Log do erro (adapte conforme necessário)
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
}

// Criar menu manualmente com apenas os itens permitidos
$menu_permitido = array(
    array(
        'nome' => 'Dashboard',
        'url' => 'dashboard.php',
        'icone' => 'fa-tachometer-alt',
        'ativo' => true
    ),
    array(
        'nome' => 'Gerenciar Usuários',
        'url' => 'gerenciar_usuarios.php',
        'icone' => 'fa-users-cog',
        'ativo' => false
    ),
    array(
        'nome' => 'Configurações Sistema',
        'url' => 'configuracoes.php',
        'icone' => 'fa-cog',
        'ativo' => false
    ),
    array(
        'nome' => 'Minha Equipe',
        'url' => 'minha_equipe.php',
        'icone' => 'fa-users',
        'ativo' => false
    ),
    array(
        'nome' => 'Aprovações',
        'url' => 'aprovacoes.php',
        'icone' => 'fa-check-circle',
        'ativo' => false
    ),
    array(
        'nome' => 'Funcionários',
        'url' => 'gerenciar_funcionarios.php',
        'icone' => 'fa-user-tie',
        'ativo' => false
    ),
    array(
        'nome' => 'Relatórios Básicos',
        'url' => 'relatorios_basicos.php',
        'icone' => 'fa-chart-bar',
        'ativo' => false
    ),
    array(
        'nome' => 'Vagas',
        'url' => 'vagas.php',
        'icone' => 'fa-briefcase',
        'ativo' => false
    ),
    array(
        'nome' => 'Webinars',
        'url' => 'webinars.php',
        'icone' => 'fa-video',
        'ativo' => false
    ),
    array(
        'nome' => 'Meu Perfil',
        'url' => 'perfil_funcionario.php',
        'icone' => 'fa-user-edit',
        'ativo' => false
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
        
        .mt-2 {
            margin-top: 10px;
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
                    <a class="nav-link <?php echo $item['ativo'] ? 'active' : ''; ?>" href="<?php echo $item['url']; ?>">
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
                    <div class="card-value"><?php echo $stats['minha_equipe']; ?></div>
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
                    <div class="card-value"><?php echo $stats['total_usuarios']; ?></div>
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

                    <!-- Botão sempre disponível - CORRIGIDO -->
                    <a href="perfil_funcionario.php" class="btn">
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
                            <?php 
                            if (!empty($stats['atividades_recentes'])) {
                                foreach ($stats['atividades_recentes'] as $atividade) {
                                    // Definir ícone e cor baseado no tipo
                                    $icone = 'fa-info-circle';
                                    $cor = '#6c757d';
                                    
                                    switch($atividade['tipo']) {
                                        case 'usuario':
                                            $icone = 'fa-user-plus';
                                            $cor = '#28a745';
                                            break;
                                        case 'vaga':
                                            $icone = 'fa-briefcase';
                                            $cor = '#6f42c1';
                                            break;
                                        case 'webinar':
                                            $icone = 'fa-video';
                                            $cor = '#e83e8c';
                                            break;
                                        case 'candidatura':
                                            $icone = 'fa-file-alt';
                                            $cor = '#17a2b8';
                                            break;
                                    }
                                    
                                    // Calcular tempo decorrido
                                    $data_acao = $atividade['data_acao'];
                                    $agora = new DateTime();
                                    
                                    // Converter data_acao para DateTime se for objeto DateTime do SQL Server
                                    if ($data_acao instanceof DateTime) {
                                        $diferenca = $agora->diff($data_acao);
                                    } else {
                                        // Se for string, converter
                                        $data_acao_dt = new DateTime($data_acao->format('Y-m-d H:i:s'));
                                        $diferenca = $agora->diff($data_acao_dt);
                                    }
                                    
                                    if ($diferenca->d > 0) {
                                        $tempo = $diferenca->d . ' dia(s) atrás';
                                    } elseif ($diferenca->h > 0) {
                                        $tempo = $diferenca->h . ' hora(s) atrás';
                                    } elseif ($diferenca->i > 0) {
                                        $tempo = $diferenca->i . ' minuto(s) atrás';
                                    } else {
                                        $tempo = 'Agora';
                                    }
                            ?>
                            <li class="list-group-item">
                                <div>
                                    <i class="fas <?php echo $icone; ?>" style="color: <?php echo $cor; ?>; margin-right: 10px;"></i>
                                    <strong><?php echo htmlspecialchars($atividade['acao']); ?>:</strong> 
                                    <?php echo htmlspecialchars($atividade['descricao']); ?>
                                </div>
                                <small class="text-muted"><?php echo $tempo; ?></small>
                            </li>
                            <?php 
                                }
                            } else {
                            ?>
                            <li class="list-group-item">
                                <div class="text-center text-muted" style="width: 100%; padding: 20px;">
                                    <i class="fas fa-info-circle" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                    <p>Nenhuma atividade recente nas últimas 24 horas</p>
                                </div>
                            </li>
                            <?php } ?>
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
                                        // Tratar se for objeto DateTime
                                        if ($usuario['ultimo_login'] instanceof DateTime) {
                                            echo $usuario['ultimo_login']->format('d/m/Y H:i');
                                        } else {
                                            echo date('d/m/Y H:i', strtotime($usuario['ultimo_login']));
                                        }
                                    } else {
                                        echo 'Primeiro acesso';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <a href="perfil_funcionario.php" class="btn-sm">
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

    <?php
    // Fechar conexão PDO (não é obrigatório, mas é boa prática)
    $pdo = null;
    ?>

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