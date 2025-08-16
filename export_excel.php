<?php
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=anexos_contables_" . date('Y-m-d') . ".xls");

require_once "conexionfin.php";

$anio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
$filtroc = isset($_GET['filtroc']) ? $_GET['filtroc'] : '0'; // nuevo

// ==== CONDICIÓN PARA FILTRO DE COMPROBANTE ====
$condicionComprobante = "";
if ($filtroc != "0") {
    $condicionComprobante = " AND JSON_UNQUOTE(JSON_EXTRACT(r.jsondte, '$.identificacion.tipoDte')) = '$filtroc' ";
}

$sql = "
SELECT 
    r.jsondte,
    JSON_UNQUOTE(JSON_EXTRACT(r.jsondte, '$.identificacion.numeroControl')) AS numeroControl,
    JSON_UNQUOTE(JSON_EXTRACT(r.jsondte, '$.identificacion.codigoGeneracion')) AS codigoGeneracion,
    (
        SELECT codigoGeneracion
        FROM respuestadte r2
        INNER JOIN factura f2 ON f2.id = r2.id_factura
        WHERE 
            r2.estado = 'PROCESADO'
            AND r2.selloRecibido IS NOT NULL
            AND TRIM(r2.selloRecibido) <> ''
            AND r2.descripcionMsg = 'RECIBIDO'
            AND YEAR(f2.fechafactura) = YEAR(f.fechafactura)
            AND MONTH(f2.fechafactura) = MONTH(f.fechafactura)
        ORDER BY CAST(JSON_UNQUOTE(JSON_EXTRACT(r2.jsondte, '$.identificacion.codigoGeneracion')) AS UNSIGNED) ASC
        LIMIT 1
    ) AS primer_codigo_generacion,
    (
        SELECT codigoGeneracion
        FROM respuestadte r3
        INNER JOIN factura f3 ON f3.id = r3.id_factura
        WHERE 
            r3.estado = 'PROCESADO'
            AND r3.selloRecibido IS NOT NULL
            AND TRIM(r3.selloRecibido) <> ''
            AND r3.descripcionMsg = 'RECIBIDO'
            AND YEAR(f3.fechafactura) = YEAR(f.fechafactura)
            AND MONTH(f3.fechafactura) = MONTH(f.fechafactura)
        ORDER BY CAST(JSON_UNQUOTE(JSON_EXTRACT(r3.jsondte, '$.identificacion.codigoGeneracion')) AS UNSIGNED) DESC
        LIMIT 1
    ) AS ultimo_codigo_generacion
FROM 
    respuestadte r
INNER JOIN 
    factura f ON f.id = r.id_factura
WHERE 
    r.estado = 'PROCESADO'
    AND r.selloRecibido IS NOT NULL
    AND TRIM(r.selloRecibido) <> ''
    AND r.descripcionMsg = 'RECIBIDO'
    AND YEAR(f.fechafactura) = $anio
    AND MONTH(f.fechafactura) = $mes
    $condicionComprobante
ORDER BY 
    CAST(JSON_UNQUOTE(JSON_EXTRACT(r.jsondte, '$.identificacion.numeroControl')) AS UNSIGNED) ASC
";

$result = $conexion->query($sql);

if (!$result) {
    die("Error en la consulta: " . $conexion->error);
}

echo "<table border='1'>";
echo "<tr>
    <th>FECHA DE EMISIÓN</th>
    <th>CLASE DE DOCUMENTO</th>
    <th>TIPO DE DOCUMENTO</th>
    <th>NÚMERO DE RESOLUCIÓN</th>
    <th>SERIE DE DOCUMENTO</th>
    <th>N° CONTROL INTERNO (DEL)</th>
    <th>N° CONTROL INTERNO (AL)</th>
    <th>N° DOCUMENTO (DEL)</th>
    <th>N° DOCUMENTO (AL)</th>
    <th>N° MAQUINA REGISTRADORA</th>
    <th>VENTAS EXENTAS</th>
    <th>VENTAS INTERNAS EXENTAS NO SUJETAS A PROP.</th>
    <th>VENTAS NO SUJETAS</th>
    <th>VENTAS GRAVADAS LOCALES</th>
    <th>EXPORT. DENTRO ÁREA C.A.</th>
    <th>EXPORT. FUERA ÁREA C.A.</th>
    <th>EXPORT. DE SERVICIOS</th>
    <th>VENTAS ZONAS FRANCAS Y DPA</th>
    <th>VENTAS A CUENTA DE TERCEROS NO DOM.</th>
    <th>TOTAL VENTAS</th>
    <th>TIPO DE OPERACIÓN (Renta)</th>
    <th>TIPO DE INGRESO (Renta)</th>
    <th>NÚMERO DE ANEXO</th>
</tr>";

while ($row = $result->fetch_assoc()) {
    $data = json_decode($row['jsondte'], true);
    $ident = $data['identificacion'] ?? [];
    $resumen = $data['resumen'] ?? [];

    echo "<tr>";
    echo "<td>" . (!empty($ident['fecEmi']) ? date('d/m/Y', strtotime($ident['fecEmi'])) : '') . "</td>";
    echo "<td>4</td>";
    echo "<td>01</td>";
    echo "<td>N/A</td>";
    echo "<td>N/A</td>";
    echo "<td>N/A</td>";
    echo "<td>N/A</td>";
    echo "<td>" . htmlspecialchars($row['primer_codigo_generacion'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($row['ultimo_codigo_generacion'] ?? '') . "</td>";
    echo "<td>0.00</td>";
    echo "<td>0.00</td>";
    echo "<td>0.00</td>";
    echo "<td>0.00</td>";
    echo "<td>" . number_format($resumen['totalGravada'] ?? 0, 2) . "</td>";
    echo "<td>0.00</td>";
    echo "<td>0.00</td>";
    echo "<td>0.00</td>";
    echo "<td>0.00</td>";
    echo "<td>0.00</td>";
    echo "<td>" . number_format($resumen['totalPagar'] ?? 0, 2) . "</td>";
    echo "<td>1</td>";
    echo "<td>3</td>";
    echo "<td></td>";
    echo "</tr>";
}

echo "</table>";
?>