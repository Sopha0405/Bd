<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'conexion.php';

$cod_socio = intval($_POST['cod_socio'] ?? 0);
$codigo = trim($_POST['codigo'] ?? '');

if (!$cod_socio || !$codigo) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// Verificar OTP válido y no expirado
$stmt = $conn->prepare("SELECT * FROM otp WHERE cod_socio = ? AND codigo = ? AND valido_hasta >= NOW()");
$stmt->execute([$cod_socio, $codigo]);

if ($stmt->rowCount() > 0) {
    // Borrar OTP usado
    $conn->prepare("DELETE FROM otp WHERE cod_socio = ?")->execute([$cod_socio]);
    echo json_encode(['success' => true, 'message' => 'Código verificado correctamente']);
} else {
    echo json_encode(['success' => false, 'error' => 'Código inválido o expirado']);
}
?>
