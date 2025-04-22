<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'conexion.php';

$cod_socio = intval($_POST['cod_socio'] ?? 0);
$contrasena = trim($_POST['contrasena'] ?? '');

if (!$cod_socio || empty($contrasena)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

$contrasena_hashed = password_hash($contrasena, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE socio SET contrasena = ? WHERE cod_socio = ?");
$success = $stmt->execute([$contrasena_hashed, $cod_socio]);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
} else {
    echo json_encode(['success' => false, 'error' => 'No se pudo actualizar la contraseña']);
}
?>