<?php include 'conexionfin.php'; ?>

<form class="form form-material" method="post" action="#" name="saveSujetoExcluido" id="saveSujetoExcluido">
    <div class="container-fluid">
        <div id="spinner"
            style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background-color:rgba(255, 255, 255, 0.77);z-index:9999;text-align:center;padding-top:200px;font-size:24px;">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
            <p>Procesando Contingencia</p>
        </div>
        <div class="card-header">
            <h4 class="card-title text-black"><i class="fa fa-file-alt"></i> Contingencias</h4>
        </div>
        <div class="row">
            <div class="form-group col-md-3">
                <label>Fecha Inicio Contingencia:</label>
                <input type="date" class="form-control" name="fechaIni" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group col-md-3">
                <label>Fecha Fin Contingencia:</label>
                <input type="date" class="form-control" name="fechaFin" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group col-md-3">
                <label>Hora Inicio Contingencia:</label>
                <input type="time" class="form-control" name="horaIni" value="<?= date('H:i') ?>">
            </div>
            <div class="form-group col-md-3">
                <label>Hora Fin Contingencia:</label>
                <input type="time" class="form-control" name="horaFin" value="<?= date('H:i') ?>">
            </div>
            <div class="form-group col-md-3">
                <label>Tipo de Contingencia :</label>
                <select name="tcontingencia" id="tcontingencia" class="form-control select2">
                    <option value="">-- SELECCIONE --</option>
                    <?php
                    require_once("includes/class.php");
                    $pago = new Action();
                    $pago = $pago->ListarCtaContingencias();
                    foreach ($pago as $p) {
                        echo "<option value='{$p['codigo']}'>{$p['valores']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group col-md-6"></div>
            <div class="form-group col-md-12"></div>
            <div class="form-group col-md-12"></div>

            <div class="form-group col-md-12 mt-3">
                <button type="submit" class="btn btn-success"><i class="fa fa-archive"></i> Transmitir
                    Factura</button>
            </div>
        </div>
    </div>
</form>



<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>