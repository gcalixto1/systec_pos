<?php
include('conexionfin.php');

$id = isset($_GET['idfactura']) ? $_GET['idfactura'] : '';
$meta = array();
if (!empty($id)) {
    $id = intval($id);
    $query = $conexion->query("SELECT * FROM factura inner join cliente on cliente.idcliente = factura.idcliente WHERE id = $id");
    if ($query) {
        $cliente = $query->fetch_assoc();
        if ($cliente) {
            $meta = $cliente;
            // echo "<pre>";
            // print_r($meta);
            // echo "</pre>";
        }
    }
}
if ($meta['tipofactura'] == "fcf") {
    include 'dte.php';
} else if ($meta['tipofactura'] == "ccf") {
    include 'dteCCF.php';
}

?>

<?php ?>
<!-- Begin Page Content -->
<div class="container-fluid">
    <form class="form form-material" method="post" action="#" name="pagoForm" id="pagoForm">
        <div class="form-content">
            <div class="form-body">
                <input type="hidden" name="id" id="id"
                    value="<?php echo isset($_GET['idfactura']) ? $_GET['idfactura'] : '' ?>"">
                <input type=" hidden" name="montodevuelto" hidden id="montodevuelto" value="0.00">
                <input type="hidden" name="creditoinicial" id="creditoinicial" value="0.00">
                <input type="hidden" name="creditodisponible" id="creditodisponible" value="0.00">
                <input type="hidden" name="abonototal" id="abonototal" value="0.00">

                <div class="row">
                    <div class="col-md-4">
                        <h6 class="mb-0 font-light">Total a Pagar</h6>
                        <h3 class="mb-0 font-medium"><label id="TextImporte"
                                name="TextImporte"><?php echo isset($meta['totalpagar']) ? htmlspecialchars($meta['totalpagar']) : ''; ?></label>
                        </h3>
                    </div>
                    <div class="col-md-4">
                        <h6 class="mb-0 font-light">Total Recibido</h6>
                        <h3 class="mb-0 font-medium"><label id="TextPagado" name="TextPagado">0.00</label></h3>
                    </div>
                    <div class="col-md-4">
                        <h6 class="mb-0 font-light">Total Cambio</h6>
                        <h3 class="mb-0 font-medium"><label id="TextCambio" name="TextCambio">0.00</label></h3>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <h6 class="mb-0 font-light">Nombre del Cliente</h6>
                        <h5 class="mb-0 font-medium"><label id="TextCliente"
                                name="TextCliente"><?php echo isset($meta['nombre']) ? htmlspecialchars($meta['nombre']) : ''; ?></label></label>
                        </h5>
                    </div>
                </div>
                <hr>
                <div id="condiciones">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group has-feedback">
                                <label class="control-label">Monto Recibido: </label>
                                <input class="form-control" type="text" name="montopagado" id="montopagado"
                                    autocomplete="off" placeholder="Monto Recibido" step="0.01" value="0.00"
                                    oninput="calcularCambio()" aria-required="true">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="mediopagos"></div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group has-feedback">
                            <label class="control-label">Observaciones: </label>
                            <input type="text" class="form-control" name="observaciones" id="observaciones"
                                onkeyup="this.value=this.value.toUpperCase();"
                                placeholder="Ingrese Observaciones en Venta" autocomplete="off" aria-required="true">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    function calcularCambio() {
        // Obtener los valores de los campos
        var importe = parseFloat(document.getElementById("TextImporte").textContent) || 0;
        var pagado = parseFloat(document.getElementById("montopagado").value) || 0;

        // Calcular la diferencia
        var cambio = pagado - importe;

        // Mostrar el resultado en el campo TextCambio y asegurarse de que los decimales sean siempre 2
        document.getElementById("TextCambio").textContent = cambio.toFixed(2);
        document.getElementById("TextPagado").textContent = pagado.toFixed(2);
    }

    $('#pagoForm').submit(function (e) {
        e.preventDefault();
        start_load();
        $.ajax({
            url: 'ajax.php?action=save_venta_completa',
            method: 'POST',
            data: $(this).serialize(),
            success: function (resp) {
                resp = JSON.parse(resp);
                if (resp.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'Venta Registrada',
                        icon: 'success',
                        confirmButtonColor: '#28a745',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const win1 = window.open(resp.ticket_url, '_blank');
                            const win2 = window.open(resp.facturaElectronica, '_blank');

                            if (!win1 || !win2) {
                                alert(
                                    "Por favor habilita las ventanas emergentes (pop-ups) para este sitio."
                                );
                            } else {
                                setTimeout(() => location.reload(), 500);
                            }
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: resp.message || 'Ocurrió un error al registrar la venta.',
                        icon: 'error',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function () {
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo conectar con el servidor.',
                    icon: 'error',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
</script>