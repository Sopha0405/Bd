<?php
include 'conexion.php';

$cod_socio = $_POST['cod_socio'] ?? '';
$codigo = $_POST['codigo'] ?? '';
$now = date("Y-m-d H:i:s");

$sql = "SELECT * FROM otp WHERE cod_socio = ? AND codigo = ? AND valido_hasta >= ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$cod_socio, $codigo, $now]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
