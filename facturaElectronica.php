<?php
require('factura/fpdf/fpdf.php');
require('phpqrcode/qrlib.php');
include('conexionfin.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // Para PHPMailer con Composer

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$codigo = isset($_GET['codigo']) ? $conexion->real_escape_string($_GET['codigo']) : null;
$codigoSE = isset($_GET['codigoSE']) ? $conexion->real_escape_string($_GET['codigoSE']) : null;
// echo "<pre>" . print_r($codigo) . "</pre>";
if (!$id && !$codigo && !$codigoSE) {
    die("Error: Debe proporcionar 'id', 'codigo' o 'codigoSE'.");
}

if ($id) {
    $sql = "SELECT correo_enviado, selloRecibido, jsondte 
            FROM respuestadte 
            INNER JOIN factura ON factura.id = respuestadte.id_factura 
            WHERE id_factura = $id";
} else if ($codigo) {
    $sql = "SELECT correo_enviado, selloRecibido, jsondte 
            FROM respuestadte 
            INNER JOIN notas_credito ON notas_credito.codigoGeneracion = respuestadte.codigoGeneracion 
            WHERE notas_credito.codigoGeneracion = '$codigo'";


} else if ($codigoSE) {
    $sql = "SELECT selloRecibido, jsondte 
            FROM respuestadte 
            WHERE respuestadte.codigoGeneracion = '$codigoSE'";
}
echo "<pre>" . print_r($sql) . "</pre>";
$resultado = $conexion->query($sql);
$row = $resultado->fetch_assoc();
$json = $row['jsondte'];

// Verifica que el JSON sea válido
$data = json_decode($json, true);
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    die("Error al decodificar el JSON");
}

// === Extraer campos importantes ===
$ident = $data['identificacion'];
$emisor = $data['emisor'];
$receptor = $data['receptor'] ?? $data['sujetoExcluido'] ?? null;
$cuerpo = $data['cuerpoDocumento'];
$resumen = $data['resumen'];
$documentosRelacionados = $data['documentoRelacionado'] ?? [];

// Verifica si hay documentos relacionados
if (is_array($documentosRelacionados) && count($documentosRelacionados) > 0) {
    // Por ejemplo, tomamos el primero
    $docRel = $documentosRelacionados[0];
    $tipoDocRel = $docRel['tipoDocumento'] ?? '';
    $numDocRel = $docRel['numeroDocumento'] ?? '';
    $fechaDocRel = $docRel['fechaEmision'] ?? '';
} else {
    $tipoDocRel = '';
    $numDocRel = '';
    $fechaDocRel = '';
}
// === Generar QR ===
$contenidoQR = "https://admin.factura.gob.sv/consultaPublica?ambiente=00&codGen={$ident['codigoGeneracion']}&fechaEmi={$ident['fecEmi']}";
$archivoQR = 'qr_temp.png';
QRcode::png($contenidoQR, $archivoQR, QR_ECLEVEL_H, 4);

