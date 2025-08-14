<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require_once "config/db.php";

// Obtener todas las incidencias
$incidencias = $pdo->query("
    SELECT i.id_incidencia, i.titulo, i.descripcion, i.fecha_ocurrida,
           p.nombre AS provincia, m.nombre AS municipio, b.nombre AS barrio,
           i.tipo, i.muertos, i.heridos, i.perdida_rd, i.link_red, i.foto_url
    FROM incidencia i
    LEFT JOIN provincia p ON i.provincia_id = p.id_provincia
    LEFT JOIN municipio m ON i.municipio_id = m.id_municipio
    LEFT JOIN barrio b ON i.barrio_id = b.id_barrio
    ORDER BY i.fecha_ocurrida DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ver Incidencias</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-4">
    <h1>Lista de Incidencias</h1>
    <a href="registrar-incide.php" class="btn btn-primary mb-3">Registrar Nueva Incidencia</a>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Título</th>
                <th>Tipo</th>
                <th>Fecha</th>
                <th>Ubicación</th>
                <th>Muertos</th>
                <th>Heridos</th>
                <th>Pérdida (RD$)</th>
                <th>Foto</th>
                <th>Detalles</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($incidencias as $inc): ?>
            <tr>
                <td><?= htmlspecialchars($inc['titulo']) ?></td>
                <td><?= htmlspecialchars($inc['tipo']) ?></td>
                <td><?= htmlspecialchars($inc['fecha_ocurrida']) ?></td>
                <td><?= htmlspecialchars($inc['provincia'] . ', ' . $inc['municipio'] . ', ' . $inc['barrio']) ?></td>
                <td><?= $inc['muertos'] ?></td>
                <td><?= $inc['heridos'] ?></td>
                <td><?= number_format($inc['perdida_rd'], 2) ?></td>
                <td>
                    <?php if ($inc['foto_url']): ?>
                        <img src="<?= htmlspecialchars($inc['foto_url']) ?>" alt="Foto" width="80">
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modal<?= $inc['id_incidencia'] ?>">Ver</button>

                    <!-- Modal -->
                    <div class="modal fade" id="modal<?= $inc['id_incidencia'] ?>" tabindex="-1">
                      <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title"><?= htmlspecialchars($inc['titulo']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body">
                            <p><strong>Descripción:</strong> <?= htmlspecialchars($inc['descripcion']) ?></p>
                            <p><strong>Tipo:</strong> <?= htmlspecialchars($inc['tipo']) ?></p>
                            <p><strong>Fecha:</strong> <?= htmlspecialchars($inc['fecha_ocurrida']) ?></p>
                            <p><strong>Ubicación:</strong> <?= htmlspecialchars($inc['provincia'] . ', ' . $inc['municipio'] . ', ' . $inc['barrio']) ?></p>
                            <p><strong>Muertos:</strong> <?= $inc['muertos'] ?> | <strong>Heridos:</strong> <?= $inc['heridos'] ?></p>
                            <p><strong>Pérdida:</strong> RD$ <?= number_format($inc['perdida_rd'], 2) ?></p>
                            <?php if ($inc['link_red']): ?>
                                <p><strong>Red social:</strong> <a href="<?= htmlspecialchars($inc['link_red']) ?>" target="_blank"><?= htmlspecialchars($inc['link_red']) ?></a></p>
                            <?php endif; ?>
                            <?php if ($inc['foto_url']): ?>
                                <p><img src="<?= htmlspecialchars($inc['foto_url']) ?>" alt="Foto" width="300"></p>
                            <?php endif; ?>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                          </div>
                        </div>
                      </div>
                    </div>

                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
