<?php
session_start();

$error_message = ''; // Inicializa a variável de erro

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtendo dados via POST
    $username = $_POST['usuario']; 
    $password = $_POST['senha']; 

    // Implementação B.D
    $valid_username = 'admin';
    $valid_password = '1234';

    if ($username === $valid_username && $password === $valid_password) {
        // Login bem-sucedido
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        exit;
    } else {
        // Login falhou
        $error_message = "Usuário ou senha inválidos!";
    }
}
?>