<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require 'conexion.php';

$datos = json_decode(file_get_contents("php://input"), true);

if (
    !isset($datos["cod_socio"]) || !isset($datos["codigo"]) ||
    !isset($datos["descripcion"]) || !isset($datos["fecha_emision"]) ||
    !isset($datos["fecha_vencimiento"]) || !isset($datos["estado"]) ||
    !isset($datos["monto"])
) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos obligatorios"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id_Socio FROM socio WHERE cod_socio = :cod_socio");
    $stmt->bindParam(":cod_socio", $datos["cod_socio"], PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Socio no encontrado"]);
        exit;
    }

    $idSocio = $stmt->fetchColumn();

    $stmtVerificar = $conn->prepare("SELECT id_cupon FROM cupones WHERE codigo = :codigo");
    $stmtVerificar->bindParam(":codigo", $datos["codigo"]);
    $stmtVerificar->execute();

    if ($stmtVerificar->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(["error" => "El código ya existe"]);
        exit;
    }

    $stmtInsert = $conn->prepare("
        INSERT INTO cupones (id_Socio, codigo, descripcion, fecha_emision, fecha_vencimiento, estado, monto)
        VALUES (:id_Socio, :codigo, :descripcion, :fecha_emision, :fecha_vencimiento, :estado, :monto)
    ");

    $stmtInsert->bindParam(":id_Socio", $idSocio);
    $stmtInsert->bindParam(":codigo", $datos["codigo"]);
    $stmtInsert->bindParam(":descripcion", $datos["descripcion"]);
    $stmtInsert->bindParam(":fecha_emision", $datos["fecha_emision"]);
    $stmtInsert->bindParam(":fecha_vencimiento", $datos["fecha_vencimiento"]);
    $stmtInsert->bindParam(":estado", $datos["estado"]);
    $stmtInsert->bindParam(":monto", $datos["monto"]);

    if ($stmtInsert->execute()) {
        echo json_encode(["mensaje" => "Cupón creado correctamente"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al insertar el cupón"]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en la base de datos: " . $e->getMessage()]);
}
?>
