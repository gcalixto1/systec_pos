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

$queryEmpresa = $conexion->query("SELECT * FROM configuracion WHERE id = 1");
$Empresa = $queryEmpresa->fetch_assoc();

$queryActividad = $conexion->query("SELECT descripcion FROM actividad_economica WHERE codigo = $Empresa[giro]");
$ActividaE = $queryActividad->fetch_assoc();

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
        "tipoItem" => 1,
        "numeroDocumento" => null,
        "codigo" => null,
        "codTributo" => null,
        "descripcion" => $row['descripcion'],
        "cantidad" => floatval($row['cantidad']),
        "uniMedida" => 59,
        "precioUni" => round(floatval($row['precioventa'] / 1.13), 2),
        "montoDescu" => 0,
        "ventaNoSuj" => 0,
        "ventaExenta" => 0,
        "ventaGravada" => round($monto / 1.13, 2),
        "tributos" => ["20"],
        "psv" => 0,
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

$sql6 = "SELECT * FROM actividad_economica WHERE codigo = '" . $factura['dato3'] . "'";
$resultado6 = $conexion->query($sql6);
$row6 = $resultado6->fetch_assoc();
// ARMAR JSON PARA EL FIRMADOR
$facturaJson = [
    "nit" => MH_USER,
    "activo" => "true",
    "passwordPri" => MH_PWD_DTE,
    "dteJson" => [
        "identificacion" => [
            "version" => 3,
            "ambiente" => "00",
            "tipoDte" => "03",
            "numeroControl" => "DTE-03-M001P001-" . str_pad($idfactura, 15, "0", STR_PAD_LEFT),
            "codigoGeneracion" => $codigoGeneracion,
            "tipoOperacion" => 1,
            "tipoModelo" => 1,
            "tipoContingencia" => null,
            "motivoContin" => null,
            "fecEmi" => $fechaEmision,
            "horEmi" => $horaEmision,
            "tipoMoneda" => "USD"
        ],
        "documentoRelacionado" => null,
        "emisor" => [
            "nit" => MH_USER,
            "nrc" => str_replace('-', '', $Empresa['dato1']),
            "nombre" => $Empresa['nombre'],
            "codActividad" => $Empresa['giro'],
            "descActividad" => $ActividaE['descripcion'],
            "nombreComercial" => $Empresa['razon_social'],
            "tipoEstablecimiento" => "02",
            "direccion" => [
                "departamento" => $Empresa['dato6'],
                "municipio" => $Empresa['dato7'],
                "complemento" => $Empresa['direccion']
            ],
            "telefono" => $Empresa['telefono'],
            "correo" => $Empresa['email'],
            "codEstableMH" => "M001",
            "codEstable" => null,
            "codPuntoVentaMH" => "P001",
            "codPuntoVenta" => null
        ],
        "receptor" => [
            "nit" => $factura['dni'],
            "nrc" => $factura['dato1'],
            "nombre" => $factura['nombre'],
            "nombreComercial" => $factura['dato2'],
            "codActividad" => $factura['dato3'],
            "descActividad" => $row6['descripcion'],
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
            "totalGravada" => round($factura['subtotal'], 2),
            "subTotalVentas" => round($factura['subtotal'], 2),
            "descuNoSuj" => 0,
            "descuExenta" => 0,
            "descuGravada" => 0,
            "porcentajeDescuento" => 0,
            "totalDescu" => 0,
            "tributos" => [
                [
                    "codigo" => "20",
                    "descripcion" => "Impuesto al Valor Agregado 13%",
                    "valor" => round($totalIva, 2)
                ]
            ],
            "subTotal" => round($factura['subtotal'], 2),
            "ivaPerci1" => 0,
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
    include 'recepciondteCCF.php'; // Enviar automáticamente a Hacienda después de firmar
} else {
    echo json_encode(['success' => false, 'message' => 'Error al firmar documento', 'detalle' => $response]);
}