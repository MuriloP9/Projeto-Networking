<?php
session_start();
include("../php/conexao.php");

// Verificar se foi enviado o ID da vaga
if (!isset($_GET['id_vaga']) || !filter_var($_GET['id_vaga'], FILTER_VALIDATE_INT)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID da vaga inválido ou não fornecido']);
    exit;
}

$id_vaga = filter_var($_GET['id_vaga'], FILTER_SANITIZE_NUMBER_INT);

try {
    $pdo = conectar();
    
    // Consulta para buscar os detalhes da vaga
    $stmt = $pdo->prepare("
        SELECT v.*, a.nome_area 
        FROM Vagas v
        LEFT JOIN AreaAtuacao a ON v.id_area = a.id_area
        WHERE v.id_vaga = ?
    ");
    
    $stmt->bindParam(':id_vaga', $id_vaga, PDO::PARAM_INT);
    $stmt->execute();
    $vaga = $stmt->fetch(PDO::FETCH_ASSOC);

     if (!$vaga) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Vaga não encontrada']);
        exit;
    }
    
    // Retorna os dados como JSON
    echo json_encode($vaga);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar vaga: ' . $e->getMessage()]);
}
?>