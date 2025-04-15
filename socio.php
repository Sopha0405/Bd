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
        SELECT 
            s.id_Socio, s.nombre, s.a_paterno, s.a_materno, s.cod_socio, s.correo, s.telefono, 
            m.id_medidor, m.numero_serie, m.ubicacion,

            -- 🔹 Consumo total
            (SELECT COALESCE(SUM(c.consumo), 0) FROM consumo_registro c WHERE c.id_medidor = m.id_medidor) AS consumo_total,

            -- 🔹 Importe total
            (SELECT COALESCE(SUM(c.monto), 0.0) FROM consumo_registro c WHERE c.id_medidor = m.id_medidor) AS importe_total,

            -- 🔹 Consumo del mes actual
            (SELECT COALESCE(SUM(c.consumo), 0) 
             FROM consumo_registro c 
             WHERE c.id_medidor = m.id_medidor 
             AND MONTH(c.fecha) = MONTH(NOW()) 
             AND YEAR(c.fecha) = YEAR(NOW())) AS consumo_actual,

            -- 🔹 Importe del mes actual
            (SELECT COALESCE(SUM(c.monto), 0.0) 
             FROM consumo_registro c 
             WHERE c.id_medidor = m.id_medidor 
             AND MONTH(c.fecha) = MONTH(NOW()) 
             AND YEAR(c.fecha) = YEAR(NOW())) AS importe_actual,

            -- 🔹 Consumo del mes anterior
            (SELECT COALESCE(SUM(c.consumo), 0) 
             FROM consumo_registro c 
             WHERE c.id_medidor = m.id_medidor 
             AND MONTH(c.fecha) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) 
             AND YEAR(c.fecha) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))) AS consumo_anterior,

            -- 🔹 Importe del mes anterior
            (SELECT COALESCE(SUM(c.monto), 0.0) 
             FROM consumo_registro c 
             WHERE c.id_medidor = m.id_medidor 
             AND MONTH(c.fecha) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) 
             AND YEAR(c.fecha) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))) AS importe_anterior

        FROM socio s
        LEFT JOIN medidor m ON s.id_Socio = m.id_socio
        WHERE s.cod_socio = :cod_socio
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":cod_socio", $cod_socio, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $socio = $stmt->fetch(PDO::FETCH_ASSOC);

        // Convertir valores a enteros o flotantes según corresponda
        $socio['consumo_total'] = intval($socio['consumo_total']);
        $socio['importe_total'] = floatval($socio['importe_total']);
        $socio['consumo_actual'] = intval($socio['consumo_actual']);
        $socio['importe_actual'] = floatval($socio['importe_actual']);
        $socio['consumo_anterior'] = intval($socio['consumo_anterior']);
        $socio['importe_anterior'] = floatval($socio['importe_anterior']);

        echo json_encode($socio);
    } else {
        echo json_encode(["error" => "Socio no encontrado"]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
}
?>
