<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'])) {
    header("Location: ../php/index.php");
    exit();
}
session_start();

$response = [
    'loggedIn' => false,
    'userName' => ''
];

if (isset($_SESSION['user_id'])) {
    $response['loggedIn'] = true;
    $response['userName'] = $_SESSION['user_name'];
}

echo json_encode($response);
?>