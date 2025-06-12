<?php
header('Content-Type: application/json');
require 'conexion.php';

$cod_socio = isset($_GET['cod_socio']) ? intval($_GET['cod_socio']) : 0;
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

if ($cod_socio === 0) {
    echo json_encode(["error" => "Código de socio no válido"]);
    exit();
}

// Definir nombres de meses en español
$mesesEspanol = [
    1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril", 5 => "Mayo", 6 => "Junio",
    7 => "Julio", 8 => "Agosto", 9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
];

$mesesAbreviados = [
    1 => "Ene", 2 => "Feb", 3 => "Mar", 4 => "Abr", 5 => "May", 6 => "Jun",
    7 => "Jul", 8 => "Ago", 9 => "Sep", 10 => "Oct", 11 => "Nov", 12 => "Dic"
];

try {
    // Obtener historial de consumo del año seleccionado con meses abreviados
    $sqlHistorial = "SELECT MONTH(fecha) as mes, CAST(SUM(consumo) AS DECIMAL(10,2)) as consumo FROM Consumo_registro 
                     INNER JOIN Medidor ON Consumo_registro.id_medidor = Medidor.id_medidor
                     INNER JOIN Socio ON Medidor.id_socio = Socio.id_Socio
                     WHERE Socio.cod_socio = :cod_socio AND YEAR(fecha) = :selectedYear
                     GROUP BY mes
                     ORDER BY mes ASC";
    $stmtHistorial = $conn->prepare($sqlHistorial);
    $stmtHistorial->bindParam(":cod_socio", $cod_socio, PDO::PARAM_INT);
    $stmtHistorial->bindParam(":selectedYear", $selectedYear, PDO::PARAM_INT);
    $stmtHistorial->execute();
    $historial = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);

    // Obtener consumo trimestral con meses completos
    $sqlTrimestral = "SELECT MONTH(fecha) as mes, CAST(SUM(consumo) AS DECIMAL(10,2)) as consumo FROM Consumo_registro 
                      INNER JOIN Medidor ON Consumo_registro.id_medidor = Medidor.id_medidor
                      INNER JOIN Socio ON Medidor.id_socio = Socio.id_Socio
                      WHERE Socio.cod_socio = :cod_socio AND YEAR(fecha) = :selectedYear
                      GROUP BY mes
                      ORDER BY mes DESC LIMIT 3";
    $stmtTrimestral = $conn->prepare($sqlTrimestral);
    $stmtTrimestral->bindParam(":cod_socio", $cod_socio, PDO::PARAM_INT);
    $stmtTrimestral->bindParam(":selectedYear", $selectedYear, PDO::PARAM_INT);
    $stmtTrimestral->execute();
    $trimestral = $stmtTrimestral->fetchAll(PDO::FETCH_ASSOC);

    // Asignar colores para gráfico
    $colors = ['0xFF0000FF', '0xFFFFFF00', '0xFF808080'];
    foreach ($trimestral as $index => &$row) {
        $row['color'] = $colors[$index % count($colors)];
        $row['mes'] = $mesesEspanol[$row['mes']] ?? "Desconocido"; // Nombre completo en español
    }

    // Obtener los tres meses de mayor consumo con nombres completos
    $sqlTop = "SELECT MONTH(fecha) as mes, CAST(MAX(consumo) AS DECIMAL(10,2)) as consumo FROM Consumo_registro 
                INNER JOIN Medidor ON Consumo_registro.id_medidor = Medidor.id_medidor
                INNER JOIN Socio ON Medidor.id_socio = Socio.id_Socio
                WHERE Socio.cod_socio = :cod_socio AND YEAR(fecha) = :selectedYear
                GROUP BY mes
                ORDER BY consumo DESC LIMIT 3";
    $stmtTop = $conn->prepare($sqlTop);
    $stmtTop->bindParam(":cod_socio", $cod_socio, PDO::PARAM_INT);
    $stmtTop->bindParam(":selectedYear", $selectedYear, PDO::PARAM_INT);
    $stmtTop->execute();
    $top = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

    // Convertir número de mes a nombre en español
    foreach ($top as &$row) {
        $row['mes'] = $mesesEspanol[$row['mes']] ?? "Desconocido"; // Nombre completo en español
        $row['consumo'] = floatval($row['consumo']);
    }

    // Convertir historial de meses a abreviaciones
    foreach ($historial as &$row) {
        $row['mes'] = $mesesAbreviados[$row['mes']] ?? "Desconocido"; // Abreviatura en español
        $row['consumo'] = floatval($row['consumo']);
    }

    // Convertir consumo trimestral a número
    foreach ($trimestral as &$row) {
        $row['consumo'] = floatval($row['consumo']);
    }

    echo json_encode([
        "historial" => $historial,
        "trimestral" => $trimestral,
        "top" => $top
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
    exit();
}
?>
