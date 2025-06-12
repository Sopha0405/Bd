<?php
// activarcuenta.php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'conexion.php';

$cod_socio   = trim($_POST['cod_socio']   ?? '');
$telefono    = trim($_POST['telefono']    ?? '');
$contrasena  = trim($_POST['contrasena']  ?? '');

if (empty($cod_socio) || empty($telefono) || empty($contrasena)) {
    echo json_encode(["success" => false, "error" => "Faltan datos"]);
    exit;
}

// 1) Buscar socio + teléfono
$sql = "
    SELECT s.id_Socio, s.estado, s.bloqueado, s.activo
      FROM socio s
      JOIN cliente c ON s.id_cliente = c.id_cliente
     WHERE s.cod_socio = ?
       AND c.telefono = ?
";
$stmt = $conn->prepare($sql);
$stmt->execute([$cod_socio, $telefono]);

if ($stmt->rowCount() === 0) {
    echo json_encode(["success" => false, "error" => "Datos incorrectos"]);
    exit;
}

$socio = $stmt->fetch(PDO::FETCH_ASSOC);
$idSocio   = intval($socio["id_Socio"]);
$estado    = intval($socio["estado"]);    // 0 = pendiente, 1 = activo, etc.
$bloqueado = intval($socio["bloqueado"]); // 0 o 1
$activo    = intval($socio["activo"]);    // 0 = de baja, 1 = activo

// 2) Si ya estaba activo (activo == 1), no puede volver a activar
if ($activo === 1) {
    echo json_encode(["success" => false, "error" => "Su cuenta ya está activa."]);
    exit;
}

// 3) Si está de baja (activo == 0 y estado == 1 por historial), avisar “de baja”
if ($activo === 0 && $estado === 1) {
    echo json_encode(["success" => false, "error" => "Su cuenta está de baja. Contacte a la oficina."]);
    exit;
}

// 4) Si está bloqueado en la tabla (bloqueado == 1), no puede activar
if ($bloqueado === 1) {
    echo json_encode(["success" => false, "error" => "Su cuenta está bloqueada. Comuníquese con la oficina."]);
    exit;
}

// 5) Verificar que efectivamente esté en “pendiente” (estado = 0) antes de activar.
//    En este punto, sabemos que activo == 0 y estado == 0 (cuenta en espera de activación).
if ($estado !== 0) {
    // Cualquier otro valor distinto de 0 en estado, no corresponde a “pendiente de activación”.
    echo json_encode(["success" => false, "error" => "Su cuenta no está en estado correcto para activar."]);
    exit;
}

// 6) Verificamos que exista un medidor activo para este socio
$medidorQuery = $conn->prepare("
    SELECT id_medidor 
      FROM medidor 
     WHERE id_socio = ? 
       AND estado = 1
");
$medidorQuery->execute([$idSocio]);
$idMedidor = $medidorQuery->fetchColumn();

if (!$idMedidor) {
    echo json_encode(["success" => false, "error" => "No se encontró un medidor activo asociado"]);
    exit;
}

// 7) Contamos registros de consumo para ese medidor
$consumoQuery = $conn->prepare("SELECT COUNT(*) FROM consumo_registro WHERE id_medidor = ?");
$consumoQuery->execute([$idMedidor]);
$totalRegistros = intval($consumoQuery->fetchColumn());

if ($totalRegistros < 3) {
    echo json_encode([
        "success" => false,
        "error"   => "Su ingreso como socio es muy reciente. Debe esperar más tiempo para activar la cuenta."
    ]);
    exit;
}

// 8) Si todo está OK (estado = 0, activo = 0, no bloqueado, medidor y registros >= 3), actualizar la cuenta
$contrasena_hashed = password_hash($contrasena, PASSWORD_DEFAULT);
$update = $conn->prepare("
    UPDATE socio
       SET contrasena = ?,
           estado     = 1,
           bloqueado  = 0,
           activo     = 1
     WHERE cod_socio = ?
");
$success = $update->execute([$contrasena_hashed, $cod_socio]);

echo json_encode([
    "success" => $success,
    "mensaje" => $success ? "Cuenta activada correctamente" : "No se pudo activar la cuenta"
]);
