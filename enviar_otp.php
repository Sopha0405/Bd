<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'conexion.php';

$cod_socio = intval($_POST['cod_socio'] ?? 0);
$telefono = intval($_POST['telefono'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM socio WHERE cod_socio = ? AND telefono = ? AND estado=1");
$stmt->execute([$cod_socio, $telefono]);


if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'error' => 'Código o teléfono incorrectos']);
    exit;
}

$otp = rand(100000, 999999);
$valido_hasta = date("Y-m-d H:i:s", strtotime("+30 minutes"));

$conn->prepare("DELETE FROM otp WHERE cod_socio = ?")->execute([$cod_socio]);
$success = $conn->prepare("INSERT INTO otp (cod_socio, codigo, valido_hasta) VALUES (?, ?, ?)")
                ->execute([$cod_socio, $otp, $valido_hasta]);

if (!$success) {
    echo json_encode(['success' => false, 'error' => 'Error al guardar OTP en la base de datos']);
    exit;
}

$numero_con_codigo = "591" . $telefono;
$apikey = "7603133"; 
$mensaje = urlencode("Tu código de verificación es: $otp");
$url = "https://api.callmebot.com/whatsapp.php?phone=$numero_con_codigo&text=$mensaje&apikey=$apikey";

$response = @file_get_contents($url); 

if ($response !== false && (strpos($response, 'Message successfully sent') !== false || strpos($response, 'Message queued.') !== false)){
    echo json_encode(['success' => true, 'message' => 'OTP enviado por WhatsApp']);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo enviar el mensaje por WhatsApp',
        'raw' => $response
    ]);
}
?>
