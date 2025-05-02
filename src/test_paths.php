<?php
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Caminho qrcodes: " . $_SERVER['DOCUMENT_ROOT'] . '/Projeto-Networking/src/qrcodes/<br>';

$testFile = $_SERVER['DOCUMENT_ROOT'] . '/Projeto-Networking/src/qrcodes/qr_74128e26ac04635e07f25dd0bb2f02d1.png';
echo "Arquivo existe? " . (file_exists($testFile) ? 'Sim' : 'Não');
?>