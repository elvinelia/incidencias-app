<?php
require '../config/db.php';
header('Content-Type: application/json');

try {
    $sql = "
        SELECT i.id, i.titulo, i.fecha, i.lat, i.lng, i.muertos, i.heridos,
               i.perdida_rd, i.link, i.foto,
               GROUP_CONCAT(t.nombre) as tipos
        FROM incidencias i
        JOIN incidencia_tipo it ON it.incidencia_id = i.id
        JOIN tipos t ON t.id = it.tipo_id
        WHERE i.estado = 'publicada'
        AND i.fecha >= (NOW() - INTERVAL 24 HOUR)
        GROUP BY i.id
    ";
    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
