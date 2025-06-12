<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

if (!isset($_GET['cod_socio'])) {
    echo json_encode(["error" => "Falta el parámetro cod_socio"]);
    exit;
}

$cod_socio = intval($_GET['cod_socio']);

try {
    $sql = "
        SELECT c.id_cupon, c.codigo, c.descripcion, c.fecha_emision, c.fecha_vencimiento, c.estado, c.monto
        FROM cupones c
        INNER JOIN socio s ON s.id_Socio = c.id_Socio
        WHERE s.cod_socio = :cod_socio
        AND c.estado = 'activo'
        AND c.fecha_vencimiento >= CURDATE()
        ORDER BY c.fecha_emision DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":cod_socio", $cod_socio, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $cupones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["cupones" => $cupones]);
    } else {
        echo json_encode(["cupones" => []]); 
    }

} catch (PDOException $e) {
    echo json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
}
?>
