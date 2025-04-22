<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include("conexion.php");

if (!isset($_POST['cod_socio'], $_POST['numero_serie_ingresado'], $_POST['numero_serie_correcto'], 
          $_POST['id_medidor'], $_POST['imagen_medidor'], $_POST['fecha'], $_POST['consumo'], $_POST['monto'], $_POST['observaciones'])) {
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit();
}

$codSocio = $_POST['cod_socio'];
$numeroSerieIngresado = $_POST['numero_serie_ingresado'];
$numeroSerieCorrecto = $_POST['numero_serie_correcto'];

$idMedidor = $_POST['id_medidor'];
$imagenMedidor = $_POST['imagen_medidor'];
$fecha = $_POST['fecha'];
$consumo = $_POST['consumo'];
$monto = $_POST['monto'];
$observaciones = $_POST['observaciones'];

if ($numeroSerieIngresado != $numeroSerieCorrecto) {
    echo json_encode(["success" => false, "message" => "Número de serie incorrecto"]);
    exit();
}

if ($idMedidor == "0") {
    $query = "SELECT m.id_medidor FROM medidor m 
              INNER JOIN socio s ON s.id_socio = m.id_socio 
              WHERE s.cod_socio = :codSocio";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':codSocio', $codSocio, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->bindColumn(1, $idMedidor);
    $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$idMedidor) {
        echo json_encode(["success" => false, "message" => "Medidor no encontrado"]);
        exit();
    }
}

$consumo = (int) $consumo;
$monto = (float) $monto;

$sql = "INSERT INTO consumo_registro (id_medidor, imagen_medidor, fecha, consumo, monto, observaciones)
        VALUES (:idMedidor, :imagenMedidor, :fecha, :consumo, :monto, :observaciones)";
$stmt = $conn->prepare($sql);

$stmt->bindParam(':idMedidor', $idMedidor, PDO::PARAM_INT);
$stmt->bindParam(':imagenMedidor', $imagenMedidor, PDO::PARAM_STR);
$stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
$stmt->bindParam(':consumo', $consumo, PDO::PARAM_STR); 
$stmt->bindParam(':monto', $monto, PDO::PARAM_STR);
$stmt->bindParam(':observaciones', $observaciones, PDO::PARAM_STR);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Consumo registrado"]);
} else {
    echo json_encode(["success" => false, "message" => "Error al insertar"]);
}
?>
