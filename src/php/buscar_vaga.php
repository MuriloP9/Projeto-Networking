<?php
session_start();
include("../php/conexao.php");

// Verificar se foi enviado o ID da vaga
if (!isset($_GET['id_vaga'])) {
    echo json_encode(['error' => 'ID da vaga não fornecido']);
    exit;
}

$id_vaga = $_GET['id_vaga'];

try {
    $pdo = conectar();
    
    // Consulta para buscar os detalhes da vaga
    $stmt = $pdo->prepare("
        SELECT v.*, a.nome_area 
        FROM Vagas v
        LEFT JOIN AreaAtuacao a ON v.id_area = a.id_area
        WHERE v.id_vaga = ?
    ");
    
    $stmt->execute([$id_vaga]);
    $vaga = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vaga) {
        echo json_encode(['error' => 'Vaga não encontrada']);
        exit;
    }
    
    // Retorna os dados como JSON
    echo json_encode($vaga);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar vaga: ' . $e->getMessage()]);
}
?>