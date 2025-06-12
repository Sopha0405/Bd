<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'conexion.php';

// Forzar modo excepción en caso de error
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cod_socio = intval($_POST['cod_socio'] ?? 0);
$telefono  = intval($_POST['telefono']  ?? 0);

if ($cod_socio === 0 || $telefono === 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Faltan datos requeridos'
    ]);
    exit;
}

// 1) Obtener id_Socio, estado, bloqueado y activo del socio, junto al teléfono
$sql = "
    SELECT 
      s.id_Socio,
      s.estado,
      s.bloqueado,
      s.activo,
      c.telefono
    FROM socio s
    INNER JOIN cliente c ON s.id_cliente = c.id_cliente
    WHERE s.cod_socio = ? 
      AND c.telefono  = ?
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->execute([$cod_socio, $telefono]);

if ($stmt->rowCount() === 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Código o teléfono incorrectos'
    ]);
    exit;
}

$row       = $stmt->fetch(PDO::FETCH_ASSOC);
$id_Socio  = intval($row['id_Socio']);
$estado    = intval($row['estado']);    // Debe ser 1
$bloqueado = intval($row['bloqueado']); // Debe ser 0
$activo    = intval($row['activo']);    // Debe ser 1

// 2) Verificar que el socio esté habilitado (estado=1), no bloqueado (bloqueado=0) y activo(=1)
if ($estado !== 1) {
    echo json_encode([
        'success' => false,
        'error'   => 'Su cuenta no está habilitada para recibir OTP'
    ]);
    exit;
}

if ($bloqueado === 1) {
    echo json_encode([
        'success' => false,
        'error'   => 'Su cuenta está bloqueada'
    ]);
    exit;
}

if ($activo === 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Su cuenta está de baja. No se envía OTP'
    ]);
    exit;
}

// 3) Generar OTP y fecha de expiración
$otp = strval(rand(100000, 999999)); 
$valido_hasta = date("Y-m-d H:i:s", strtotime("+30 minutes"));

// 4) Borrar OTPs anteriores (si existen) para este id_Socio
$conn->prepare("DELETE FROM otp WHERE id_Socio = ?")->execute([$id_Socio]);

// 5) Insertar nuevo registro OTP
$insert = $conn->prepare("
    INSERT INTO otp (id_Socio, codigo, valido_hasta)
    VALUES (?, ?, ?)
");
$success = $insert->execute([$id_Socio, $otp, $valido_hasta]);

if (!$success) {
    $errorInfo = $insert->errorInfo();
    echo json_encode([
        'success' => false,
        'error'   => 'Error al guardar OTP',
        'details' => $errorInfo
    ]);
    exit;
}

// 6) Enviar el OTP por WhatsApp (CallMeBot)
$numero_con_prefijo = "591" . $telefono;
$apikey   = "6594445"; 
$mensaje  = urlencode("Tu código de verificación es: $otp");
$url      = "https://api.callmebot.com/whatsapp.php?phone={$numero_con_prefijo}&text={$mensaje}&apikey={$apikey}";

// Usamos @file_get_contents para capturar la respuesta (puedes cambiarlo a cURL si lo prefieres)
$response = @file_get_contents($url);

if ($response !== false &&
    (strpos($response, 'Message successfully sent') !== false 
     || strpos($response, 'Message queued.') !== false)
) {
    echo json_encode([
        'success' => true,
        'message' => 'OTP enviado por WhatsApp'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error'   => 'No se pudo enviar el mensaje por WhatsApp',
        'raw'     => $response
    ]);
}
