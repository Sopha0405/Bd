<?php
header('Content-Type: application/json');
require 'conexion.php';

$cod_socio = isset($_GET['cod_socio']) ? intval($_GET['cod_socio']) : 0;
$anio_anterior = date('Y') - 1;

if ($cod_socio === 0) {
    echo json_encode(["error" => "Código de socio no válido"]);
    exit();
}

try {
    $sql = "SELECT YEAR(fecha) as anio, MONTH(fecha) as mes, CAST(SUM(consumo) AS DECIMAL(10,2)) as consumo 
            FROM Consumo_registro 
            INNER JOIN Medidor ON Consumo_registro.id_medidor = Medidor.id_medidor
            INNER JOIN Socio ON Medidor.id_socio = Socio.id_Socio
            WHERE Socio.cod_socio = :cod_socio AND YEAR(fecha) <= :anio_anterior
            GROUP BY anio, mes
            ORDER BY anio ASC, mes ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":cod_socio", $cod_socio, PDO::PARAM_INT);
    $stmt->bindParam(":anio_anterior", $anio_anterior, PDO::PARAM_INT);
    $stmt->execute();
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($datos as &$row) {
        $row['consumo'] = floatval($row['consumo']);
    }

    echo json_encode(["historial" => $datos], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
}
?>
