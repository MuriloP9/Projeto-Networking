<?php
// Função para verificar se o usuário está logado
function verificarLogin() {
    if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
        return false;
    }
    return true;
}

// Função para verificar nível de acesso do funcionário
function verificarNivelAcesso($nivel_minimo_requerido) {
    if (!verificarLogin()) {
        return false;
    }
    
    // Se não for funcionário, permite acesso (usuário normal)
    if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'funcionario') {
        return true;
    }
    
    // Verifica o nível de acesso do funcionário
    $nivel_atual = $_SESSION['nivel_acesso'] ?? 999;
    
    // Níveis mais baixos têm mais permissões (0 = Admin, 1 = Gerente, 2 = Supervisor)
    return $nivel_atual <= $nivel_minimo_requerido;
}

// Função para obter informações do usuário logado
function getUsuarioLogado() {
    if (!verificarLogin()) {
        return null;
    }
    
    return [
        'nome' => $_SESSION['nome_usuario'] ?? '',
        'tipo' => $_SESSION['tipo_usuario'] ?? 'usuario',
        'nivel_acesso' => $_SESSION['nivel_acesso'] ?? null,
        'nivel_nome' => $_SESSION['nivel_acesso_nome'] ?? '',
        'id_usuario' => $_SESSION['id_usuario'] ?? null,
        'id_funcionario' => $_SESSION['id_funcionario'] ?? null,
        'email' => $_SESSION['email_usuario'] ?? ''
    ];
}

// Função para verificar se pode acessar uma funcionalidade específica
function podeAcessar($funcionalidade) {
    if (!verificarLogin()) {
        return false;
    }
    
    // Se não for funcionário, permite acesso total (usuário normal)
    if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'funcionario') {
        return true;
    }
    
    $nivel = $_SESSION['nivel_acesso'] ?? 999;
    
    // Definir permissões por funcionalidade
    $permissoes = [
        'gerenciar_usuarios' => 0,          // Apenas Admin
        'configuracoes_sistema' => 0,       // Apenas Admin  
        'logs_auditoria' => 0,              // Apenas Admin
        'backup_restauracao' => 0,          // Apenas Admin
        'relatorios_gerenciais' => 0,       // Apenas Admin
        'departamentos' => 0,               // Apenas Admin
        
        'relatorios_departamentais' => 1,   // Gerente ou superior
        'gerenciar_equipe' => 1,            // Gerente ou superior
        'aprovacoes' => 1,                  // Gerente ou superior
        
        'relatorios_basicos' => 2,          // Supervisor ou superior
        'visualizar_funcionarios' => 2,     // Supervisor ou superior
        'meu_perfil' => 2,                  // Supervisor ou superior
        
        // Funcionalidades gerais (todos podem acessar)
        'dashboard' => 2,
        'alterar_senha' => 2,
        'sair' => 2
    ];
    
    $nivel_requerido = $permissoes[$funcionalidade] ?? 999;
    
    return $nivel <= $nivel_requerido;
}

// Função para redirecionar se não tiver permissão
function exigirPermissao($funcionalidade, $redirecionar = true) {
    if (!podeAcessar($funcionalidade)) {
        if ($redirecionar) {
            header("Location: ../php/acesso_negado.php");
            exit();
        }
        return false;
    }
    return true;
}

// Função para exigir nível mínimo específico
function exigirNivel($nivel_minimo, $redirecionar = true) {
    if (!verificarNivelAcesso($nivel_minimo)) {
        if ($redirecionar) {
            header("Location: ../php/acesso_negado.php");
            exit();
        }
        return false;
    }
    return true;
}

// Função para gerar menu baseado nas permissões
function gerarMenuPermitido() {
    $usuario = getUsuarioLogado();
    
    if (!$usuario) {
        return [];
    }
    
    // Se não for funcionário, retorna menu completo original
    if ($usuario['tipo'] !== 'funcionario') {
        return [
            ['nome' => 'Dashboard', 'url' => 'dashboard.php', 'icone' => 'fa-home'],
            ['nome' => 'Usuários', 'url' => 'usuarios.php', 'icone' => 'fa-users'],
            ['nome' => 'Relatórios', 'url' => 'relatorios.php', 'icone' => 'fa-chart-bar'],
            ['nome' => 'Configurações', 'url' => 'config.php', 'icone' => 'fa-cog']
        ];
    }
    
    // Menu para funcionários baseado no nível
    $menu = [];
    $nivel = $usuario['nivel_acesso'];
    
    // Itens básicos para todos
    $menu[] = ['nome' => 'Dashboard', 'url' => 'dashboard.php', 'icone' => 'fa-home'];
    
    // Admin (nível 0)
    if ($nivel <= 0) {
        $menu[] = ['nome' => 'Gerenciar Usuários', 'url' => 'gerenciar_usuarios.php', 'icone' => 'fa-users-cog'];
        $menu[] = ['nome' => 'Configurações Sistema', 'url' => 'configuracoes.php', 'icone' => 'fa-cog'];
        $menu[] = ['nome' => 'Logs de Auditoria', 'url' => 'logs.php', 'icone' => 'fa-history'];
        $menu[] = ['nome' => 'Backup', 'url' => 'backup.php', 'icone' => 'fa-download'];
        $menu[] = ['nome' => 'Departamentos', 'url' => 'departamentos.php', 'icone' => 'fa-building'];
    }
    
    // Gerente (nível 1) ou superior
    if ($nivel <= 1) {
        $menu[] = ['nome' => 'Minha Equipe', 'url' => 'equipe.php', 'icone' => 'fa-users'];
        $menu[] = ['nome' => 'Aprovações', 'url' => 'aprovacoes.php', 'icone' => 'fa-check-circle'];
        $menu[] = ['nome' => 'Relatórios Departamentais', 'url' => 'relatorios_dept.php', 'icone' => 'fa-chart-line'];
    }
    
    // Supervisor (nível 2) ou superior
    if ($nivel <= 2) {
        $menu[] = ['nome' => 'Funcionários', 'url' => 'funcionarios.php', 'icone' => 'fa-id-card'];
        $menu[] = ['nome' => 'Relatórios Básicos', 'url' => 'relatorios_basicos.php', 'icone' => 'fa-chart-bar'];
        $menu[] = ['nome' => 'Meu Perfil', 'url' => 'perfil.php', 'icone' => 'fa-user'];
    }
    
    return $menu;
}

// Função para mostrar badge do nível
function getBadgeNivel($nivel) {
    switch($nivel) {
        case 0: return '<span class="badge badge-danger">Administrador</span>';
        case 1: return '<span class="badge badge-warning">Gerente</span>';  
        case 2: return '<span class="badge badge-success">Supervisor</span>';
        default: return '<span class="badge badge-secondary">Funcionário</span>';
    }
}

// Função para verificar se pode editar outro funcionário
function podeEditarFuncionario($id_funcionario_alvo) {
    if (!verificarLogin() || $_SESSION['tipo_usuario'] !== 'funcionario') {
        return false;
    }
    
    $nivel_atual = $_SESSION['nivel_acesso'] ?? 999;
    $id_atual = $_SESSION['id_funcionario'] ?? 0;
    
    // Admin pode editar qualquer um
    if ($nivel_atual <= 0) {
        return true;
    }
    
    // Pode editar a si mesmo
    if ($id_funcionario_alvo == $id_atual) {
        return true;
    }
    
    // Para outros casos, precisaria consultar o banco para verificar o nível do funcionário alvo
    return false;
}
?>