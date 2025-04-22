<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'conexion.php';

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

$cod_socio = intval($_POST['cod_socio'] ?? 0);
$telefono = intval($_POST['telefono'] ?? 0);

if ($cod_socio === 0 || $telefono === 0) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos requeridos']);
    exit;
}

$stmt = $conn->prepare("
    SELECT s.cod_socio, c.telefono 
    FROM socio s 
    INNER JOIN cliente c ON s.id_cliente = c.id_cliente 
    WHERE s.cod_socio = ? AND c.telefono = ? AND s.estado = 1
");
$stmt->execute([$cod_socio, $telefono]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'error' => 'Código o teléfono incorrectos']);
    exit;
}

$otp = strval(rand(100000, 999999)); // Convertir a string para VARCHAR(6)
$valido_hasta = date("Y-m-d H:i:s", strtotime("+30 minutes"));

$conn->prepare("DELETE FROM otp WHERE cod_socio = ?")->execute([$cod_socio]);

$insert = $conn->prepare("INSERT INTO otp (cod_socio, codigo, valido_hasta) VALUES (?, ?, ?)");
$success = $insert->execute([$cod_socio, $otp, $valido_hasta]);

if (!$success) {
    $errorInfo = $insert->errorInfo();
    echo json_encode(['success' => false, 'error' => 'Error al guardar OTP', 'details' => $errorInfo]);
    exit;
}

$numero_con_codigo = "591" . $telefono;
$apikey = "7603133"; 
$mensaje = urlencode("Tu código de verificación es: $otp");
$url = "https://api.callmebot.com/whatsapp.php?phone=$numero_con_codigo&text=$mensaje&apikey=$apikey";

$response = @file_get_contents($url);

if ($response !== false && (strpos($response, 'Message successfully sent') !== false || strpos($response, 'Message queued.') !== false)) {
    echo json_encode(['success' => true, 'message' => 'OTP enviado por WhatsApp']);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo enviar el mensaje por WhatsApp',
        'raw' => $response
    ]);
}
?>
