<?php
// cambiar_contrasena.php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'conexion.php';

$cod_socio  = intval($_POST['cod_socio']  ?? 0);
$contrasena = trim($_POST['contrasena']    ?? '');

if (!$cod_socio || empty($contrasena)) {
    echo json_encode([
        'success' => false,
        'error'   => 'Datos incompletos'
    ]);
    exit;
}

// 1) Recuperar información básica del socio (activo, estado, bloqueado)
$sqlCheck = "
    SELECT activo, estado, bloqueado
      FROM socio
     WHERE cod_socio = ?
";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->execute([$cod_socio]);

if ($stmtCheck->rowCount() === 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Socio no encontrado'
    ]);
    exit;
}

$socio = $stmtCheck->fetch(PDO::FETCH_ASSOC);
$activo    = intval($socio['activo']);    // 0 = de baja, 1 = activo
$estado    = intval($socio['estado']);    // 1 = habilitado
$bloqueado = intval($socio['bloqueado']); // 0 = no bloqueado

// 2) Si está de baja (activo == 0), devolvemos un mensaje y no hacemos nada
if ($activo === 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'El usuario está de baja. No puede cambiar la contraseña.'
    ]);
    exit;
}

// 3) Si está bloqueado, tampoco se permite
if ($bloqueado === 1) {
    echo json_encode([
        'success' => false,
        'error'   => 'La cuenta está bloqueada. No se puede cambiar la contraseña.'
    ]);
    exit;
}

// 4) Si no está en estado = 1 (por ejemplo, pendiente o inactivo), no permitimos el cambio
if ($estado !== 1) {
    echo json_encode([
        'success' => false,
        'error'   => 'El socio no está en un estado válido para cambiar la contraseña.'
    ]);
    exit;
}

// 5) Hasta aquí llegamos solo si activo == 1, estado == 1 y bloqueado == 0
//    Procedemos a hashear y actualizar la contraseña:

$contrasena_hashed = password_hash($contrasena, PASSWORD_DEFAULT);

$stmtUpdate = $conn->prepare("
    UPDATE socio
       SET contrasena = ?
     WHERE cod_socio = ?
");
$success = $stmtUpdate->execute([$contrasena_hashed, $cod_socio]);

if ($success) {
    echo json_encode([
        'success' => true,
        'message' => 'Contraseña actualizada correctamente'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error'   => 'No se pudo actualizar la contraseña'
    ]);
}
