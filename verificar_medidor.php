<?php
header('Content-Type: application/json');
require 'conexion.php';

$cod_socio = isset($_GET['cod_socio']) ? intval($_GET['cod_socio']) : 0;
$idMedidor = isset($_GET['id_medidor']) ? intval($_GET['id_medidor']) : 0;

if ($cod_socio === 0 || $idMedidor === 0) {
    echo json_encode(["error" => "Parámetros incompletos"]);
    exit();
}

try {
    $sql = "
        SELECT COUNT(*) 
        FROM medidor m
        INNER JOIN socio s ON m.id_socio = s.id_Socio
        WHERE s.cod_socio = :cod_socio AND m.id_medidor = :id_medidor
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':cod_socio', $cod_socio, PDO::PARAM_INT);
    $stmt->bindParam(':id_medidor', $idMedidor, PDO::PARAM_INT);
    $stmt->execute();

    $esValido = $stmt->fetchColumn() > 0;

    echo json_encode(["esValido" => $esValido]);

} catch (PDOException $e) {
    echo json_encode(["error" => "Error en la base de datos: " . $e->getMessage()]);
    exit();
}
?>
