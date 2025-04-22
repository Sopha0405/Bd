<?php
header('Content-Type: application/json');
include 'conexion.php'; 

if (!isset($_POST['cod_socio'])) {
  echo json_encode(["error" => "No se recibió cod_socio"]);
  exit;
}

$codSocio = $_POST['cod_socio'];

$stmt = $conn->prepare("SELECT id_Socio, bloqueado FROM socio WHERE cod_socio = ?");
$stmt->execute([$codSocio]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$socio) {
  echo json_encode(["error" => "Socio no encontrado"]);
  exit;
}

$idSocio = $socio['id_Socio'];
$bloqueado = intval($socio['bloqueado']);

$stmt = $conn->prepare("SELECT id_medidor FROM medidor WHERE id_socio = ?");
$stmt->execute([$idSocio]);
$idMedidor = $stmt->fetchColumn();

if (!$idMedidor) {
  echo json_encode(["error" => "Medidor no encontrado"]);
  exit;
}

$stmtUltimo = $conn->prepare("SELECT fecha FROM consumo_registro WHERE id_medidor = ? ORDER BY fecha DESC LIMIT 1");
$stmtUltimo->execute([$idMedidor]);
$rowUltimo = $stmtUltimo->fetch(PDO::FETCH_ASSOC);
$ultimoRegistro = $rowUltimo ? $rowUltimo['fecha'] : null;

$stmtMes = $conn->prepare("
  SELECT COUNT(*) FROM consumo_registro 
  WHERE id_medidor = ? 
    AND MONTH(fecha) = MONTH(CURDATE()) 
    AND YEAR(fecha) = YEAR(CURDATE())
");
$stmtMes->execute([$idMedidor]);
$tieneConsumoEsteMes = $stmtMes->fetchColumn() > 0;

$pendientes = $tieneConsumoEsteMes ? [] : [$idMedidor];

echo json_encode([
  "bloqueado" => $bloqueado,
  "pendientes" => $pendientes,
  "fecha_ultimo_registro" => $ultimoRegistro
]);
?>
