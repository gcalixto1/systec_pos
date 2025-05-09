<?php
include 'conexionfin.php';
require_once 'config/config.php'; // Asegúrate de que este archivo tenga las constantes necesarias

$idfactura = intval($_GET['idfactura']);

// OBTENER FACTURA
$query = $conexion->query("SELECT factura.id, factura.tipofactura, factura.numerofactura, 
                                    factura.subtotal, factura.iva_impuesto, factura.totalpagar, 
                                    factura.letras, factura.forma_pago, factura.fechafactura, 
                                    cliente.nombre, cliente.dni, cliente.telefono, cliente.correo,
                                    cliente.dato1, cliente.dato2, cliente.dato3,
                                    cliente_direccion.departamento, cliente_direccion.municipio, 
                                    cliente_direccion.complemento, cliente.tipoDocumento, medio_pago.medio_pago
                                FROM factura 
                                INNER JOIN cliente ON cliente.idcliente = factura.idcliente
                                LEFT JOIN cliente_direccion ON cliente_direccion.cliente_dni = cliente.dni
                                INNER JOIN medio_pago ON medio_pago.codigo = factura.forma_pago
                                WHERE factura.id = $idfactura");
$factura = $query->fetch_assoc();

// OBTENER DETALLE DE PRODUCTOS
$items = [];
$detalle = $conexion->query("SELECT df.cantidad,p.descripcion, df.precioventa FROM detallefactura df inner join producto p ON p.codproducto = df.cod_producto WHERE idfactura = $idfactura");
$numItem = 1;
$totalGravada = 0;
$totalIva = 0;

while ($row = $detalle->fetch_assoc()) {
    $monto = floatval($row['cantidad']) * floatval($row['precioventa']);
    $montoiva = round($monto / 1.13, 2);
    $ivaItem = round($montoiva * 0.13, 2);
    $items[] = [
        "numItem" => $numItem++,
        "numeroDocumento" => null,
        "tipoItem" => 1,
        "cantidad" => floatval($row['cantidad']),
        "codigo" => "02",
        "uniMedida" => 59,
        "descripcion" => $row['descripcion'],
        "precioUni" => floatval($row['precioventa']),
        "montoDescu" => 0,
        "codTributo" => null,
        "ventaNoSuj" => 0,
        "ventaExenta" => 0,
        "ventaGravada" => $monto,
        "ivaItem" => $ivaItem,
        "tributos" => null,
        "psv" => $monto,
        "noGravado" => 0
    ];
    $totalGravada += $monto;
    $totalIva += $ivaItem;
}

// OBTENER TOKEN DE HACIENDA
require_once 'api/token.php';
$token = leerTokenCache();
if (!$token) {
    $token = obtenerTokenDesdeAPI();
}

// GENERAR CÓDIGO Y FECHA
$codigoGeneracion = strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4)));
date_default_timezone_set("America/El_Salvador");
$fechaEmision = date("Y-m-d");  // Formato: 2025-04-18
$horaEmision = date("H:i:s");

// ARMAR JSON PARA EL FIRMADOR
$facturaJson = [
    "nit" => MH_USER,
    "activo" => "true",
    "passwordPri" => MH_PWD_DTE,
    "dteJson" => [
        "identificacion" => [
            "version" => 1,
            "ambiente" => "00",
            "tipoDte" => "01",
            "numeroControl" => "DTE-01-M001P001-" . str_pad($idfactura, 15, "0", STR_PAD_LEFT),
            "codigoGeneracion" => $codigoGeneracion,
            "tipoModelo" => 1,
            "tipoOperacion" => 1,
            "tipoContingencia" => null,
            "motivoContin" => null,
            "fecEmi" => $fechaEmision,
            "horEmi" => $horaEmision,
            "tipoMoneda" => "USD"
        ],
        "documentoRelacionado" => null,
        "emisor" => [
            "nit" => "03011504761021",
            "nrc" => "3173130",
            "nombre" => "Oscar Eduardo Fuentes Mancia",
            "codActividad" => "46632",
            "descActividad" => "venta al por mayor de articulos de ferreteria y pinturerias",
            "nombreComercial" => "FERRETERIA FUENTES",
            "tipoEstablecimiento" => "02",
            "direccion" => [
                "departamento" => "03",
                "municipio" => "20",
                "complemento" => "Canton Punta Remedios, Caserio Los Cobanos,Acajutla"
            ],
            "telefono" => "73999642",
            "correo" => "ferreteriafuentes019@gmail.com",
            "codEstable" => null,
            "codPuntoVenta" => null,
            "codEstableMH" => "M001",
            "codPuntoVentaMH" => "P001"
        ],
        "receptor" => [
            "nrc" => null,
            "tipoDocumento" => $factura['tipoDocumento'],
            "numDocumento" => $factura['dni'],
            "nombre" => $factura['nombre'],
            "codActividad" => null,
            "descActividad" => null,
            "direccion" => [
                "departamento" => $factura['departamento'],
                "municipio" => $factura['municipio'],
                "complemento" => $factura['complemento']
            ],
            "telefono" => $factura['telefono'],
            "correo" => $factura['correo'],
        ],
        "otrosDocumentos" => null,
        "ventaTercero" => null,
        "cuerpoDocumento" => $items,
        "resumen" => [
            "totalNoSuj" => 0,
            "totalExenta" => 0,
            "totalGravada" => round($totalGravada, 2),
            "subTotalVentas" => round($totalGravada, 2),
            "descuNoSuj" => 0,
            "descuExenta" => 0,
            "descuGravada" => 0,
            "totalDescu" => 0,
            "porcentajeDescuento" => 0,
            "tributos" => null,
            "subTotal" => round($totalGravada, 2),
            "totalIva" => round($totalIva, 2),
            "ivaRete1" => 0,
            "reteRenta" => 0,
            "montoTotalOperacion" => round($totalGravada, 2),
            "totalNoGravado" => 0,
            "totalPagar" => round($totalGravada, 2),
            "totalLetras" => $factura['letras'],
            "saldoFavor" => 0,
            "condicionOperacion" => 1,
            "pagos" => [
                [
                    "codigo" => $factura['forma_pago'],
                    "montoPago" => round($totalGravada, 2),
                    "referencia" => $factura['medio_pago'],
                    "plazo" => null,
                    "periodo" => null
                ]
            ],
            "numPagoElectronico" => null
        ],
        "extension" => null,
        "apendice" => null
    ]
];

// ENVIAR A FIRMADOR
$curl = curl_init(MH_API_FIRMADOR);
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($facturaJson, JSON_UNESCAPED_UNICODE),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]
]);
file_put_contents('cache_estructura.json', json_encode($facturaJson, JSON_UNESCAPED_UNICODE));
$response = curl_exec($curl);

if (curl_errno($curl)) {
    echo json_encode(['success' => false, 'message' => 'CURL error: ' . curl_error($curl)]);
    curl_close($curl);
    exit;
}

// GUARDAR RESPUESTA DEL FIRMADOR EN CACHE
file_put_contents('cache_firmador.json', $response);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// SI FIRMA CORRECTAMENTE, ENVIAR A HACIENDA
if ($httpCode === 200) {
    include 'recepciondte.php'; // Enviar automáticamente a Hacienda después de firmar
} else {
    echo json_encode(['success' => false, 'message' => 'Error al firmar documento', 'detalle' => $response]);
}