<?php
include('conexionfin.php');
?>
<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f5f5f5;
}

.container {
    display: flex;
    height: 100vh;
}

.products {
    width: 60%;
    padding: 20px;
    background: #ffffff;
    overflow-y: auto;
}

.search-bar {
    width: 100%;
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
}

.product-card {
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 10px;
    background: #f9f9f9;
}

.product-card img {
    width: 100%;
    border-radius: 10px;
}

.product-card p {
    margin: 10px 0;
    font-size: 14px;
}

.product-card button {
    padding: 5px 10px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.product-card button:hover {
    background-color: #0056b3;
}

.cart2 {
    width: 60%;
    padding: -25px;
    background: #f0f0f0;
    overflow-y: auto;
}

.cart table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.cart table th,
.cart table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.cart-summary {
    text-align: right;
}

.cart-summary p {
    margin: 5px 0;
}

.btn {
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.btn.cancel {
    background-color: #dc3545;
    color: white;
}

.btn.process {
    background-color: #28a745;
    color: white;
}

.btn:hover {
    opacity: 0.9;
}

.product-card {
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 10px;
    background: #f9f9f9;
    cursor: pointer;
    /* Cambia el cursor para indicar que es clickeable */
    transition: background-color 0.3s ease;
}

.product-card:hover {
    background-color: #e0e0e0;
    /* Cambia ligeramente el fondo al pasar el mouse */
}

.tooltip-suggestions {
    position: absolute;
    background-color: #fff;
    border: 1px solid #ccc;
    z-index: 1000;
    width: 100%;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    max-height: 200px;
    overflow-y: auto;
    display: none;
    /* Oculto por defecto */
}

.tooltip-suggestions div {
    padding: 8px;
    cursor: pointer;
}

.tooltip-suggestions div:hover {
    background-color: #f4f4f4;
}
</style>
<div class="container col-lg-12">
    <!-- Panel Izquierdo: Productos -->
    <div class="products">
        <!-- Barra de búsqueda -->
        <div class="form-group has-feedback">
            <div class="input-group">
                <input type="text" class="search-bar" name="busquedaproductov" id="busquedaproductov" autocomplete="off"
                    placeholder="Realice la Búsqueda por Código de barra o Nombre del producto"
                    onkeyup="filterProducts()">
            </div>
        </div>
        <div class="product-grid" id="productContainer">
            <?php
            // Consulta para obtener los productos desde la base de datos
            $productos = $conexion->query("SELECT * FROM producto LIMIT 20");

            while ($producto = $productos->fetch_assoc()): ?>
            <div class="product-card"
                onclick="addToCart(<?= $producto['codproducto'] ?>, '<?= addslashes($producto['descripcion']) ?>', <?= $producto['precio'] ?>)"
                data-name="<?= strtolower($producto['descripcion']) ?>"
                data-code="<?= strtolower($producto['codBarra']) ?>">
                <img src="<?= $producto['imagen_producto'] ?>" alt="<?= $producto['descripcion'] ?>">
                <p><?= $producto['descripcion'] ?></p>
                <p>$ <?= $producto['precio'] ?></p>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Panel Derecho: Carrito -->
    <form class="cart2" method="post" action="#" name="saveventa" id="saveventa">
        <div class="cart">
            <div class="col-md-12">
                <div class="form-group has-feedback">
                    <label class="control-label">Búsqueda de Clientes: </label>
                    <div class="input-group">
                        <input type="hidden" name="codcliente" id="codcliente" value="0">
                        <input type="hidden" id="csrf_token" name="csrf_token"
                            value="<?php echo md5($_SESSION['login_idusuario']); ?>">
                        <input type="text" class="form-control" name="busqueda" id="busqueda"
                            placeholder="Ingrese Criterio para la Búsqueda del Cliente" autocomplete="off" />
                        <span class="input-group-text" style="cursor: pointer;background:#28a745;" id="new_cliente"
                            onclick="AgregarCliente()"><i class="fa fa-user-plus" style="color:white;"></i>
                        </span>
                        <div id="suggestions" class="tooltip-suggestions"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group has-feedback">
                    <label class="control-label">Seleccione Comprobante: </label>
                    <select name="prefix" id="prefix" class="form-control" aria-required="true">
                        <option value=""> -- SELECCIONE -- </option>
                        <?php
                        require_once("includes/class.php");
                        $consecutivo = new Action();
                        $consecutivo = $consecutivo->Listarconsecutivos();
                        for ($i = 0; $i < sizeof($consecutivo); $i++) { ?>
                        <option value="<?php echo $consecutivo[$i]['codigo_consecutivo']; ?>">
                            <?php echo $consecutivo[$i]['descripcionconse'] ?>
                        </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group has-feedback">
                    <label class="control-label">Forma de Pago: </label>
                    <select name="forma_pago" id="forma_pago" class="form-control" aria-required="true">
                        <option value=""> -- SELECCIONE -- </option>
                        <?php
                        require_once("includes/class.php");
                        $pago = new Action();
                        $pago = $pago->ListarMediosPagos();
                        for ($i = 0; $i < sizeof($pago); $i++) { ?>
                        <option value="<?php echo $pago[$i]['codigo']; ?>">
                            <?php echo $pago[$i]['medio_pago'] ?>
                        </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cart-items">
                    <!-- Aquí se agregan los productos dinámicamente -->
                </tbody>
            </table>
            <div class="cart-summary">
                <p>SubTotal: <span id="subtotal">$ 0.00</span></p>
                <p>I.V.A.: <span id="igv">$ 0.00</span></p>
                <p style="font-weight: 700; font-size:25px;">Total: <span id="total">$ 0.00</span></p>
                <div class="text-right">
                    <?php
                    include('conexionfin.php');
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
                            echo '<button type="submit" id="idpagar"   class="btn btn-warning"><span class="fa fa-calculator"></span> Pagar</button>';
                        } else {
                            // No hay caja abierta
                            echo '<button type="submit" id="idpagar" disabled class="btn btn-dark"><span class="fa fa-calculator"></span> Pagar</button>';
                        }
                    } else {
                        // No hay registros en la tabla
                        echo '<button type="submit" id="idpagar" disabled class="btn btn-dark"><span class="fa fa-calculator"></span> Pagar</button>';
                    }
                    ?>
                    <button type="button" class="btn btn-dark" id="vaciar" onclick="Refresh()"><span
                            class="fa fa-trash"></span>
                        Cancelar</button>
                </div>
            </div>
        </div>
    </form>

</div>

<script src="js/scriptscar.js"></script>
<script src="assets/script/autocompleto.js"></script>
<script>
function AgregarCliente() {
    uni_modal("Gestion Clientes", "manage_clientes_venta.php")
}

function filterProducts() {
    const searchInput = document.getElementById('busquedaproductov').value.toLowerCase();
    const products = document.querySelectorAll('.product-card');

    products.forEach(product => {
        const name = product.getAttribute('data-name');
        const code = product.getAttribute('data-code');

        if (name.includes(searchInput) || code.includes(searchInput)) {
            product.style.display = ''; // Mostrar producto
        } else {
            product.style.display = 'none'; // Ocultar producto
        }
    });
}
</script>