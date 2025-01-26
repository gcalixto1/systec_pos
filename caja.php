<?php
include('conexionfin.php');
$query = $conexion->query("SELECT * FROM apertura_caja WHERE estado = 'A' LIMIT 1");
if ($query) {
    $caja = $query->fetch_assoc();
    if ($caja) {
        $meta = $caja;
    }
}
?>
<style>
    .boton_add {
        margin-top: -4%;
        margin-left: 75%;
        width: 25%;
    }

    .boton_add2 {
        margin-top: -4%;
        margin-left: 75%;
        width: 25%;
    }
</style>

<div class="container-fluid">
    <div class="card-header">
        <h4 class="card-title text-black"><i class="fa fa-money-bill-wave"></i> Gestion de Caja</h4>
    </div>
    <br>
    <div class="col-lg-12">

        <?php if ($_SESSION['login_rol'] == 1) : ?>
            <div class="text-right">
                <?php
                $qry = $conexion->query("SELECT * FROM apertura_caja");
                if ($qry->num_rows > 0) { // Verifica si hay registros
                    $hayCajaAbierta = false;
                    while ($row = $qry->fetch_assoc()) {
                        if ($row['estado'] == "A") {
                            $hayCajaAbierta = true;
                            break; // Sale del bucle si encuentra una caja abierta
                        }
                    }
                    if ($hayCajaAbierta) {
                        // Caja abierta encontrada
                        echo '<button class="btn btn-dark btn-lg" disabled type="button" id="apertura"><i class="fa fa-cash-register"></i> Apertura de Caja</button>';
                    } else {
                        // No hay caja abierta
                        echo '<button class="btn btn-success btn-lg" type="button" id="apertura"><i class="fa fa-cash-register"></i> Apertura de Caja</button>';
                    }
                } else {
                    // No hay registros en la tabla
                    echo '<button class="btn btn-success btn-lg" type="button" id="apertura"><i class="fa fa-cash-register"></i> Apertura de Caja</button>';
                }
                ?>
                <button class="btn btn-danger btn-lg" type="button" id="cierre"><i class="fa fa-columns"></i> Cierre de
                    Caja</button>
            </div>
            <br />
        <?php endif; ?>
        <input hidden name="idcajaA" id="idcajaA" class="form-control"
            value="<?php echo isset($meta['idcaja']) ? htmlspecialchars($meta['idcaja']) : ''; ?>">
        <table class="table table-bordered table-responsive" id="borrower-list">
            <colgroup>
                <col width="5%">
                <col width="10%">
                <col width="15%">
                <col width="10%">
                <col width="10%">
                <col width="10%">
                <col width="15%">
            </colgroup>
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th class="text-center">Numero de Apertura</th>
                    <th class="text-center">Fecha de Apertura</th>
                    <th class="text-center">Saldo Inicial</th>
                    <th class="text-center">Ingresos/Ventas y otros</th>
                    <th class="text-center">Egresos/Gasto</th>
                    <th class="text-center">Accion</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                $qry = $conexion->query("SELECT * FROM apertura_caja");
                while ($row = $qry->fetch_assoc()) :

                ?>
                    <tr>
                        <td>
                            <?php echo $i++ ?>
                        </td>
                        <td>
                            <?php echo $row['num_apertura'] ?>
                        </td>
                        <td>
                            <?php echo $row['fch_hora_apertura'] ?>
                        </td>
                        <td>
                            <?php echo $row['saldo_inicial'] ?>
                        </td>
                        <td>
                            <?php echo $row['saldo_venta_total'] ?>
                        </td>
                        <td>
                            <?php echo $row['gasto'] ?>
                        </td>

                        <td style="white-space: nowrap;">
                            <center>
                                <button class="btn btn-default btn-md edit_borrower" type="button"
                                    data-id="<?php echo $row['idcaja'] ?>">
                                    <i class="fa fa-list"></i>
                                </button>
                            </center>
                        </td>

                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    </div>
</div>

<script>
    var id_caja = document.getElementById("idcajaA").value;
    $('#borrower-list').dataTable()
    $('#apertura').click(function() {
        uni_modal("Apertura de Caja", "apertura_caja.php")
    })
    $('#cierre').click(function() {
        uni_modal_caja("Cierre de Caja", "cierre_caja.php?idcaja=" + id_caja)
    })
    $('#borrower-list').on('click', '.edit_borrower', function() {
        uni_modal_documentos("Detalle de caja", "detalle_caja.php?idcaja=" + $(this).attr('data-id'))
    })
</script>