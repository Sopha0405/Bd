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

// Obtener el id_Socio a partir del cod_socio
$stmt = $conn->prepare("SELECT id_Socio FROM socio WHERE cod_socio = ?");
$stmt->execute([$cod_socio]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'error' => 'Socio no encontrado']);
    exit;
}

$id_Socio = $stmt->fetchColumn();

// Verificar el OTP
$stmt = $conn->prepare("SELECT * FROM otp WHERE id_Socio = ? AND codigo = ? AND valido_hasta >= NOW()");
$stmt->execute([$id_Socio, $codigo]);

if ($stmt->rowCount() > 0) {
    // OTP correcto, eliminarlo
    $conn->prepare("DELETE FROM otp WHERE id_Socio = ?")->execute([$id_Socio]);
    echo json_encode(['success' => true, 'message' => 'Código verificado correctamente']);
} else {
    echo json_encode(['success' => false, 'error' => 'Código inválido o expirado']);
}
?>
