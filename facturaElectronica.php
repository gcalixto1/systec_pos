<?php
require_once('phpqrcode/qrlib.php');
require_once('factura/fpdf/fpdf.php');



// === Generar QR ===
$contenidoQR = 'https://admin.factura.gob.sv/consultaPublica?ambiente=00&codGen=D50A7905-4CF4-E5B3-9E79-48A4B6A5A384&fechaEmi=2025-04-26';
$archivoQR = 'qr_temp.png';
QRcode::png($contenidoQR, $archivoQR, QR_ECLEVEL_H, 4);

// === Crear PDF ===
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// === Encabezado ===
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, utf8_decode('DOCUMENTO TRIBUTARIO ELECTRÓNICO'), 0, 1, 'C');
$pdf->Cell(0, 6, utf8_decode('FACTURA'), 0, 1, 'C');

// Código QR
$pdf->Image($archivoQR, 90, 30, 30, 30);

// === Datos Generales ===
$pdf->SetY(65);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(60, 6, utf8_decode('Código de Generación:'), 0, 0);
$pdf->Cell(60, 6, utf8_decode('Modelo de Facturación:'), 0, 1);

$pdf->Cell(60, 6, utf8_decode('Número de Control:'), 0, 0);
$pdf->Cell(60, 6, utf8_decode('Tipo de Transmisión:'), 0, 1);

$pdf->Cell(60, 6, utf8_decode('Sello de Recepción:'), 0, 0);
$pdf->Cell(60, 6, utf8_decode('Fecha y Hora de Generación:'), 0, 1);

$pdf->Ln(5);

// === Cuadro Emisor y Receptor ===
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(90, 6, 'EMISOR', 1, 0, 'C');
$pdf->Cell(90, 6, 'RECEPTOR', 1, 1, 'C');

$pdf->SetFont('Arial', '', 9);
for ($i = 0; $i < 6; $i++) {
    $pdf->Cell(90, 6, 'Dato Emisor', 1, 0);
    $pdf->Cell(90, 6, 'Dato Receptor', 1, 1);
}

// === Venta a Cuenta de Terceros ===
$pdf->Ln(4);
$pdf->Cell(0, 6, 'VENTA A CUENTA DE TERCEROS', 1, 1, 'C');
$pdf->Cell(0, 6, 'NIT: ______________________', 1, 1);

// === Documentos Relacionados ===
$pdf->Ln(2);
$pdf->Cell(0, 6, 'DOCUMENTOS RELACIONADOS', 1, 1, 'C');
$pdf->Cell(63, 6, 'Tipo de Documento', 1, 0);
$pdf->Cell(63, 6, 'N° de Documento', 1, 0);
$pdf->Cell(64, 6, 'Fecha del Documento', 1, 1);

// === Cuerpo Detalle ===
$pdf->Ln(3);
$cols = ['N°', 'Cantidad', 'Unidad', 'Descripción', 'Precio Unitario', 'Otras no afectas', 'Descuento x item', 'Ventas No Sujetas', 'Ventas Exentas', 'Ventas Gravadas'];
$widths = [10, 15, 20, 50, 25, 25, 25, 20, 20, 25];

$pdf->SetFont('Arial', 'B', 8);
foreach ($cols as $i => $col) {
    $pdf->Cell($widths[$i], 6, utf8_decode($col), 1, 0, 'C');
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 8);
for ($r = 0; $r < 4; $r++) {
    foreach ($widths as $w) {
        $pdf->Cell($w, 6, '', 1, 0);
    }
    $pdf->Ln();
}

// === Resumen Totales ===
$pdf->Ln(3);
$pdf->Cell(130, 6, '', 0, 0);
$pdf->Cell(30, 6, 'Sub-Total:', 1, 0);
$pdf->Cell(30, 6, '$100.00', 1, 1);

$pdf->Cell(130, 6, '', 0, 0);
$pdf->Cell(30, 6, 'IVA:', 1, 0);
$pdf->Cell(30, 6, '$13.00', 1, 1);

$pdf->Cell(130, 6, '', 0, 0);
$pdf->Cell(30, 6, 'Total a Pagar:', 1, 0);
$pdf->Cell(30, 6, '$113.00', 1, 1);

// === Mostrar PDF ===
$pdf->Output('I', 'factura_formato.pdf');
unlink($archivoQR);
?>