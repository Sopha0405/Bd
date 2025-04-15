<?php
header('Content-Type: application/json');
require 'conexion.php';

$cod_socio = isset($_GET['cod_socio']) ? intval($_GET['cod_socio']) : 0;
$idMedidor = isset($_GET['idMedidor']) ? intval($_GET['idMedidor']) : 0;

if ($cod_socio === 0) {
    echo json_encode(["error" => "Código de socio no válido"]);
    exit();
}

try {
    $sqlMedidor = "SELECT MONTH(fecha) as mes, CAST(SUM(consumo) AS DECIMAL(10,2)) as consumo FROM Consumo_registro 
                     INNER JOIN Medidor ON Consumo_registro.id_medidor = Medidor.id_medidor
                     INNER JOIN Socio ON Medidor.id_socio = Socio.id_Socio
                     WHERE Socio.cod_socio = :cod_socio AND  = :selectedYe";
    $stmMedidor = $conn->prepare($sqlMedidor);
    $stmMedidor->bindParam(":cod_socio", $cod_socio, PDO::PARAM_INT);
    $stmMedidor->bindParam(":idMedidor", $idMedidor, PDO::PARAM_INT);
    $stmMedidor->execute();
    $ValidoMedidor = $stmMedidor->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "esValido" => $ValidoMedidor
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
    exit();
}
?>
