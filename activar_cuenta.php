<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'conexion.php';

$cod_socio = $_POST['cod_socio'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';

if (empty($cod_socio) || empty($telefono) || empty($contrasena)) {
    echo json_encode(["success" => false, "error" => "Faltan datos"]);
    exit;
}

$socioQuery = $conn->prepare("SELECT id_Socio, estado FROM socio WHERE cod_socio = ? AND telefono = ?");
$socioQuery->execute([$cod_socio, $telefono]);

if ($socioQuery->rowCount() === 0) {
    echo json_encode(["success" => false, "error" => "Datos incorrectos"]);
    exit;
}

$socio = $socioQuery->fetch();
$idSocio = $socio["id_Socio"];
$estado = $socio["estado"];

if ($estado == 1) {
    echo json_encode(["success" => false, "error" => "Su cuenta ya está activa"]);
    exit;
}

$medidorQuery = $conn->prepare("SELECT id_medidor FROM medidor WHERE id_socio = ?");
$medidorQuery->execute([$idSocio]);

$idMedidor = $medidorQuery->fetchColumn();

if (!$idMedidor) {
    echo json_encode(["success" => false, "error" => "No se encontró el medidor asociado"]);
    exit;
}

$checkConsumo = $conn->prepare("SELECT COUNT(*) FROM consumo_registro WHERE id_medidor = ?");
$checkConsumo->execute([$idMedidor]);

if ($checkConsumo->fetchColumn() < 3) {
    echo json_encode(["success" => false, "error" => "Su ingreso como socio es muy reciente. Debe esperar más tiempo."]);
    exit;
}

$contrasena_hashed = password_hash($contrasena, PASSWORD_DEFAULT);

$update = $conn->prepare("UPDATE socio SET contrasena = ?, estado = 1 WHERE cod_socio = ?");
$success = $update->execute([$contrasena_hashed, $cod_socio]);

echo json_encode(["success" => $success]);
?>
