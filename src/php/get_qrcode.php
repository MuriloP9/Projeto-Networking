<?php
// get_qrcode.php
header('Content-Type: image/png');

// Caminhos absolutos confirmados pelo teste
$qrDir = 'C:/xampp/htdocs/Projeto-Networking/src/qrcodes/';
$defaultImage = 'C:/xampp/htdocs/Projeto-Networking/assets/img/default_qr.png';

$file = isset($_GET['file']) ? basename($_GET['file']) : '';

// Debug - remova depois de testar
error_log("Buscando QR: ".$qrDir.$file);

if ($file && preg_match('/^qr_[a-f0-9]{32}\.png$/i', $file)) {
    $filePath = $qrDir . $file;
    
    if (file_exists($filePath) && is_readable($filePath)) {
        readfile($filePath);
        exit;
    }
    error_log("Erro: Arquivo não encontrado ou sem permissão: ".$filePath);
}

// Fallback 1: Imagem padrão
if (file_exists($defaultImage) && is_readable($defaultImage)) {
    readfile($defaultImage);
    exit;
}

// Fallback 2: QR Code genérico de erro
header('Content-Type: image/png');
$im = imagecreate(200, 200);
$bg = imagecolorallocate($im, 255, 255, 255);
$textColor = imagecolorallocate($im, 255, 0, 0);
imagestring($im, 5, 20, 100, 'QR Code não encontrado', $textColor);
imagepng($im);
imagedestroy($im);
?>