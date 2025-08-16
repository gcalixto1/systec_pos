<?php
// ==== CONFIGURAR RANGO DE AÑOS ====
$anio_actual = date("Y");
$mes_actual = date("n");

$anios = range($anio_actual, $anio_actual - 5);

// ==== LISTA DE MESES ====
$meses = [
    1 => "Enero",
    2 => "Febrero",
    3 => "Marzo",
    4 => "Abril",
    5 => "Mayo",
    6 => "Junio",
    7 => "Julio",
    8 => "Agosto",
    9 => "Septiembre",
    10 => "Octubre",
    11 => "Noviembre",
    12 => "Diciembre"
];

// ==== FILTROS DESDE GET O POR DEFECTO ====
$anioFiltro = isset($_GET['anio']) ? (int) $_GET['anio'] : $anio_actual;
$mesFiltro = isset($_GET['mes']) ? (int) $_GET['mes'] : $mes_actual;
$filtroc = isset($_GET['filtroc']) ? $_GET['filtroc'] : '0'; // nuevo

// ==== CONDICIÓN PARA FILTRO DE COMPROBANTE ====
$condicionComprobante = "";
if ($filtroc != "0") {
    $condicionComprobante = " AND JSON_UNQUOTE(JSON_EXTRACT(r.jsondte, '$.identificacion.tipoDte')) = '$filtroc' ";
}
?>

<div class="container-fluid mt-4">
    <h4 class="mb-4">📄 Libro de Ventas Anexo Contable</h4>

    <!-- FILTROS -->
    <form id="filtroForm" method="GET" class="row g-3 mb-3">
        <div class="col-md-2">
            <label for="anio" class="form-label">Año</label>
            <select class="form-control" name="anio" id="anio">
                <?php foreach ($anios as $anio): ?>
                    <option value="<?= $anio ?>" <?= ($anioFiltro == $anio) ? 'selected' : '' ?>>
                        <?= $anio ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label for="mes" class="form-label">Mes</label>
            <select class="form-control" name="mes" id="mes">
                <?php foreach ($meses as $num => $nombre): ?>
                    <option value="<?= $num ?>" <?= ($mesFiltro == $num) ? 'selected' : '' ?>>
                        <?= $nombre ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label for="filtroc" class="form-label">Tipo de Comprobante</label>
            <select class="form-control" name="filtroc" id="filtroc">
                <option value="0" <?= ($filtroc == '0') ? 'selected' : '' ?>>Todos</option>
                <option value="01" <?= ($filtroc == '01') ? 'selected' : '' ?>>Facturas Consumidor Final</option>
                <option value="03" <?= ($filtroc == '03') ? 'selected' : '' ?>>Comprobante de Crédito Fiscal</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">_</label>
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <button type="button" id="btnFiltrar" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
                <div class="col-auto">
                    <button type="button" id="btnExcel" class="btn btn-success w-100">
                        <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                    </button>
                </div>
                <div class="col-auto">
                    <button type="button" id="btnCsv" class="btn btn-warning w-100">
                        <i class="bi bi-filetype-csv"></i> Exportar CSV
                    </button>
                </div>
            </div>
        </div>

    </form>
    <br />
    <table class="table table-bordered table-responsive" id="tablaDocumentos">
        <thead>
            <tr>
                <th>FECHA DE EMISIÓN</th>
                <th>CLASE DOC</th>
                <th>TIPO DOC</th>
                <th>N° RESOLUCIÓN</th>
                <th>SERIE DOC</th>
                <th>N° CONTROL INTERNO (DEL)</th>
                <th>N° CONTROL INTERNO (AL)</th>
                <th>N° DOC (DEL)</th>
                <th>N° DOC (AL)</th>
                <th>N° MÁQUINA</th>
                <th>VENTAS EXENTAS</th>
                <th>VENTAS EXENTAS NO SUJ.</th>
                <th>VENTAS NO SUJETAS</th>
                <th>VENTAS GRAVADAS</th>
                <th>EXPORT. C.A.</th>
                <th>EXPORT. FUERA C.A.</th>
                <th>EXPORT. SERVICIOS</th>
                <th>VENTAS ZONA FRANCA</th>
                <th>VENTAS A TERCEROS</th>
                <th>TOTAL VENTAS</th>
                <th>TIPO OPE. (Renta)</th>
                <th>TIPO ING. (Renta)</th>
                <th>N° ANEXO</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "
                SELECT 
    r.id,
    r.jsondte,
    JSON_UNQUOTE(JSON_EXTRACT(r.jsondte, '$.identificacion.numeroControl')) AS numeroControl,
    JSON_UNQUOTE(JSON_EXTRACT(r.jsondte, '$.identificacion.codigoGeneracion')) AS codigoGeneracion,
    (
        SELECT JSON_UNQUOTE(JSON_EXTRACT(r2.jsondte, '$.identificacion.codigoGeneracion'))
        FROM respuestadte r2
        INNER JOIN factura f2 ON f2.id = r2.id_factura
        WHERE 
            r2.estado = 'PROCESADO'
            AND r2.selloRecibido IS NOT NULL
            AND TRIM(r2.selloRecibido) <> ''
            AND r2.descripcionMsg = 'RECIBIDO'
            AND YEAR(f2.fechafactura) = YEAR(f.fechafactura)
            AND MONTH(f2.fechafactura) = MONTH(f.fechafactura)
        ORDER BY JSON_UNQUOTE(JSON_EXTRACT(r2.jsondte, '$.identificacion.codigoGeneracion')) ASC
        LIMIT 1
    ) AS primer_codigo_generacion,
    (
        SELECT JSON_UNQUOTE(JSON_EXTRACT(r3.jsondte, '$.identificacion.codigoGeneracion'))
        FROM respuestadte r3
        INNER JOIN factura f3 ON f3.id = r3.id_factura
        WHERE 
            r3.estado = 'PROCESADO'
            AND r3.selloRecibido IS NOT NULL
            AND TRIM(r3.selloRecibido) <> ''
            AND r3.descripcionMsg = 'RECIBIDO'
            AND YEAR(f3.fechafactura) = YEAR(f.fechafactura)
            AND MONTH(f3.fechafactura) = MONTH(f.fechafactura)
        ORDER BY JSON_UNQUOTE(JSON_EXTRACT(r3.jsondte, '$.identificacion.codigoGeneracion')) DESC
        LIMIT 1
    ) AS ultimo_codigo_generacion