// PDF DE FACTURA CONSUMIDOR FINAL
if ($ident['tipoDte'] == '01') {
    // === Obtener nombre del departamento ===
    $sql2 = "SELECT valor FROM departamentos WHERE codigo = '" . $emisor['direccion']['departamento'] . "'";
    $resultado2 = $conexion->query($sql2);
    $row2 = $resultado2->fetch_assoc();

    $sql3 = "SELECT valor FROM municipios WHERE codigo = '" . $emisor['direccion']['municipio'] . "'";
    $resultado3 = $conexion->query($sql3);
    $row3 = $resultado3->fetch_assoc();

    // === Obtener nombre del departamento ===
    $sql4 = "SELECT valor FROM departamentos WHERE codigo = '" . $receptor['direccion']['departamento'] . "'";
    $resultado4 = $conexion->query($sql4);
    $row4 = $resultado4->fetch_assoc();

    $sql5 = "SELECT valor FROM municipios WHERE codigo = '" . $receptor['direccion']['municipio'] . "'";
    $resultado5 = $conexion->query($sql5);
    $row5 = $resultado5->fetch_assoc();

    $sql6 = "SELECT valor FROM documentos WHERE codigo = '" . $receptor['tipoDocumento'] . "'";
    $resultado6 = $conexion->query($sql6);
    $row6 = $resultado6->fetch_assoc();

    $sql7 = "SELECT usuario.nombre,factura.numerofactura FROM usuario INNER JOIN factura ON factura.idusuario = usuario.idusuario WHERE factura.id = $id";
    $resultado7 = $conexion->query($sql7);
    $row7 = $resultado7->fetch_assoc();

    // === Crear PDF ===
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 10);

    // === Encabezado ===
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, utf8_decode('DOCUMENTO TRIBUTARIO ELECTRÓNICO'), 0, 1, 'C');
    $pdf->Cell(0, 6, utf8_decode('FACTURA'), 0, 1, 'C');

    // Código QR
    $pdf->Image($archivoQR, 85, 27, 40, 40);

    // === Datos Generales ===
    $pdf->SetY(30);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(35, 6, utf8_decode('Código de Generación: '), 0, 0);
    $pdf->Cell(225, 6, utf8_decode('Modelo de Fcaturación: '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', '', size: 9);
    $pdf->Cell(35, 6, utf8_decode($ident['codigoGeneracion']), 0, 0);
    $pdf->Cell(220, 6, utf8_decode('Modelo Facturación previo '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(35, 6, utf8_decode('Número de Control: '), 0, 0);
    $pdf->Cell(220, 6, utf8_decode('Tipo de Transmisión: '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', '', 9);
    $pdf->Cell(35, 6, utf8_decode($ident['numeroControl']), 0, 0);
    $pdf->Cell(210, 6, utf8_decode('Transmisión normal'), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(35, 6, utf8_decode('Sello de Recepción:'), 0, 0);
    $pdf->Cell(234, 6, utf8_decode('Fecha y Hora de Generación: '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', '', 9);
    $pdf->Cell(35, 6, utf8_decode($row['selloRecibido']), 0, 0);
    $pdf->Cell(210, 6, utf8_decode($ident['fecEmi'] . ' ' . $ident['horEmi']), 0, 1, 'C');

    $pdf->Ln(5);

    $actividad = $emisor['descActividad'] ?? '';
    $codActividad = $emisor['descActividad']['codActividad'] ?? '';
    $actividadR = $receptor['descActividad'] ?? '';
    $codActividadR = $receptor['descActividad']['codActividad'] ?? '';

    // --- Texto EMISOR ---
    $emisorText = "Nombre: {$emisor['nombre']}\n";
    $emisorText .= "NIT: {$emisor['nit']}\n";
    $emisorText .= "NRC: {$emisor['nrc']}\n";
    $emisorText .= "Actividad económica: $actividad\n";
    $emisorText .= "Dirección: " . ($emisor['direccion']['complemento'] ?? '') . ', ' . utf8_decode($row2['valor']) . ', ' . utf8_decode($row3['valor']) . "\n";
    $emisorText .= "Número de teléfono: " . ($emisor['telefono'] ?? '') . "\n";
    $emisorText .= "Correo electrónico: " . ($emisor['correo'] ?? '') . "\n";
    $emisorText .= "Tipo de establecimiento: Casa Matriz";

    // --- Texto RECEPTOR ---
    $receptorText = "Nombre: {$receptor['nombre']}\n";
    $receptorText .= "Tipo de documento de identificación: " . utf8_decode($row6['valor']) . "\n";
    $receptorText .= "Número de Documento de Identificación: " . ($receptor['numDocumento'] ?? '') . "\n";
    $receptorText .= "Actividad económica: $actividadR ($codActividadR)\n";
    $receptorText .= "Dirección: " . ($receptor['direccion']['complemento'] ?? '') . ', ' . utf8_decode($row4['valor']) . ', ' . utf8_decode($row5['valor']) . "\n";
    $receptorText .= "Correo electrónico: " . ($receptor['correo'] ?? '') . "\n";
    $receptorText .= "Teléfono: " . ($receptor['telefono'] ?? '');

    // Posiciones y medidas
    $startY = 70;
    $emisorX = 10;
    $receptorX = 110;
    $width = 90;

    // Dibujar título y calcular altura EMISOR
    $pdf->SetXY($emisorX, $startY);
    $pdf->SetFont('Arial', 'UB', 14);
    $pdf->Cell($width, 7, 'Emisor', 0, 2, 'C');
    $pdf->SetFont('Times', '', 11);
    $pdf->SetX($emisorX);
    $startYContent = $pdf->GetY();
    $pdf->MultiCell($width, 4, utf8_decode($emisorText), 0, 'L');
    $endY = $pdf->GetY();
    $boxHeight = $endY - $startY;

    // Dibujar rectángulo redondeado EMISOR
    $pdf->RoundedRect($emisorX, $startY, $width, $boxHeight + 3, 3);

    // Dibujar título y contenido RECEPTOR
    $pdf->SetXY($receptorX, $startY);
    $pdf->SetFont('Arial', 'UB', 14);
    $pdf->Cell($width, 7, 'Receptor', 0, 2, 'C');
    $pdf->SetFont('Times', '', 11);
    $pdf->SetX($receptorX);
    $pdf->MultiCell($width, 4, utf8_decode($receptorText), 0, 'L');

    // Dibujar rectángulo redondeado RECEPTOR (igual altura que EMISOR)
    $pdf->RoundedRect($receptorX, $startY, $width, $boxHeight + 3, 3);

    $pdf->Ln(5);
    // Detalle de productos
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(10, 6, 'N', 1, 0, 'C');
    $pdf->Cell(55, 6, 'Descripcion', 1, 0, 'C');
    $pdf->Cell(15, 6, 'Cantidad', 1, 0, 'C');
    $pdf->Cell(10, 6, 'Unidad', 1, 0, 'C');
    $pdf->Cell(20, 6, 'Precio Uni', 1, 0, 'C');
    $pdf->Cell(20, 6, 'Descuento', 1, 0, 'C');
    $pdf->Cell(20, 6, 'No Sujetas', 1, 0, 'C');
    $pdf->Cell(20, 6, 'Exentas', 1, 0, 'C');
    $pdf->Cell(20, 6, 'Gravadas', 1, 1, 'C');

    $pdf->SetFont('Arial', '', 8);
    $contador = 1;

    foreach ($data['cuerpoDocumento'] as $item) {
        $pdf->Cell(10, 5, $contador++, 1, 0, 'C');
        $pdf->Cell(55, 5, substr($item['descripcion'] ?? '', 0, 40), 1);
        $pdf->Cell(15, 5, $item['cantidad'] ?? '', 1, 0, 'C');
        $pdf->Cell(10, 5, $item['uniMedida'] ?? '', 1, 0, 'C');
        $pdf->Cell(20, 5, '$' . number_format($item['precioUni'] ?? 0, 4), 1, 0, 'R');
        $pdf->Cell(20, 5, '$' . number_format($item['montoDescu'] ?? 0, 3), 1, 0, 'R');
        $pdf->Cell(20, 5, '$' . number_format($item['ventaNoSuj'] ?? 0, 2), 1, 0, 'R');
        $pdf->Cell(20, 5, '$' . number_format($item['ventaExenta'] ?? 0, 2), 1, 0, 'R');
        $pdf->Cell(20, 5, '$' . number_format($item['ventaGravada'] ?? 0, 2), 1, 1, 'R');
    }

    // Totales
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(170, 5, 'SUMA DE VENTAS:', 1, 0, 'R');
    $pdf->Cell(20, 5, '$' . number_format($data['resumen']['totalGravada'] ?? 0, 2), 1, 1, 'R');

    // Información adicional
    $pdf->Ln(3);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Sumal Total de Operaciones:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['montoTotalOperacion'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Monto global Desc., Rebajas y otros a ventas no sujetas:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['descuNoSuj'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Monto global Desc., Rebajas y otros a ventas Exentas:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['descuExenta'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Monto global Desc., Rebajas y otros a ventas Gravadas:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['descuGravada'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Sub-Total:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['subTotal'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'IVA Retenido:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format(0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Retencion de Renta:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['reteRenta'] ?? 0, 2), 0, 1, 'R');

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Total a pagar:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['totalPagar'] ?? 0, 2), 0, 1, 'R');

    // Valor en letras y condición
    $pdf->Ln(4);
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->SetFillColor(80, 80, 80);
    $pdf->SetTextColor(255);
    $pdf->Cell(130, 6, utf8_decode('Valor en letras: ' . strtoupper($data['resumen']['totalLetras'] ?? 'CERO 00/100')), 0, 0, 'L', true);
    $pdf->Cell(60, 6, 'Condicion de la operacion: Contado', 0, 1, 'R', true);
    $pdf->SetTextColor(0); // Restaurar color

    $pdf->Ln(4);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetFillColor(80, 80, 80);
    $pdf->SetTextColor(255);
    $pdf->Cell(190, 6, 'Apendice', 0, 0, 'L', true);
    $pdf->SetTextColor(0); // Restaurar color

    $pdf->Ln(10);
    $pdf->SetFont('Times', 'UB', 12);
    $pdf->Cell(35, 6, utf8_decode('Datos del Vendedor '), 0, 0);
    $pdf->Cell(225, 6, utf8_decode('Datos del Documento '), 0, 0, 'C');


    $pdf->Ln(5);
    $pdf->SetFont('Times', '', 10);
    $pdf->Cell(35, 6, utf8_decode('Nombre : ' . $row7['nombre']), 0, 0);
    $pdf->Cell(235, 6, utf8_decode('Numero de Documento : ' . $row7['numerofactura']), 0, 1, 'C');
    $pdf->Cell(46, 6, utf8_decode('Sello Ministerio de Hacienda : ' . $row['selloRecibido']), 0, 0, 'L');

} else if ($ident['tipoDte'] == '03') {
    $sql2 = "SELECT valor FROM departamentos WHERE codigo = '" . $emisor['direccion']['departamento'] . "'";
    $resultado2 = $conexion->query($sql2);
    $row2 = $resultado2->fetch_assoc();

    $sql3 = "SELECT valor FROM municipios WHERE codigo = '" . $emisor['direccion']['municipio'] . "'";
    $resultado3 = $conexion->query($sql3);
    $row3 = $resultado3->fetch_assoc();

    // === Obtener nombre del departamento ===
    $sql4 = "SELECT valor FROM departamentos WHERE codigo = '" . $receptor['direccion']['departamento'] . "'";
    $resultado4 = $conexion->query($sql4);
    $row4 = $resultado4->fetch_assoc();

    $sql5 = "SELECT valor FROM municipios WHERE codigo = '" . $receptor['direccion']['municipio'] . "'";
    $resultado5 = $conexion->query($sql5);
    $row5 = $resultado5->fetch_assoc();

    // $sql6 = "SELECT valor FROM documentos WHERE codigo = '" . $receptor['tipoDocumento'] . "'";
    // $resultado6 = $conexion->query($sql6);
    // $row6 = $resultado6->fetch_assoc();

    $sql7 = "SELECT usuario.nombre,factura.numerofactura FROM usuario INNER JOIN factura ON factura.idusuario = usuario.idusuario WHERE factura.id = $id";
    $resultado7 = $conexion->query($sql7);
    $row7 = $resultado7->fetch_assoc();
    // === Crear PDF ===
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 10);

    // === Encabezado ===
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, utf8_decode('DOCUMENTO TRIBUTARIO ELECTRÓNICO'), 0, 1, 'C');
    $pdf->Cell(0, 6, utf8_decode('COMPROBANTE DE CRÉDITO FISCAL'), 0, 1, 'C');

    // Código QR
    $pdf->Image($archivoQR, 85, 27, 40, 40);

    // === Datos Generales ===
    $pdf->SetY(30);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(35, 6, utf8_decode('Código de Generación: '), 0, 0);
    $pdf->Cell(225, 6, utf8_decode('Modelo de Facturación: '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', '', size: 9);
    $pdf->Cell(35, 6, utf8_decode($ident['codigoGeneracion']), 0, 0);
    $pdf->Cell(220, 6, utf8_decode('Modelo Facturación previo '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(35, 6, utf8_decode('Número de Control: '), 0, 0);
    $pdf->Cell(220, 6, utf8_decode('Tipo de Transmisión: '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', '', 9);
    $pdf->Cell(35, 6, utf8_decode($ident['numeroControl']), 0, 0);
    $pdf->Cell(210, 6, utf8_decode('Transmisión normal'), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(35, 6, utf8_decode('Sello de Recepción:'), 0, 0);
    $pdf->Cell(234, 6, utf8_decode('Fecha y Hora de Generación: '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', '', 9);
    $pdf->Cell(35, 6, utf8_decode($row['selloRecibido']), 0, 0);
    $pdf->Cell(210, 6, utf8_decode($ident['fecEmi'] . ' ' . $ident['horEmi']), 0, 1, 'C');

    $pdf->Ln(5);

    $actividad = $emisor['descActividad'] ?? '';
    $codActividad = $emisor['descActividad']['codActividad'] ?? '';
    $actividadR = $receptor['descActividad'] ?? '';
    $codActividadR = $receptor['descActividad']['codActividad'] ?? '';

    // --- Texto EMISOR ---
    $emisorText = "Nombre: {$emisor['nombre']}\n";
    $emisorText .= "NIT: {$emisor['nit']}\n";
    $emisorText .= "NRC: {$emisor['nrc']}\n";
    $emisorText .= "Actividad económica: $actividad\n";
    $emisorText .= "Dirección: " . ($emisor['direccion']['complemento'] ?? '') . ', ' . utf8_decode($row2['valor']) . ', ' . utf8_decode($row3['valor']) . "\n";
    $emisorText .= "Número de teléfono: " . ($emisor['telefono'] ?? '') . "\n";
    $emisorText .= "Correo electrónico: " . ($emisor['correo'] ?? '') . "\n";
    $emisorText .= "Tipo de establecimiento: Casa Matriz";

    // --- Texto RECEPTOR ---
    $receptorText = "Nombre: {$receptor['nombre']}\n";
    $receptorText .= "NIT: " . utf8_decode($receptor['nit']) . "\n";
    $receptorText .= "NRC: " . ($receptor['nrc'] ?? '') . "\n";
    $receptorText .= "Actividad económica: $actividadR ($codActividadR)\n";
    $receptorText .= "Dirección: " . ($receptor['direccion']['complemento'] ?? '') . ', ' . utf8_decode($row4['valor']) . ', ' . utf8_decode($row5['valor']) . "\n";
    $receptorText .= "Correo electrónico: " . ($receptor['correo'] ?? '') . "\n";
    $receptorText .= "Teléfono: " . ($receptor['telefono'] ?? '');

    // Posiciones y medidas
    $startY = 70;
    $emisorX = 10;
    $receptorX = 110;
    $width = 90;

    // Dibujar título y calcular altura EMISOR
    $pdf->SetXY($emisorX, $startY);
    $pdf->SetFont('Arial', 'UB', 14);
    $pdf->Cell($width, 7, 'Emisor', 0, 2, 'C');
    $pdf->SetFont('Times', '', 11);
    $pdf->SetX($emisorX);
    $startYContent = $pdf->GetY();
    $pdf->MultiCell($width, 4, utf8_decode($emisorText), 0, 'L');
    $endY = $pdf->GetY();
    $boxHeight = $endY - $startY;

    // Dibujar rectángulo redondeado EMISOR
    $pdf->RoundedRect($emisorX, $startY, $width, $boxHeight + 3, 3);

    // Dibujar título y contenido RECEPTOR
    $pdf->SetXY($receptorX, $startY);
    $pdf->SetFont('Arial', 'UB', 14);
    $pdf->Cell($width, 7, 'Receptor', 0, 2, 'C');
    $pdf->SetFont('Times', '', 11);
    $pdf->SetX($receptorX);
    $pdf->MultiCell($width, 4, utf8_decode($receptorText), 0, 'L');

    // Dibujar rectángulo redondeado RECEPTOR (igual altura que EMISOR)
    $pdf->RoundedRect($receptorX, $startY, $width, $boxHeight + 3, 3);

    $pdf->Ln(5);
    // Detalle de productos
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(10, 6, 'N', 1, 0, 'C');
    $pdf->Cell(55, 6, 'Descripcion', 1, 0, 'C');
    $pdf->Cell(15, 6, 'Cantidad', 1, 0, 'C');
    $pdf->Cell(10, 6, 'Unidad', 1, 0, 'C');
    $pdf->Cell(20, 6, 'Precio Uni', 1, 0, 'C');
    $pdf->Cell(20, 6, 'Descuento', 1, 0, 'C');
    $pdf->Cell(20, 6, 'No Sujetas', 1, 0, 'C');
    $pdf->Cell(20, 6, 'Exentas', 1, 0, 'C');
    $pdf->Cell(20, 6, 'Gravadas', 1, 1, 'C');

    $pdf->SetFont('Arial', '', 8);
    $contador = 1;

    foreach ($data['cuerpoDocumento'] as $item) {
        $pdf->Cell(10, 5, $contador++, 1, 0, 'C');
        $pdf->Cell(55, 5, substr($item['descripcion'] ?? '', 0, 40), 1);
        $pdf->Cell(15, 5, $item['cantidad'] ?? '', 1, 0, 'C');
        $pdf->Cell(10, 5, $item['uniMedida'] ?? '', 1, 0, 'C');
        $pdf->Cell(20, 5, '$' . number_format($item['precioUni'] ?? 0, 4), 1, 0, 'R');
        $pdf->Cell(20, 5, '$' . number_format($item['montoDescu'] ?? 0, 3), 1, 0, 'R');
        $pdf->Cell(20, 5, '$' . number_format($item['ventaNoSuj'] ?? 0, 2), 1, 0, 'R');
        $pdf->Cell(20, 5, '$' . number_format($item['ventaExenta'] ?? 0, 2), 1, 0, 'R');
        $pdf->Cell(20, 5, '$' . number_format($item['ventaGravada'] ?? 0, 2), 1, 1, 'R');
    }

    // Totales
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(170, 5, 'SUMA DE VENTAS:', 1, 0, 'R');
    $pdf->Cell(20, 5, '$' . number_format($data['resumen']['totalGravada'] ?? 0, 2), 1, 1, 'R');

    // Información adicional
    $pdf->Ln(3);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Suma Total de Operaciones:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['subTotal'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Monto global Desc., Rebajas y otros a ventas no sujetas:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['descuNoSuj'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Monto global Desc., Rebajas y otros a ventas Exentas:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['descuExenta'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Monto global Desc., Rebajas y otros a ventas Gravadas:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['descuGravada'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Sub-Total:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['subTotal'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(121, 5, '', 0, 0);
    foreach ($resumen['tributos'] as $tributo) {
        $descripcion = utf8_decode($tributo['descripcion'] ?? '');
        $valor = number_format($tributo['valor'] ?? 0, 2);
        $pdf->Cell(44, 5, $descripcion, 0, 0, 'L');
        $pdf->Cell(25, 5, '$' . $valor, 0, 1, 'R');
    }
    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'IVA Percibido:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format(0, 2), 0, 1, 'R');


    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'IVA Retenido:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format(0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Retencion de Renta:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['reteRenta'] ?? 0, 2), 0, 1, 'R');

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Total a pagar:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['totalPagar'] ?? 0, 2), 0, 1, 'R');

    // Valor en letras y condición
    $pdf->Ln(4);
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->SetFillColor(80, 80, 80);
    $pdf->SetTextColor(255);
    $pdf->Cell(130, 6, utf8_decode('Valor en letras: ' . strtoupper($data['resumen']['totalLetras'] ?? 'CERO 00/100')), 0, 0, 'L', true);
    $pdf->Cell(60, 6, 'Condicion de la operacion: Contado', 0, 1, 'R', true);
    $pdf->SetTextColor(0); // Restaurar color

    $pdf->Ln(4);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetFillColor(80, 80, 80);
    $pdf->SetTextColor(255);
    $pdf->Cell(190, 6, 'Apendice', 0, 0, 'L', true);
    $pdf->SetTextColor(0); // Restaurar color

    $pdf->Ln(10);
    $pdf->SetFont('Times', 'UB', 12);
    $pdf->Cell(35, 6, utf8_decode('Datos del Vendedor '), 0, 0);
    $pdf->Cell(225, 6, utf8_decode('Datos del Documento '), 0, 0, 'C');


    $pdf->Ln(5);
    $pdf->SetFont('Times', '', 10);
    $pdf->Cell(35, 6, utf8_decode('Nombre : ' . $row7['nombre']), 0, 0);
    $pdf->Cell(235, 6, utf8_decode('Numero de Documento : ' . $row7['numerofactura']), 0, 1, 'C');
    $pdf->Cell(46, 6, utf8_decode('Sello Ministerio de Hacienda : ' . $row['selloRecibido']), 0, 0, 'L');

} else if ($ident['tipoDte'] == '05') {
    $sql2 = "SELECT valor FROM departamentos WHERE codigo = '" . $emisor['direccion']['departamento'] . "'";
    $resultado2 = $conexion->query($sql2);
    $row2 = $resultado2->fetch_assoc();

    $sql3 = "SELECT valor FROM municipios WHERE codigo = '" . $emisor['direccion']['municipio'] . "'";
    $resultado3 = $conexion->query($sql3);
    $row3 = $resultado3->fetch_assoc();

    // === Obtener nombre del departamento ===
    $sql4 = "SELECT valor FROM departamentos WHERE codigo = '" . $receptor['direccion']['departamento'] . "'";
    $resultado4 = $conexion->query($sql4);
    $row4 = $resultado4->fetch_assoc();

    $sql5 = "SELECT valor FROM municipios WHERE codigo = '" . $receptor['direccion']['municipio'] . "'";
    $resultado5 = $conexion->query($sql5);
    $row5 = $resultado5->fetch_assoc();

    $sqlnot = "SELECT usuario.nombre,notas_credito.numeroDocumento FROM usuario INNER JOIN notas_credito ON notas_credito.id_usuario = usuario.idusuario WHERE notas_credito.codigoGeneracion = '" . $codigo . "'";
    $resultadonot = $conexion->query($sqlnot);
    $rownot = $resultadonot->fetch_assoc();

    // === Crear PDF ===
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 10);

    // === Encabezado ===
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, utf8_decode('DOCUMENTO TRIBUTARIO ELECTRÓNICO'), 0, 1, 'C');
    $pdf->Cell(0, 6, utf8_decode('NOTA DE CREDITO'), 0, 1, 'C');

    // Código QR
    $pdf->Image($archivoQR, 85, 27, 40, 40);

    // === Datos Generales ===
    $pdf->SetY(30);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(35, 6, utf8_decode('Código de Generación: '), 0, 0);
    $pdf->Cell(225, 6, utf8_decode('Modelo de Facturación: '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', '', size: 9);
    $pdf->Cell(35, 6, utf8_decode($ident['codigoGeneracion']), 0, 0);
    $pdf->Cell(220, 6, utf8_decode('Modelo Facturación previo '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(35, 6, utf8_decode('Número de Control: '), 0, 0);
    $pdf->Cell(220, 6, utf8_decode('Tipo de Transmisión: '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', '', 9);
    $pdf->Cell(35, 6, utf8_decode($ident['numeroControl']), 0, 0);
    $pdf->Cell(210, 6, utf8_decode('Transmisión normal'), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(35, 6, utf8_decode('Sello de Recepción:'), 0, 0);
    $pdf->Cell(234, 6, utf8_decode('Fecha y Hora de Generación: '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', '', 9);
    $pdf->Cell(35, 6, utf8_decode($row['selloRecibido']), 0, 0);
    $pdf->Cell(210, 6, utf8_decode($ident['fecEmi'] . ' ' . $ident['horEmi']), 0, 1, 'C');

    $pdf->Ln(3);

    $actividad = $emisor['descActividad'] ?? '';
    $codActividad = $emisor['descActividad']['codActividad'] ?? '';
    $actividadR = $receptor['descActividad'] ?? '';
    $codActividadR = $receptor['descActividad']['codActividad'] ?? '';

    // --- Texto EMISOR ---
    $emisorText = "Nombre: {$emisor['nombre']}\n";
    $emisorText .= "NIT: {$emisor['nit']}\n";
    $emisorText .= "NRC: {$emisor['nrc']}\n";
    $emisorText .= "Actividad económica: $actividad\n";
    $emisorText .= "Dirección: " . ($emisor['direccion']['complemento'] ?? '') . ', ' . utf8_decode($row2['valor']) . ', ' . utf8_decode($row3['valor']) . "\n";
    $emisorText .= "Número de teléfono: " . ($emisor['telefono'] ?? '') . "\n";
    $emisorText .= "Correo electrónico: " . ($emisor['correo'] ?? '') . "\n";
    $emisorText .= "Tipo de establecimiento: Casa Matriz";

    // --- Texto RECEPTOR ---
    $receptorText = "Nombre: {$receptor['nombre']}\n";
    $receptorText .= "NIT: " . utf8_decode($receptor['nit']) . "\n";
    $receptorText .= "NRC: " . ($receptor['nrc'] ?? '') . "\n";
    $receptorText .= "Actividad económica: $actividadR ($codActividadR)\n";
    $receptorText .= "Dirección: " . ($receptor['direccion']['complemento'] ?? '') . ', ' . utf8_decode($row4['valor']) . ', ' . utf8_decode($row5['valor']) . "\n";
    $receptorText .= "Correo electrónico: " . ($receptor['correo'] ?? '') . "\n";
    $receptorText .= "Teléfono: " . ($receptor['telefono'] ?? '');

    // Posiciones y medidas
    $startY = 70;
    $emisorX = 10;
    $receptorX = 110;
    $width = 90;

    // Dibujar título y calcular altura EMISOR
    $pdf->SetXY($emisorX, $startY);
    $pdf->SetFont('Arial', 'UB', 14);
    $pdf->Cell($width, 7, 'Emisor', 0, 2, 'C');
    $pdf->SetFont('Times', '', 11);
    $pdf->SetX($emisorX);
    $startYContent = $pdf->GetY();
    $pdf->MultiCell($width, 4, utf8_decode($emisorText), 0, 'L');
    $endY = $pdf->GetY();
    $boxHeight = $endY - $startY;

    // Dibujar rectángulo redondeado EMISOR
    $pdf->RoundedRect($emisorX, $startY, $width, $boxHeight + 3, 3);

    // Dibujar título y contenido RECEPTOR
    $pdf->SetXY($receptorX, $startY);
    $pdf->SetFont('Arial', 'UB', 14);
    $pdf->Cell($width, 7, 'Receptor', 0, 2, 'C');
    $pdf->SetFont('Times', '', 11);
    $pdf->SetX($receptorX);
    $pdf->MultiCell($width, 4, utf8_decode($receptorText), 0, 'L');

    // Dibujar rectángulo redondeado RECEPTOR (igual altura que EMISOR)
    $pdf->RoundedRect($receptorX, $startY, $width, $boxHeight + 3, 3);

    $pdf->Ln(15);

    // === Documentos Relacionados ===
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, utf8_decode('DOCUMENTOS RELACIONADOS'), 0, 1, 'C');

    // Encabezado de la tabla
    $pdf->SetFillColor(230, 250, 240); // Gris claro
    $pdf->SetDrawColor(0); // Color del borde
    $pdf->SetLineWidth(0.3);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(50, 8, utf8_decode('Tipo de Documento:'), 1, 0, 'C', true);
    $pdf->Cell(80, 8, utf8_decode('N° de documento:'), 1, 0, 'C', true);
    $pdf->Cell(60, 8, utf8_decode('Fecha del Documento:'), 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(50, 8, utf8_decode($tipoDocRel), 1, 0, 'C');
    $pdf->Cell(80, 8, utf8_decode($numDocRel), 1, 0, 'C');
    $pdf->Cell(60, 8, utf8_decode($fechaDocRel), 1, 1, 'C');

    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(10, 6, 'N', 1, 0, 'C');
    $pdf->Cell(15, 6, 'Cantidad', 1, 0, 'C');
    $pdf->Cell(10, 6, 'Unidad', 1, 0, 'C');
    $pdf->Cell(55, 6, 'Descripcion', 1, 0, 'C');
    $pdf->Cell(20, 6, 'Precio Uni', 1, 0, 'C');
    $pdf->Cell(20, 6, 'Descuento', 1, 0, 'C');
    $pdf->Cell(20, 6, 'No Sujetas', 1, 0, 'C');
    $pdf->Cell(20, 6, 'Exentas', 1, 0, 'C');
    $pdf->Cell(20, 6, 'Gravadas', 1, 1, 'C');

    $pdf->SetFont('Arial', '', 8);
    $contador = 1;

    foreach ($data['cuerpoDocumento'] as $item) {
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        // Calcular altura que tomará la descripción
        $pdf->SetXY($x + 10 + 15 + 10, $y); // posición de descripción
        $pdf->MultiCell(55, 5, utf8_decode($item['descripcion'] ?? ''), 1);
        $height = $pdf->GetY() - $y;

        // Volver al inicio de línea
        $pdf->SetXY($x, $y);

        // Columnas alineadas
        $pdf->Cell(10, $height, $contador++, 1, 0, 'C');
        $pdf->Cell(15, $height, $item['cantidad'] ?? '', 1, 0, 'C');
        $pdf->Cell(10, $height, $item['uniMedida'] ?? '', 1, 0, 'C');

        // Ya se imprimió descripción con MultiCell, avanzar el cursor
        $pdf->SetXY($x + 10 + 15 + 10 + 55, $y);

        $pdf->Cell(20, $height, '$' . number_format($item['precioUni'] ?? 0, 4), 1, 0, 'R');
        $pdf->Cell(20, $height, '$' . number_format($item['montoDescu'] ?? 0, 3), 1, 0, 'R');
        $pdf->Cell(20, $height, '$' . number_format($item['ventaNoSuj'] ?? 0, 2), 1, 0, 'R');
        $pdf->Cell(20, $height, '$' . number_format($item['ventaExenta'] ?? 0, 2), 1, 0, 'R');
        $pdf->Cell(20, $height, '$' . number_format($item['ventaGravada'] ?? 0, 2), 1, 1, 'R');
    }
    // Totales
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(170, 5, 'SUMA DE VENTAS:', 1, 0, 'R');
    $pdf->Cell(20, 5, '$' . number_format($data['resumen']['totalGravada'] ?? 0, 2), 1, 1, 'R');

    // Información adicional
    $pdf->Ln(3);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Suma Total de Operaciones:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['subTotal'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Monto global Desc., Rebajas y otros a ventas no sujetas:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['descuNoSuj'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Monto global Desc., Rebajas y otros a ventas Exentas:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['descuExenta'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Monto global Desc., Rebajas y otros a ventas Gravadas:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['descuGravada'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Sub-Total:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['subTotal'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(121, 5, '', 0, 0);
    foreach ($resumen['tributos'] as $tributo) {
        $descripcion = utf8_decode($tributo['descripcion'] ?? '');
        $valor = number_format($tributo['valor'] ?? 0, 2);
        $pdf->Cell(44, 5, $descripcion, 0, 0, 'L');
        $pdf->Cell(25, 5, '$' . $valor, 0, 1, 'R');
    }

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Total a pagar:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['subTotal'] + $valor ?? 0, 2), 0, 1, 'R');

    // Valor en letras y condición
    $pdf->Ln(4);
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->SetFillColor(80, 80, 80);
    $pdf->SetTextColor(255);
    $pdf->Cell(130, 6, utf8_decode('Valor en letras: ' . strtoupper($data['resumen']['totalLetras'] ?? 'CERO 00/100')), 0, 0, 'L', true);
    $pdf->Cell(60, 6, 'Condicion de la operacion: Contado', 0, 1, 'R', true);
    $pdf->SetTextColor(0); // Restaurar color

    $pdf->Ln(4);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetFillColor(80, 80, 80);
    $pdf->SetTextColor(255);
    $pdf->Cell(190, 6, 'Apendice', 0, 0, 'L', true);
    $pdf->SetTextColor(0); // Restaurar color

    $pdf->Ln(10);
    $pdf->SetFont('Times', 'UB', 12);
    $pdf->Cell(35, 6, utf8_decode('Datos del Vendedor '), 0, 0);
    $pdf->Cell(225, 6, utf8_decode('Datos del Documento '), 0, 0, 'C');


    $pdf->Ln(5);
    $pdf->SetFont('Times', '', 10);
    $pdf->Cell(35, 6, utf8_decode('Nombre : ' . $rownot['nombre']), 0, 0);
    $pdf->Cell(235, 6, utf8_decode('Numero de Documento : ' . $rownot['numeroDocumento']), 0, 1, 'C');
    $pdf->Cell(46, 6, utf8_decode('Sello Ministerio de Hacienda : ' . $row['selloRecibido']), 0, 0, 'L');

} else if ($ident['tipoDte'] == '14') {
    $sql2 = "SELECT valor FROM departamentos WHERE codigo = '" . $emisor['direccion']['departamento'] . "'";
    $resultado2 = $conexion->query($sql2);
    $row2 = $resultado2->fetch_assoc();

    $sql3 = "SELECT valor FROM municipios WHERE codigo = '" . $emisor['direccion']['municipio'] . "'";
    $resultado3 = $conexion->query($sql3);
    $row3 = $resultado3->fetch_assoc();

    // === Obtener nombre del departamento ===
    $sql4 = "SELECT valor FROM departamentos WHERE codigo = '" . $receptor['direccion']['departamento'] . "'";
    $resultado4 = $conexion->query($sql4);
    $row4 = $resultado4->fetch_assoc();

    $sql5 = "SELECT valor FROM municipios WHERE codigo = '" . $receptor['direccion']['municipio'] . "'";
    $resultado5 = $conexion->query($sql5);
    $row5 = $resultado5->fetch_assoc();

    $sql6 = "SELECT valor FROM documentos WHERE codigo = '" . $receptor['tipoDocumento'] . "'";
    $resultado6 = $conexion->query($sql6);
    $row6 = $resultado6->fetch_assoc();

    // $sql7 = "SELECT usuario.nombre,factura.numerofactura FROM usuario INNER JOIN factura ON factura.idusuario = usuario.idusuario WHERE factura.id = $id";
    // $resultado7 = $conexion->query($sql7);
    // $row7 = $resultado7->fetch_assoc();
    // === Crear PDF ===
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 10);

    // === Encabezado ===
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, utf8_decode('DOCUMENTO TRIBUTARIO ELECTRÓNICO'), 0, 1, 'C');
    $pdf->Cell(0, 6, utf8_decode('FACTURA SUJETO EXCLUIDO'), 0, 1, 'C');

    // Código QR
    $pdf->Image($archivoQR, 85, 27, 40, 40);

    // === Datos Generales ===
    $pdf->SetY(30);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(35, 6, utf8_decode('Código de Generación: '), 0, 0);
    $pdf->Cell(225, 6, utf8_decode('Modelo de Facturación: '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', '', size: 9);
    $pdf->Cell(35, 6, utf8_decode($ident['codigoGeneracion']), 0, 0);
    $pdf->Cell(220, 6, utf8_decode('Modelo Facturación previo '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(35, 6, utf8_decode('Número de Control: '), 0, 0);
    $pdf->Cell(220, 6, utf8_decode('Tipo de Transmisión: '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', '', 9);
    $pdf->Cell(35, 6, utf8_decode($ident['numeroControl']), 0, 0);
    $pdf->Cell(210, 6, utf8_decode('Transmisión normal'), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(35, 6, utf8_decode('Sello de Recepción:'), 0, 0);
    $pdf->Cell(234, 6, utf8_decode('Fecha y Hora de Generación: '), 0, 0, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Times', '', 9);
    $pdf->Cell(35, 6, utf8_decode($row['selloRecibido']), 0, 0);
    $pdf->Cell(210, 6, utf8_decode($ident['fecEmi'] . ' ' . $ident['horEmi']), 0, 1, 'C');

    $pdf->Ln(5);

    $actividad = $emisor['descActividad'] ?? '';
    $codActividad = $emisor['descActividad']['codActividad'] ?? '';
    $actividadR = $receptor['descActividad'] ?? '';
    $codActividadR = $receptor['descActividad']['codActividad'] ?? '';

    // --- Texto EMISOR ---
    $emisorText = "Nombre o razon social: {$emisor['nombre']}\n";
    $emisorText .= "NIT: {$emisor['nit']}\n";
    $emisorText .= "Actividad económica: $actividad\n";
    $emisorText .= "Dirección: " . ($emisor['direccion']['complemento'] ?? '') . ', ' . utf8_decode($row2['valor']) . ', ' . utf8_decode($row3['valor']) . "\n";
    $emisorText .= "Número de teléfono: " . ($emisor['telefono'] ?? '') . "\n";
    $emisorText .= "Correo electrónico: " . ($emisor['correo'] ?? '') . "\n";
    $emisorText .= "Nombre comercial: " . ($emisor['razon_social'] ?? '') . "\n";
    $emisorText .= "Tipo de establecimiento: Casa Matriz";

    // --- Texto RECEPTOR ---
    $receptorText = "Nombre o razon social: {$receptor['nombre']}\n";
    $receptorText .= "Tipo de documento de identificación: " . utf8_decode($row6['valor']) . "\n";
    $receptorText .= "Número de Documento de Identificación: " . ($receptor['numDocumento'] ?? '') . "\n";
    $receptorText .= "Teléfono: " . ($receptor['telefono'] ?? '') . "\n";
    $receptorText .= "Dirección: " . ($receptor['direccion']['complemento'] ?? '') . ', ' . utf8_decode($row4['valor']) . ', ' . utf8_decode($row5['valor']) . "\n";


    // Posiciones y medidas
    $startY = 70;
    $emisorX = 10;
    $receptorX = 110;
    $width = 90;

    // Dibujar título y calcular altura EMISOR
    $pdf->SetXY($emisorX, $startY);
    $pdf->SetFont('Arial', 'UB', 14);
    $pdf->Cell($width, 7, 'Emisor', 0, 2, 'C');
    $pdf->SetFont('Times', '', 11);
    $pdf->SetX($emisorX);
    $startYContent = $pdf->GetY();
    $pdf->MultiCell($width, 4, utf8_decode($emisorText), 0, 'L');
    $endY = $pdf->GetY();
    $boxHeight = $endY - $startY;

    // Dibujar rectángulo redondeado EMISOR
    $pdf->RoundedRect($emisorX, $startY, $width, $boxHeight + 3, 3);

    // Dibujar título y contenido RECEPTOR
    $pdf->SetXY($receptorX, $startY);
    $pdf->SetFont('Arial', 'UB', 14);
    $pdf->Cell($width, 7, 'SUJETO EXCLUIDO', 0, 2, 'C');
    $pdf->SetFont('Times', '', 11);
    $pdf->SetX($receptorX);
    $pdf->MultiCell($width, 4, utf8_decode($receptorText), 0, 'L');

    // Dibujar rectángulo redondeado RECEPTOR (igual altura que EMISOR)
    $pdf->RoundedRect($receptorX, $startY, $width, $boxHeight + 3, 3);

    $pdf->Ln(25);
    // Detalle de productos
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(10, 6, 'N', 1, 0, 'C');
    $pdf->Cell(15, 6, 'Cantidad', 1, 0, 'C');
    $pdf->Cell(10, 6, 'Unidad', 1, 0, 'C');
    $pdf->Cell(80, 6, 'Descripcion', 1, 0, 'C');
    $pdf->Cell(20, 6, 'Precio Uni', 1, 0, 'C');
    $pdf->Cell(35, 6, 'Descuento por item', 1, 0, 'C');
    $pdf->Cell(20, 6, 'ventas', 1, 1, 'C');

    $pdf->SetFont('Arial', '', 8);
    $contador = 1;

    foreach ($data['cuerpoDocumento'] as $item) {
        $pdf->Cell(10, 5, $contador++, 1, 0, 'C');
        $pdf->Cell(15, 5, $item['cantidad'] ?? '', 1, 0, 'C');
        $pdf->Cell(10, 5, $item['uniMedida'] ?? '', 1, 0, 'C');
        $pdf->Cell(80, 5, substr($item['descripcion'] ?? '', 0, 40), 1);
        $pdf->Cell(20, 5, '$' . number_format($item['precioUni'] ?? 0, 4), 1, 0, 'R');
        $pdf->Cell(35, 5, '$' . number_format($item['montoDescu'] ?? 0, 3), 1, 0, 'R');
        $pdf->Cell(20, 5, '$' . number_format($item['compra'] ?? 0, 2), 1, 1, 'R');
    }

    // Totales
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(170, 5, 'SUMA DE VENTAS:', 1, 0, 'R');
    $pdf->Cell(20, 5, '$' . number_format($data['resumen']['totalCompra'] ?? 0, 2), 1, 1, 'R');

    // Información adicional
    $pdf->Ln(3);
    $pdf->SetFont('Arial', '', 8);

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Monto global Desc., Rebajas y otros:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['totalDescu'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Sub-Total:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['subTotal'] ?? 0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'IVA Retenido:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format(0, 2), 0, 1, 'R');

    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Retencion de Renta:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['reteRenta'] ?? 0, 2), 0, 1, 'R');

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(120, 5, '', 0, 0);
    $pdf->Cell(45, 5, 'Total a pagar:', 0, 0, 'R');
    $pdf->Cell(25, 5, '$' . number_format($data['resumen']['totalPagar'] ?? 0, 2), 0, 1, 'R');

    // Valor en letras y condición
    $pdf->Ln(4);
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->SetFillColor(80, 80, 80);
    $pdf->SetTextColor(255);
    $pdf->Cell(130, 6, utf8_decode('Valor en letras: ' . strtoupper($data['resumen']['totalLetras'] ?? 'CERO 00/100')), 0, 0, 'L', true);
    $pdf->Cell(60, 6, 'Condicion de la operacion: Contado', 0, 1, 'R', true);
    $pdf->SetTextColor(0); // Restaurar color

    $pdf->Ln(4);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetFillColor(80, 80, 80);

}
// $pdf->Ln(30);
// $pdf->SetFont('Arial', '', 10);
// $pdf->SetFillColor(80, 80, 80);
// $pdf->SetTextColor(255);
// $pdf->Cell(190, 6, utf8_decode('Ferreteria Fuentes Construyendo tus sueños'), 0, 1, 'C', true);
// $pdf->SetFont('Arial', '', 8);
// $pdf->Cell(190, 6, utf8_decode('Telefonos: (503) 2273-2218, (503) 7875-5428    Correo: soluciones@grupoph.com'), 0, 0, 'C', true);
// $pdf->SetTextColor(2); // Restaurar color


$nombreArchivo = $ident['numeroControl'] . '.pdf';
ob_clean(); // Limpiar buffer
$pdf->Output('F', $nombreArchivo); // Guarda el archivo en disco
$correoEnviado = $row['correo_enviado'] ?? 0;
// ----- 2. ENVIAR EL PDF POR CORREO -----
if ($correoEnviado == 0) {
    // Tu código de envío de correo aquí
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'alex.calix1992@gmail.com';
        $mail->Password = 'mvwo raxu urmn jhps'; // Contraseña de aplicación
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Remitente y receptor
        $mail->setFrom('alex.calix1992@gmail.com', utf8_decode('Ferretería Fuentes'));
        $mail->addAddress($receptor['correo'], 'Cliente');

        // Adjuntar PDF
        $mail->addAttachment($nombreArchivo);
        // Adjuntar JSON desde variable
        $mail->addStringAttachment(
            $json,         // contenido del archivo
            $ident['numeroControl'] . '.json',       // nombre del archivo
            'base64',             // codificación
            'application/json'    // tipo MIME
        );

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = utf8_decode('FERRETERIA FUENTES - Factura Electrónica');
        $mail->Body = 'Estimado cliente,<br><br>Este correo es generado automaticamente por : <b>FERRETERIA FUENTES</b><br><br>Gracias por su compra.';

        $mail->send();
        $pdf->Output('I', $nombreArchivo . '.pdf');

        // Opcional: Eliminar el archivo PDF del servidor
        unlink($nombreArchivo);

        // Establecer que el correo ya ha sido enviado en esta sesión
        $save = $conexion->query("UPDATE notas_credito SET correo_enviado = 1 WHERE codigoGeneracion = '$codigo'");

    } catch (Exception $e) {
        echo "No se pudo enviar el correo: {$mail->ErrorInfo}";
    }
} else {
    // Si el correo ya fue enviado, simplemente muestra el PDF
    $pdf->Output('I', $nombreArchivo . '.pdf');

    // Opcional: Eliminar el archivo PDF del servidor
    unlink($nombreArchivo);
}

?>