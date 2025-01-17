<?php
include('conexionfin.php');

$id = 1;
$meta = array();
if (!empty($id)) {
    $id = intval($id);
    $query = $conexion->query("SELECT * FROM configuracion WHERE id = $id");
    if ($query) {
        $configuracion = $query->fetch_assoc();
        if ($configuracion) {
            $meta = $configuracion;
        }
    }
}
?>
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title text-black"><i class="fa fa-pen"></i> Configuración (Perfil General de la Empresa)</h4>
        </div>
        <form id="configuracion">
            <div id="error"></div>
            <div class="form-body">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group has-feedback">
                                <label>NRC/NIT:</label>
                                <input type="text" name="txtDni" value="<?php echo isset($meta['dni']) ? htmlspecialchars($meta['dni']) : ''; ?>" id="txtDni" placeholder="Dni de la Empresa" required class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group has-feedback">
                                <label>Nombre:</label>
                                <input type="text" name="txtNombre" class="form-control" value="<?php echo isset($meta['nombre']) ? htmlspecialchars($meta['nombre']) : ''; ?>" id="txtNombre" placeholder="Nombre de la Empresa" required class="form-control">

                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group has-feedback">
                                <label>Razon Social:</label>
                                <input type="text" name="txtRSocial" class="form-control" value="<?php echo isset($meta['razon_social']) ? htmlspecialchars($meta['razon_social']) : ''; ?>" id="txtRSocial" placeholder="Razon Social de la Empresa">

                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group has-feedback">
                                <label>Teléfono:</label>
                                <input type="text" name="txtTelEmpresa" class="form-control" value="<?php echo isset($meta['telefono']) ? htmlspecialchars($meta['telefono']) : ''; ?>" id="txtTelEmpresa" placeholder="teléfono de la Empresa" required>

                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group has-feedback">
                                <label>Correo Electrónico:</label>
                                <input type="email" name="txtEmailEmpresa" class="form-control" value="<?php echo isset($meta['email']) ? htmlspecialchars($meta['email']) : ''; ?>" id="txtEmailEmpresa" placeholder="Correo de la Empresa" required>

                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group has-feedback">
                                <label>Dirección:</label>
                                <input type="text" name="txtDirEmpresa" class="form-control" value="<?php echo isset($meta['direccion']) ? htmlspecialchars($meta['direccion']) : ''; ?>" id="txtDirEmpresa" placeholder="Dirreción de la Empresa" required>

                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group has-feedback">
                                <label>Impuesto (%):</label>
                                <input type="text" name="txtIgv" class="form-control" value="<?php echo isset($meta['igv']) ? htmlspecialchars($meta['igv']) : ''; ?>" id="txtIgv" placeholder="IGV de la Empresa" required>

                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="control-label">Impresora</label>
                            <select name="impresion" id="impresion" class="form-control" required aria-required="true">
                                <option value="">-- SELECCIONE --</option>
                                <option value="58mm" <?php echo ($meta['impresion'] == "58mm") ? "selected" : ""; ?>>Impresion 58mm</option>
                                <option value="80mm" <?php echo ($meta['impresion'] == "80mm") ? "selected" : ""; ?>>Impresion 80mm</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="fileinput fileinput-new" data-provides="fileinput">
                                <div class="fileinput-preview thumbnail" data-trigger="fileinput" style="width: 210px; height: 110px;">
                                    <?php
                                    $logoPrincipal = file_exists("img/logo.png") ? "img/logo.png" : "img/ninguna.png";
                                    echo "<img id='logoPreview' src='$logoPrincipal' class='img-rounded' border='0' width='210' height='110' title='Logo' data-rel='tooltip'>";
                                    ?>
                                </div>
                                <div>
                                    <label for="imagen" class="btn btn-success">
                                        <span class="fileinput-exists"><i class="fa fa-paint-brush"></i> Logo Principal</span>
                                    </label>
                                    <input type="file" size="10" accept=".png" data-original-title="Subir Logo Principal" data-rel="tooltip" placeholder="Suba su Logo Principal" name="imagen" id="imagen" style="display: none;" onchange="previewImage(event, 'logoPreview', 'imagen')" />
                                    <small>
                                        <p>Para Subir el Logo Principal debe tener en cuenta:
                                            <br> * La Imagen debe ser extension.png<br> * La imagen no debe ser mayor de 200 KB
                                        </p>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="fileinput fileinput-new" data-provides="fileinput">
                                <div class="fileinput-preview thumbnail" data-trigger="fileinput" style="width: 210px; height: 110px;">
                                    <?php
                                    $logoPrincipal2 = file_exists("img/fondo.jpg") ? "img/fondo.jpg" : "img/ninguna.png";
                                    echo "<img id='logoPreview2' src='$logoPrincipal2' class='img-rounded' border='0' width='210' height='110' title='Logo' data-rel='tooltip'>";
                                    ?>
                                </div>
                                <div>
                                    <label for="imagen2" class="btn btn-warning">
                                        <span class="fileinput-exists"><i class="fa fa-paint-brush"></i> Fondo Principal</span>
                                    </label>
                                    <input type="file" size="10" accept=".png" data-original-title="Subir Logo Principal" 
                                    data-rel="tooltip" placeholder="Suba su Logo Principal" name="imagen2" id="imagen2" style="display: none;" 
                                    onchange="previewImage2(event, 'logoPreview2', 'imagen2')" />
                                    <small>
                                        <p>Para Subir el fondo Principal debe tener en cuenta:
                                            <br> * La Imagen debe ser extension.png<br> * La imagen no debe ser mayor de 200 KB
                                        </p>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div>
                        <button type="submit" class="btn btn-primary btn-lg" style="color:white;"><span class="fa fa-save"></span> Actualizar Datos</button>
                        <button class="btn btn-dark btn-lg" onclick="window.location.reload();" type="reset"><span class="fa fa-window-close"></span>
                            Cancelar</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function previewImage(event, previewId, inputId) {
        console.log("Función llamada");
        var input = event.target;
        var reader = new FileReader();
        reader.onload = function() {
            console.log("Imagen cargada");
            var preview = document.getElementById(previewId);
            var img = new Image();
            img.onload = function() {
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');
                canvas.width = 210;
                canvas.height = 110;
                ctx.drawImage(img, 0, 0, 210, 110);
                preview.src = canvas.toDataURL('image/png');
            };
            img.src = reader.result;
        }
        reader.onerror = function(event) {
            console.error("Error al cargar la imagen:", event.target.error);
        }
        if (input.files && input.files[0]) {
            reader.readAsDataURL(input.files[0]);
        } else {
            console.error("No se seleccionó ningún archivo.");
        }
    }
    function previewImage2(event, previewId, inputId) {
        console.log("Función llamada");
        var input = event.target;
        var reader = new FileReader();
        reader.onload = function() {
            console.log("Imagen cargada");
            var preview = document.getElementById(previewId);
            var img = new Image();
            img.onload = function() {
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');
                canvas.width = 210;
                canvas.height = 110;
                ctx.drawImage(img, 0, 0, 210, 110);
                preview.src = canvas.toDataURL('image/png');
            };
            img.src = reader.result;
        }
        reader.onerror = function(event) {
            console.error("Error al cargar la imagen:", event.target.error);
        }
        if (input.files && input.files[0]) {
            reader.readAsDataURL(input.files[0]);
        } else {
            console.error("No se seleccionó ningún archivo.");
        }
    }
    $(document).ready(function() {
        $('#configuracion').submit(function(e) {
            e.preventDefault(); // Evitar el envío del formulario por defecto

            // Deshabilitar el botón de enviar y mostrar el mensaje "Actualizando..."
            $('#configuracion button[type="submit"]').attr('disabled', true).html('Actualizando...');

            // Eliminar cualquier mensaje de error anterior
            if ($(this).find('.alert-danger').length > 0) {
                $(this).find('.alert-danger').remove();
            }

            // Realizar la petición AJAX
            $.ajax({
                url: 'ajax.php?action=save_configuracion',
                method: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                cache: false,
                error: function(err) {
                    console.log(err);
                    // Habilitar el botón de enviar y restaurar su texto original
                    $('#configuracion button[type="submit"]').removeAttr('disabled').html('Entrar al sistema');
                },
                success: function(resp) {
                    if (resp == 1) {
                        // Mostrar mensaje de éxito y recargar la página
                        Swal.fire({
                            title: 'Éxito!',
                            text: "El registro se guardó con éxito",
                            icon: 'success',
                            confirmButtonColor: '#28a745',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    }
                }
            });
        });
    });
</script>