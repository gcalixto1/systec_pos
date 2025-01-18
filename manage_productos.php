<?php
include('conexionfin.php');

$id = isset($_GET['codproducto']) ? $_GET['codproducto'] : '';

$meta = array();

if (!empty($id)) {

    $id = intval($id);

    $query = $conexion->query("SELECT * FROM producto WHERE codproducto = $id");

    if ($query) {

        $producto = $query->fetch_assoc();

        if ($producto) {
            $meta = $producto;
        }
    }
}
?>
<style>
.contenedor-imagen {
    width: 135px;
    height: 135px;
    overflow: hidden;
}

.contenedor-imagen img {
    width: 75%;
    height: auto;
}

.cont-imagen {

    border: 1px solid #ddd;
    border-radius: 8px;
    width: 30%;
    text-align: center;
    margin-left: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.image-card {
    position: relative;
}

.image-preview {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 10px;
    margin: 0 auto;
}

.image-name {
    margin: 8px 0 4px;
    font-size: 14px;
    font-weight: bold;
}

.image-size {
    font-size: 8px;
    color: #888;
    margin-bottom: 8px;
}

.image-actions {
    display: flex;
    justify-content: center;
    gap: 8px;
}

.action-button {
    background-color: #007bff;
    border: none;
    color: #fff;
    padding: 8px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 8px;
}

.action-button:hover {
    background-color: #0056b3;
}

.controls {
    margin-top: 16px;
    display: flex;
    gap: 8px;
    align-items: center;
}

.image-input {
    flex: 1;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 8px;
}

.control-button {
    padding: 8px 8px 8px 8px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.control-button.delete {
    background-color: #dc3545;
    color: #fff;
}

.control-button.select {
    background-color: #28a745;
    color: #fff;
}

.control-button:hover {
    opacity: 0.9;
}

.marform {
    margin-left: 15px;
}
</style>
<div class="container-fluid">
    <div class="card">
        <form class="form form-material" method="post" action="#" enctype="multipart/form-data" name="saveproductos"
            id="saveproductos">
            <div id="save">
            </div>
            <div class="form-body">
                <div class="card-body">
                    <div class="row">
                        <div class="form-group has-feedback">
                            <input type="hidden" name="id"
                                value="<?php echo isset($_GET['codproducto']) ? $_GET['codproducto'] : '' ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="name">Codigo de Barra </label>
                            <input type="text" name="codBarra" id="codBarra" class="form-control"
                                placeholder="748596758473"
                                value="<?php echo isset($meta['codBarra']) ? htmlspecialchars($meta['codBarra']) : ''; ?>"
                                required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="name">Presentacion</label>
                            <select name="prop2" id="prop2" class="form-control" required aria-required="true">
                                <option value="0">-- SELECCIONE --</option>
                                <?php
                                require_once("includes/class.php");
                                $pro = new Action();
                                $categoria = $pro->ListarPresentacion();
                                foreach ($categoria as $prov) {
                                    $selected = isset($meta['prop2']) && $meta['prop2'] == $prov['presentacion'] ? 'selected' : '';
                                ?>
                                <option value="<?php echo $prov['presentacion']; ?>" <?php echo $selected; ?>>
                                    <?php echo $prov['presentacion']; ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="control-label">Categoria</label>
                            <select name="categoria" id="categoria" class="form-control" required aria-required="true">
                                <option value="0">-- SELECCIONE --</option>
                                <?php
                                require_once("includes/class.php");
                                $pro = new Action();
                                $categoria = $pro->ListarCategorias();
                                foreach ($categoria as $prov) {
                                    $selected = isset($meta['categoria']) && $meta['categoria'] == $prov['categoria_id'] ? 'selected' : '';
                                ?>
                                <option value="<?php echo $prov['categoria_id']; ?>" <?php echo $selected; ?>>
                                    <?php echo $prov['categoria_des']; ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="name">Nombre/descripcion del producto</label>
                            <input type="text" name="descripcion" id="descripcion" class="form-control"
                                placeholder="Ingrese nombre o descripcion"
                                value="<?php echo isset($meta['descripcion']) ? htmlspecialchars($meta['descripcion']) : ''; ?>"
                                required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="name">Precio de Compra</label>
                            <div class="input-group mb-12">
                                <span class="input-group-text" id="basic-addon1">$</span>
                                <input type="text" class="form-control" name="precio_compra" id="precio_compra"
                                    placeholder="Ingrese Costo" autocomplete="off"
                                    <?php if (isset($meta['precio_compra'])) { ?>
                                    value="<?php echo $meta['precio_compra']; ?>" <?php } ?> />
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="name">Margen (%)</label>
                            <input type="text" name="prop1" placeholder="15" id="prop1" class="form-control"
                                value="<?php echo isset($meta['prop1']) ? htmlspecialchars($meta['prop1']) : ''; ?>"
                                required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="name">Precio de Venta</label>
                            <div class="input-group mb-12">
                                <span class="input-group-text" id="basic-addon1">$</span>
                                <input type="text" class="form-control" name="precio" id="precio"
                                    placeholder="Ingrese P.V" autocomplete="off" <?php if (isset($meta['precio'])) { ?>
                                    value="<?php echo $meta['precio']; ?>" <?php } ?> />
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="name">Stock actual</label>
                            <input type="text" name="existencia" id="existencia" class="form-control"
                                placeholder="Ingrese cantidad"
                                value="<?php echo isset($meta['existencia']) ? htmlspecialchars($meta['existencia']) : ''; ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="name">Stock Minimo</label>
                            <input type="text" name="exis_min" id="exis_min" class="form-control"
                                placeholder="Ingrese cantidad"
                                value="<?php echo isset($meta['exis_min']) ? htmlspecialchars($meta['exis_min']) : ''; ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="name">Estado</label>
                            <input type="text" name="prop3" id="prop3" class="form-control" placeholder="Activo"
                                value="<?php echo isset($meta['prop3']) ? htmlspecialchars($meta['prop3']) : ''; ?>"
                                required>
                        </div>
                        <div class="form-group col-md-12">
                            <label class="control-label">Proveedor</label>
                            <select name="proveedor" id="proveedor" class="form-control" required aria-required="true">
                                <option value="0">-- SELECCIONE --</option>
                                <?php
                                require_once("includes/class.php");
                                $pro = new Action();
                                $categoria = $pro->ListarProveedores();
                                foreach ($categoria as $prov) {
                                    $selected = isset($meta['proveedor']) && $meta['proveedor'] == $prov['idproveedor'] ? 'selected' : '';
                                ?>
                                <option value="<?php echo $prov['idproveedor']; ?>" <?php echo $selected; ?>>
                                    <?php echo $prov['proveedor']; ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="cont-imagen">
                            <div class="">
                                <label>Imagen Producto</label>
                                <div class="image-card">
                                    <?php
                                    $imagenBD = isset($meta['imagen_producto']) ? htmlspecialchars($meta['imagen_producto']) : '';
                                    $imagenmostrar = file_exists("$imagenBD") ? "$imagenBD" : "img/ninguna.png";
                                    echo "<img src='$imagenmostrar' alt='Imagen de cliente' class='image-preview' id='imagePreview'>";
                                    ?>
                                </div>
                                <div class="controls">
                                    <input type="text" id="imageInput" class="image-input" value="Seleccione imagen"
                                        hidden>
                                    <button type="button" class="control-button delete"
                                        onclick="clearImage()">Borrar</button>
                                    <button type="button" class="control-button select"
                                        onclick="selectImage()">Seleccionar</button>
                                    <input type="file" id="imagen_producto" name="imagen_producto"
                                        style="display: none;" accept="image/*" onchange="handleFileSelect(event)">
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-4 ">
                            <label for="name">Fecha Vencimiento</label>
                            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" class="form-control"
                                value="<?php echo isset($meta['fecha_vencimiento']) ? htmlspecialchars($meta['fecha_vencimiento']) : ''; ?>"
                                required>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    </form>
</div>
</div>

<script type="text/javascript" src="assets/script/script2.js"></script>
<!-- <script src="assets/plugins/fileupload/bootstrap-fileupload.min.js"></script> -->
<script type="text/javascript" src="assets/script/titulos.js"></script>
<script type="text/javascript" src="assets/script/script2.js"></script>
<script type="text/javascript" src="assets/script/validation.min.js"></script>
<script src="assets/plugins/noty/packaged/jquery.noty.packaged.min.js"></script>
<script>
$('#saveproductos').submit(function(e) {

    e.preventDefault();
    var formData = new FormData(this);
    var isValid = true;
    $('#saveproductos input[required]').each(function() {
        if ($(this).val().trim() === '') {
            isValid = false;
            Swal.fire({
                title: 'Error!',
                text: 'Todos los campos son obligatorios. Por favor, complete el campo vacio',
                icon: 'error',
                confirmButtonColor: '#d33',
                confirmButtonText: 'OK'
            });
            return false;
        }
    });
    if (isValid) {
        start_load();
        $.ajax({
            url: 'ajax.php?action=save_productos',
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(resp) {
                if (resp == 1) {
                    Swal.fire({
                        title: 'Éxito!',
                        text: 'El registro se guardó con éxito.',
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
    }
});

function selectImage() {
    const fileInput = document.getElementById('imagen_producto');
    fileInput.click(); // Simula un clic en el input de archivo oculto
}

// Función para manejar la selección de archivo
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Actualiza la vista previa de la imagen
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('imageName').textContent = file.name;
            document.getElementById('imageSize').textContent = `(${(file.size / 1024).toFixed(2)} KB)`;
        };
        reader.readAsDataURL(file);
    }
}

// Función para borrar la imagen seleccionada
function clearImage() {
    document.getElementById('imagePreview').src = 'img/ninguna.png';
    document.getElementById('imageName').textContent = 'No hay imagen';
    document.getElementById('imageSize').textContent = '(0.00KB)';
    document.getElementById('imagen_producto').value = ''; // Limpia el input de archivo
    document.getElementById('imageInput').value = 'Seleccione imagen';
}
</script>