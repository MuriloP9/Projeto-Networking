<?php
session_start();
include("../php/conexao.php");

$pdo = conectar();

// Verifica se o parâmetro id_vaga foi fornecido e é válido
if (!isset($_GET['id_vaga']) || !is_numeric($_GET['id_vaga'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID da vaga inválido ou não especificado']);
    exit;
}

// Sanitiza o ID da vaga - garantindo que é um número inteiro
$id_vaga = (int)$_GET['id_vaga'];

// Verifica se o ID é maior que 0 (válido)
if ($id_vaga <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID da vaga inválido']);
    exit;
}

try {
    // Prepara a consulta usando parâmetros nomeados para maior clareza
    $stmt = $pdo->prepare("
        SELECT v.*, a.nome_area 
        FROM Vagas v
        LEFT JOIN AreaAtuacao a ON v.id_area = a.id_area
        WHERE v.id_vaga = :id_vaga
    ");
    
    // Executa a consulta com o parâmetro sanitizado
    $stmt->execute([':id_vaga' => $id_vaga]);
    $vaga = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vaga) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Vaga não encontrada']);
        exit;
    }

    // Sanitiza os dados da vaga antes de retornar
    $vagaSanitizada = [
        'id_vaga' => (int)$vaga['id_vaga'],
        'id_funcionario' => isset($vaga['id_funcionario']) ? (int)$vaga['id_funcionario'] : null,
        'titulo_vaga' => htmlspecialchars($vaga['titulo_vaga'], ENT_QUOTES, 'UTF-8'),
        'localizacao' => isset($vaga['localizacao']) ? htmlspecialchars($vaga['localizacao'], ENT_QUOTES, 'UTF-8') : null,
        'tipo_emprego' => htmlspecialchars($vaga['tipo_emprego'], ENT_QUOTES, 'UTF-8'),
        'descricao' => isset($vaga['descricao']) ? htmlspecialchars($vaga['descricao'], ENT_QUOTES, 'UTF-8') : null,
        'id_area' => isset($vaga['id_area']) ? (int)$vaga['id_area'] : null,
        'id_usuario' => isset($vaga['id_usuario']) ? (int)$vaga['id_usuario'] : null,
        'nome_area' => isset($vaga['nome_area']) ? htmlspecialchars($vaga['nome_area'], ENT_QUOTES, 'UTF-8') : null
    ];

    header('Content-Type: application/json');
    echo json_encode($vagaSanitizada);
    
} catch (PDOException $e) {
    // Log do erro (você deveria implementar um sistema de log adequado)
    error_log('Erro ao buscar vaga: ' . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro ao buscar vaga']);
}