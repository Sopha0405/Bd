<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'conexion.php';

$cod_socio = $_POST['cod_socio'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';

if (empty($cod_socio) || empty($contrasena)) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos']);
    exit;
}

$sql = "SELECT * FROM socio WHERE cod_socio = ? AND estado = 1";
$stmt = $conn->prepare($sql);
$stmt->execute([$cod_socio]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'error' => 'Socio no encontrado o no activado']);
    exit;
}

$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if (password_verify($contrasena, $socio['contrasena'])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta']);
}
?>
