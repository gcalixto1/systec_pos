<?php
include 'conexionfin.php';

$documento = $_GET['documento'] ?? '';

// Consulta base
$query = "SELECT factura.id, factura.fechafactura, factura.numerofactura, consecutivos.descripcionconse, 
                respuestadte.codigoGeneracion, cliente.nombre, cliente.dni, factura.totalpagar, medio_pago.medio_pago
          FROM factura
          INNER JOIN cliente ON cliente.idcliente = factura.idcliente
          INNER JOIN consecutivos ON consecutivos.codigo_consecutivo = factura.tipofactura
          INNER JOIN respuestadte ON respuestadte.id_factura = factura.id
          INNER JOIN medio_pago ON medio_pago.codigo = factura.forma_pago
          WHERE 1=1 AND respuestadte.estado = 'PROCESADO'";

if (!empty($documento)) {
    $query .= " AND cliente.dni LIKE '%$documento%'";
}

$query .= " ORDER BY factura.fechafactura DESC";

$resultado = $conexion->query($query);
if (!$resultado) {
    die("Error en la consulta: " . $conexion->error);
}
?>

<div class="table-responsive">
    <table id="tablaVentas" class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>FECHA FACTURA</th>
                <th>TIPO COMPROBANTE</th>
                <th>CODIGO COMPROBANTE</th>
                <th>CLIENTE</th>
                <th>TOTAL FACTURA</th>
                <th>METODO DE PAGO</th>
                <th>ACCION</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $resultado->fetch_assoc()) { ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($row['fechafactura'])) ?></td>
                <td><?= htmlspecialchars($row['descripcionconse']) ?></td>
                <td><?= htmlspecialchars($row['codigoGeneracion']) ?></td>
                <td><?= htmlspecialchars($row['nombre']) ?></td>
                <td><?= htmlspecialchars("$ " . $row['totalpagar']) ?></td>
                <td><?= htmlspecialchars($row['medio_pago']) ?></td>
                <td>
                    <button type="button" class="btn btn-primary btn-agregar-documento"
                        data-codigo="<?= $row['codigoGeneracion'] ?>"
                        data-fecha="<?= date('d/m/Y H:i', strtotime($row['fechafactura'])) ?>"
                        data-monto="<?= $row['totalpagar'] ?>">
                        Seleccionar
                    </button>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- DataTable -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

<script>
$(document).ready(function() {
    // Inicializar DataTable de la tabla modal
    $('#tablaVentas').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        order: [
            [6, 'desc']
        ],
        pageLength: 5
    });

    // Acción al seleccionar factura desde modal
    $(document).on('click', '.btn-agregar-documento', function() {
        const codigo = $(this).data('codigo');
        const monto = $(this).data('monto');
        const fecha = $(this).data('fecha');

        // Agregar a la tabla de documentos relacionados
        const fila = `
        <tr>
            <td>Comprobante de Credito Fiscal</td>
            <td>Electronico</td>
            <td>${codigo}</td>
            <td>${fecha}</td>
            <td><button type="button" class="btn btn-danger btn-sm eliminar-fila"><i class="fas fa-trash"></i></button></td>
        </tr>
    `;

        $('#tablaDocumentosRelacionados tbody').append(fila);

        let montoActual = parseFloat($('#monto').val()) || 0;
        let nuevoMonto = parseFloat(monto) || 0;
        let total = montoActual + nuevoMonto;
        $('#monto').val(total.toFixed(2));

        // Cierra el modal de forma segura (solo si realmente necesitas hacerlo)
        let modal = bootstrap.Modal.getInstance(document.getElementById('modalFacturas'));
        if (modal) modal.hide(); // Bootstrap 5 way
    });

    // Eliminar fila de documentos relacionados
    $(document).on('click', '.eliminar-fila', function() {
        $(this).closest('tr').remove();
    });
});
</script>