FROM respuestadte r
INNER JOIN factura f ON f.id = r.id_factura
WHERE 
    r.estado = 'PROCESADO'
    AND r.selloRecibido IS NOT NULL
    AND TRIM(r.selloRecibido) <> ''
    AND r.descripcionMsg = 'RECIBIDO'
    AND YEAR(f.fechafactura) = $anioFiltro
    AND MONTH(f.fechafactura) = $mesFiltro
    $condicionComprobante
    AND JSON_UNQUOTE(JSON_EXTRACT(r.jsondte, '$.identificacion.codigoGeneracion')) NOT IN (
        SELECT codigoGeneracion FROM invalidaciones
    )
ORDER BY CAST(JSON_UNQUOTE(JSON_EXTRACT(r.jsondte, '$.identificacion.numeroControl')) AS UNSIGNED) ASC;

            ";

            $result = $conexion->query($sql);

            if (!$result) {
                die("<b>Error en la consulta:</b> " . $conexion->error);
            }

            while ($row = $result->fetch_assoc()):
                $data = json_decode($row['jsondte'], true);
                $ident = $data['identificacion'] ?? [];
                $resumen = $data['resumen'] ?? [];
                ?>
                <tr>
                    <td><?= !empty($ident['fecEmi']) ? date('d/m/Y', strtotime($ident['fecEmi'])) : '' ?></td>
                    <td>4</td>
                    <td>01</td>
                    <td>N/A</td>
                    <td>N/A</td>
                    <td><?= htmlspecialchars($row['primer_codigo_generacion'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['ultimo_codigo_generacion'] ?? '') ?></td>
                    <td>0.00</td>
                    <td>0.00</td>
                    <td>0.00</td>
                    <td><?= number_format($resumen['ventasExentas'] ?? 0, 2) ?></td>
                    <td>0.00</td>
                    <td>0.00</td>
                    <td><?= number_format($resumen['totalGravada'] ?? 0, 2) ?></td>
                    <td>0.00</td>
                    <td>0.00</td>
                    <td>0.00</td>
                    <td>0.00</td>
                    <td>0.00</td>
                    <td><?= number_format($resumen['totalPagar'] ?? 0, 2) ?></td>
                    <td>1</td>
                    <td>3</td>
                    <td></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</div>

<script>
    $('#tablaDocumentos').dataTable();

    function redirigirFiltro() {
        var anio = document.getElementById('anio').value;
        var mes = document.getElementById('mes').value;
        var filtroc = document.getElementById('filtroc').value;
        window.location.href = "index.php?page=anexos_contables&anio=" + anio + "&mes=" + mes + "&filtroc=" + filtroc;
    }

    // Cambiar año o mes => redirigir
    document.getElementById('anio').addEventListener('change', redirigirFiltro);
    document.getElementById('mes').addEventListener('change', redirigirFiltro);

    // Botón filtrar => redirigir
    document.getElementById('btnFiltrar').addEventListener('click', redirigirFiltro);

    // Exportar Excel con filtro
    document.getElementById('btnExcel').addEventListener('click', function () {
        var anio = document.getElementById('anio').value;
        var mes = document.getElementById('mes').value;
        var filtroc = document.getElementById('filtroc').value;
        window.location.href = "export_excel.php?anio=" + anio + "&mes=" + mes + "&filtroc=" + filtroc;
    });

    // Exportar CSV con filtro
    document.getElementById('btnCsv').addEventListener('click', function () {
        var anio = document.getElementById('anio').value;
        var mes = document.getElementById('mes').value;
        var filtroc = document.getElementById('filtroc').value;
        window.location.href = "export_csv.php?anio=" + anio + "&mes=" + mes + "&filtroc=" + filtroc;
    });
</script>