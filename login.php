<?php
// login.php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'conexion.php';

$cod_socio  = trim($_POST['cod_socio']  ?? '');
$contrasena = $_POST['contrasena'] ?? '';

if (empty($cod_socio) || empty($contrasena)) {
    echo json_encode([
        'success' => false,
        'error'   => 'Faltan datos'
    ]);
    exit;
}

// 1) Recuperar estado, bloqueado y activo del socio
$sql = "
    SELECT estado, bloqueado, activo, contrasena
      FROM socio
     WHERE cod_socio = ?
";
$stmt = $conn->prepare($sql);
$stmt->execute([$cod_socio]);

if ($stmt->rowCount() === 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Socio no encontrado'
    ]);
    exit;
}

$socio     = $stmt->fetch(PDO::FETCH_ASSOC);
$estado    = intval($socio['estado']);    // 1 = habilitado
$bloqueado = intval($socio['bloqueado']); // 0 = no bloqueado
$activo    = intval($socio['activo']);    // 1 = activo, 0 = de baja
$hashBd    = $socio['contrasena'];

// 2) Si está de baja (activo == 0), devolvemos mensaje y no verificamos nada
if ($activo === 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Usuario de baja. No puede iniciar sesión.'
    ]);
    exit;
}

// 3) Si está bloqueado, devolvemos mensaje de bloqueo
if ($bloqueado === 1) {
    echo json_encode([
        'success' => false,
        'error'   => 'Cuenta bloqueada. Comuníquese con la oficina.'
    ]);
    exit;
}

// 4) Si no está en estado = 1 (p.ej. pendiente de activación), no permitimos el login
if ($estado !== 1) {
    echo json_encode([
        'success' => false,
        'error'   => 'Cuenta no habilitada. Verifique su estado.'
    ]);
    exit;
}

// 5) Solo aquí llegamos si activo == 1, estado == 1 y bloqueado == 0.
//    Verificamos la contraseña:
if (password_verify($contrasena, $hashBd)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'error'   => 'Contraseña incorrecta'
    ]);
}
