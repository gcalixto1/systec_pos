<?php
include ('conexionfin.php');

$id = isset ($_GET['idproveedor']) ? $_GET['idproveedor'] : '';

$meta = array();



if (!empty ($id)) {

    $id = intval($id);

    $query = $conexion->query("SELECT * FROM proveedor WHERE idproveedor = $id");

    if ($query) {

        $proveedor = $query->fetch_assoc();

        if ($proveedor) {

            $meta = $proveedor;

        }

    }

}
?>
<div class="container-fluid">
    <div class="card">
        <form class="form form-material" method="post" action="#" name="saveproveedor" id="saveproveedor">
            <div id="save">
            </div>
            <div class="form-body">
                <div class="card-body">
                    <div class="row">
                        <div class="form-group has-feedback">
                            <input type="hidden" name="id"
                                value="<?php echo isset ($_GET['idproveedor']) ? $_GET['idproveedor'] : '' ?>">
                        </div>
                        <div class="form-group col-md-12">
                            <label for="name">Nombre Completo</label>
                            <input type="text" name="proveedor" id="proveedor" class="form-control"
                                value="<?php echo isset ($meta['proveedor']) ? htmlspecialchars($meta['proveedor']) : ''; ?>"
                                required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="name">Documento</label>
                            <input type="text" name="contacto" id="contacto" class="form-control"
                                value="<?php echo isset ($meta['contacto']) ? htmlspecialchars($meta['contacto']) : ''; ?>"
                                required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="name">Telefono</label>
                            <input type="text" name="telefono" id="telefono" class="form-control"
                                value="<?php echo isset ($meta['telefono']) ? htmlspecialchars($meta['telefono']) : ''; ?>"
                                required>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="name">Dirección</label>
                            <input type="text" name="direccion" id="direccion" class="form-control"
                                value="<?php echo isset ($meta['direccion']) ? htmlspecialchars($meta['direccion']) : ''; ?>"
                                required>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>

    $('#saveproveedor').submit(function (e) {
        e.preventDefault();
        var isValid = true;
        $('#saveproveedor input[required]').each(function () {
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
                url: 'ajax.php?action=save_proveedores',
                method: 'POST',
                data: $(this).serialize(),
                success: function (resp) {
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
    function cargarMunicipios() {
        var id_provincia = document.getElementById("id_provincia").value;

        // Realizar la solicitud AJAX para obtener los municipios
        fetch('departamentos_vista.php?id_departamento=' + id_provincia)
            .then(response => response.json())
            .then(data => {
                var selectMunicipios = document.getElementById("id_departamento");
                // Limpiar las opciones anteriores
                selectMunicipios.innerHTML = '<option value="0"> -- SELECCIONE -- </option>';
                // Agregar las opciones de municipios correspondientes al departamento seleccionado
                data.forEach(municipio => {
                    var option = document.createElement("option");
                    option.text = municipio.departamento;
                    option.value = municipio.id_departamento;
                    selectMunicipios.appendChild(option);
                });
            })
            .catch(error => console.error('Error:', error));
    }
</script>