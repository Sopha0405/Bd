<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'conexion.php';

$cod_socio = $_POST['cod_socio'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';

if (empty($cod_socio) || empty($telefono) || empty($contrasena)) {
    echo json_encode(["success" => false, "error" => "Faltan datos"]);
    exit;
}

$sql = "
    SELECT s.id_Socio, s.estado, s.bloqueado
    FROM socio s
    INNER JOIN cliente c ON s.id_cliente = c.id_cliente
    WHERE s.cod_socio = ? AND c.telefono = ?
";
$stmt = $conn->prepare($sql);
$stmt->execute([$cod_socio, $telefono]);

if ($stmt->rowCount() === 0) {
    echo json_encode(["success" => false, "error" => "Datos incorrectos"]);
    exit;
}

$socio = $stmt->fetch();
$idSocio = $socio["id_Socio"];
$estado = intval($socio["estado"]);
$bloqueado = intval($socio["bloqueado"]);

if ($estado !== 0) {
    echo json_encode(["success" => false, "error" => "Su cuenta ya está activa."]);
    exit;
}

if ($bloqueado === 1) {
    echo json_encode(["success" => false, "error" => "Su cuenta está bloqueada. Comuníquese con la oficina."]);
    exit;
}

$medidorQuery = $conn->prepare("SELECT id_medidor FROM medidor WHERE id_socio = ?");
$medidorQuery->execute([$idSocio]);
$idMedidor = $medidorQuery->fetchColumn();

if (!$idMedidor) {
    echo json_encode(["success" => false, "error" => "No se encontró el medidor asociado"]);
    exit;
}

$consumoQuery = $conn->prepare("SELECT COUNT(*) FROM consumo_registro WHERE id_medidor = ?");
$consumoQuery->execute([$idMedidor]);
$totalRegistros = $consumoQuery->fetchColumn();

if ($totalRegistros < 3) {
    echo json_encode([
        "success" => false,
        "error" => "Su ingreso como socio es muy reciente. Debe esperar más tiempo para activar la cuenta."
    ]);
    exit;
}

$contrasena_hashed = password_hash($contrasena, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE socio SET contrasena = ?, estado = 1 WHERE cod_socio = ?");
$success = $update->execute([$contrasena_hashed, $cod_socio]);

echo json_encode([
    "success" => $success,
    "mensaje" => $success ? "Cuenta activada correctamente" : "No se pudo activar la cuenta"
]);
?>
