<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'conexion.php';

try {
    $cod_socio = isset($_GET['cod_socio']) ? intval($_GET['cod_socio']) : 0;

    if ($cod_socio === 0) {
        echo json_encode(['success' => false, 'error' => 'Código de socio no proporcionado']);
        exit;
    }

    // Obtener el id_Socio basado en cod_socio
    $stmt = $conn->prepare("SELECT id_Socio FROM socio WHERE cod_socio = ?");
    $stmt->execute([$cod_socio]);
    $socio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$socio) {
        echo json_encode(['success' => false, 'error' => 'Socio no encontrado']);
        exit;
    }

    $id_Socio = $socio['id_Socio'];

    // Buscar el último cupón emitido por fecha
    $stmt = $conn->prepare("
        SELECT id_cupon, id_Socio, codigo, descripcion, fecha_emision, fecha_vencimiento, estado, monto
        FROM cupones
        WHERE id_Socio = ?
        ORDER BY fecha_emision DESC, id_cupon DESC
        LIMIT 1
    ");
    $stmt->execute([$id_Socio]);
    $cupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cupon) {
        echo json_encode([
            'success' => true,
            'cupon' => $cupon
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No se encontraron cupones para este socio'
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error inesperado: ' . $e->getMessage()]);
}
?>
