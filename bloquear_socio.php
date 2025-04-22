<?php
header('Content-Type: application/json');
include 'conexion.php';

if (!isset($_POST['cod_socio'])) {
    echo json_encode(["success" => false, "error" => "Falta parámetro cod_socio"]);
    exit;
}

$cod_socio = $_POST['cod_socio'];

try {
    $stmt = $conn->prepare("UPDATE socio SET bloqueado = 1, estado = 0 WHERE cod_socio = ?");
    $stmt->execute([$cod_socio]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Socio no encontrado o sin cambios"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
