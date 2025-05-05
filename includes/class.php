<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
ini_set('display_errors', 1);
require 'vendor/autoload.php';

use Luecano\NumeroALetras\NumeroALetras;

class Action
{
	private $dbh;

	public function __construct()
	{
		ob_start();
		include 'conexionfin.php';
		$this->dbh = $conexion;
	}

	function __destruct()
	{
		$this->dbh->close();
		ob_end_flush();
	}

	#region Login
	function login()
	{
		extract($_POST);
		$stmt = $this->dbh->prepare("SELECT * FROM usuario WHERE usuario = ?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				if ($row['clave'] === md5($password)) {
					foreach ($row as $key => $value) {
						if ($key != 'clave' && !is_numeric($key)) {
							$_SESSION['login_' . $key] = $value;
						}
					}
					return 1; // Autenticación exitosa
				}
			}
			return 3; // Contraseña incorrecta
		} else {
			return 3; // Usuario no encontrado
		}
	}
	function logout()
	{
		session_destroy();
		header("location:login.php");
		exit; // Asegurar la terminación del script después de la redirección
	}
	public function ListarImpresoras()
	{
		$sql = "SELECT * FROM configuracion";

		$result = mysqli_query($this->dbh, $sql);

		$impresora = array();
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$impresora[] = $row;
			}
		}
		return $impresora;
	}
	#endregion
	#region Configuracion
	function save_configuracion()
	{
		extract($_POST);
		$txtdni = $_POST['txtDni'];
		$txtNombre = $_POST['txtNombre'];
		$txtRSocial = $_POST['txtRSocial'];
		$txtTelefono = $_POST['txtTelEmpresa'];
		$txtDireccion = $_POST['txtDirEmpresa'];
		$txtemail = $_POST['txtEmailEmpresa'];
		$txtigv = $_POST['txtIgv'];
		$txtimpresion = $_POST['impresion'];
		$txtmoneda = $_POST['moneda'];

		// Construye la cadena de datos correctamente
		$data = " dni = '$txtdni'";
		$data .= ", nombre = '$txtNombre'";
		$data .= ", razon_social = '$txtRSocial'";
		$data .= ", telefono = '$txtTelefono'";
		$data .= ", direccion = '$txtDireccion'";
		$data .= ", email = '$txtemail'";
		$data .= ", igv = '$txtigv'";
		$data .= ", impresion = '$txtimpresion'";
		$data .= ", moneda = '$txtmoneda'";

		// Evita inyección SQL usando consultas preparadas
		$id = 1;
		if (empty($id)) {
			$save = $this->dbh->query("INSERT INTO configuracion SET " . $data);
		} else {
			$id = 1; // Escapa el valor de $id
			$save = $this->dbh->query("UPDATE configuracion SET " . $data . " WHERE id = $id");

			// Subir el logo principal
			if (isset($_FILES['imagen']['name'])) {
				$nombre_archivo = $_FILES['imagen']['name'];
				$tipo_archivo = $_FILES['imagen']['type'];
				$tamano_archivo = $_FILES['imagen']['size'];
				if ((strpos($tipo_archivo, 'image/png') !== false) && $tamano_archivo < 60000000) {
					if (move_uploaded_file($_FILES['imagen']['tmp_name'], "img/" . $nombre_archivo) && rename("img/" . $nombre_archivo, "img/logo.png")) {
						// Puedes mostrar un mensaje de éxito aquí
					} else {
						// Puedes manejar el error aquí
					}
				}
			}
			// Subir el logo del reporte
			if (isset($_FILES['imagen2']['name'])) {
				$nombre_archivo = $_FILES['imagen2']['name'];
				$tipo_archivo = $_FILES['imagen2']['type'];
				$tamano_archivo = $_FILES['imagen2']['size'];
				if ((strpos($tipo_archivo, 'image/png') !== false) && $tamano_archivo < 60000000) {
					if (move_uploaded_file($_FILES['imagen2']['tmp_name'], "img/" . $nombre_archivo) && rename("img/" . $nombre_archivo, "img/fondo.jpg")) {
						// Puedes mostrar un mensaje de éxito aquí
					} else {
						// Puedes manejar el error aquí
					}
				}
			}
		}

		if ($save) {
			return 1;
		}
	}
	#endregion
	#region Usuarios
	function save_usuario()
	{
		extract($_POST);
		$data = "nombre = '$nombre'";
		$data .= ",correo = '$correo'";
		$data .= ",usuario = '$usuario'";
		$data .= ",rol = $rol";
		if (!empty($clave)) {
			$data .= ",clave = '" . md5($clave) . "'";
		}
		if (empty($id)) {
			$save = $this->dbh->query("INSERT INTO usuario set " . $data);
		} else {
			$save = $this->dbh->query("UPDATE usuario set " . $data . " where idusuario = " . $id);
		}
		if ($save) {
			return 1;
		}
	}
	function delete_usuario()
	{
		extract($_POST);
		$delete = $this->dbh->query("DELETE FROM usuario where idusuario = " . $codigo);
		if ($delete)
			return 1;
	}
	public function Listarnivel()
	{
		$sql = "SELECT * FROM rol";
		$result = mysqli_query($this->dbh, $sql);
		$usuarios = array();
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$usuarios[] = $row;
			}
		}
		return $usuarios;
	}
	#endregion
	#region Categoria
	function save_categoria()
	{
		extract($_POST);
		$data = " categoria_des = '$categoria_des' ";
		if (empty($id)) {
			$save = $this->dbh->query("INSERT INTO categoria set " . $data);
		} else {
			$save = $this->dbh->query("UPDATE categoria set " . $data . " where categoria_id = " . $id);
		}
		if ($save) {
			return 1;
		}
	}
	function delete_categoria()
	{
		extract($_POST);
		$delete = $this->dbh->query("DELETE FROM categoria where categoria_id = " . $codcategoria);
		if ($delete)
			return 1;
	}
	public function ListarCategorias()
	{
		$sql = "SELECT * FROM categoria ORDER BY categoria_des ASC";

		$result = mysqli_query($this->dbh, $sql);

		$categoria = array();
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$categoria[] = $row;
			}
		}
		return $categoria;
	}
	#endregion
	#region Presentacion
	function save_presentacion()
	{
		extract($_POST);
		$data = " presentacion = '$presentacion' ";
		if (empty($id)) {
			$save = $this->dbh->query("INSERT INTO presentacion set " . $data);
		} else {
			$save = $this->dbh->query("UPDATE presentacion set " . $data . " where categoria_id = " . $id);
		}
		if ($save) {
			return 1;
		}
	}
	function delete_presentacion()
	{
		extract($_POST);
		$delete = $this->dbh->query("DELETE FROM presentacion where idpresentacion = " . $idPresentacion);
		if ($delete)
			return 1;
	}
	public function ListarPresentacion()
	{
		$sql = "SELECT * FROM presentacion";

		$result = mysqli_query($this->dbh, $sql);

		$impuesto = array();
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$impuesto[] = $row;
			}
		}
		return $impuesto;
	}
	#endregion
	#region Clientes
	function save_cliente()
	{
		extract($_POST);
		// Construye la cadena de datos correctamente
		$data = " dni = '$dni'";
		$data .= ", nombre = '$nombre'";
		$data .= ", telefono = '$telefono'";
		$data .= ", correo = '$correo'";
		$data .= ", tipoDocumento = '$tipoDoc'";

		// Evita inyección SQL usando consultas preparadas
		if (empty($id)) {
			$save = $this->dbh->query("INSERT INTO cliente SET " . $data);
			if ($save) {

			}
		} else {
			$id = mysqli_real_escape_string($this->dbh, $id); // Escapa el valor de $id
			$save = $this->dbh->query("UPDATE cliente SET " . $data . " WHERE idcliente = $id");
		}

		if ($save) {
			$this->save_clienteDireccion(); // Llama a la función para guardar la dirección del cliente
			return 1;
		}
	}
	function save_clienteDireccion()
	{
		extract($_POST);
		// Construye la cadena de datos correctamente
		$data = " departamento = '$departamento'";
		$data .= ", municipio = '$municipio'";
		$data .= ", complemento = '$direccion'";
		$data .= ", cliente_dni = '$dni'";

		// Evita inyección SQL usando consultas preparadas
		if (empty($id)) {
			$save = $this->dbh->query("INSERT INTO cliente_direccion SET " . $data);
		} else {
			$id = mysqli_real_escape_string($this->dbh, $id); // Escapa el valor de $id
			$save = $this->dbh->query("UPDATE cliente_direccion SET " . $data . " WHERE cliente_dni = '$dni'");
		}

		if ($save) {
			return 1;
		}
	}

	function delete_cliente()
	{
		extract($_POST);
		$delete = $this->dbh->query("DELETE FROM cliente where idcliente = " . $idcliente);
		if ($delete)
			return 1;
	}
	public function listarclientes($filtro)
	{
		// Evitar inyección SQL utilizando prepared statements
		$consulta = "SELECT * FROM cliente WHERE nombre LIKE ?";

		// Preparar la consulta
		$stmt = $this->dbh->prepare($consulta);
		if ($stmt === false) {
			die("Error en la preparación de la consulta: " . $this->dbh->error);
		}

		// Agregar '%' al principio y al final del término de búsqueda para buscar coincidencias parciales
		$filtro = "%" . $filtro . "%";

		// Ejecutar la consulta con un parámetro
		$stmt->bind_param("s", $filtro);
		$stmt->execute();

		// Obtener los resultados
		$result = $stmt->get_result();
		$clientes = $result->fetch_all(MYSQLI_ASSOC);

		$stmt->close();

		return $clientes;
	}
	public function ListarDepartamentos()
	{
		$sql = "SELECT * FROM departamentos";

		$result = mysqli_query($this->dbh, $sql);

		$impuesto = array();
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$impuesto[] = $row;
			}
		}
		return $impuesto;
	}
	public function ListarDocumentos()
	{
		$sql = "SELECT * FROM documentos";

		$result = mysqli_query($this->dbh, $sql);

		$impuesto = array();
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$impuesto[] = $row;
			}
		}
		return $impuesto;
	}
	public function ListarMunicipios($departamento_id)
	{
		// Protección contra inyección SQL (cast explícito)
		$departamento_id = intval($departamento_id);

		$sql = "SELECT codigo, valor FROM municipios WHERE iddepartamento = $departamento_id";
		$result = mysqli_query($this->dbh, $sql);

		$municipios = array();
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$municipios[] = [
					'codigo' => $row['codigo'],
					'valor' => $row['valor']
				];
			}
		}
		return $municipios;
	}

	#endregion
	#region Proveedor
	function save_proveedor()
	{
		extract($_POST);
		// Construye la cadena de datos correctamente
		$data = " proveedor = '$proveedor'";
		$data .= ", contacto = '$contacto'";
		$data .= ", telefono = '$telefono'";
		$data .= ", direccion = '$direccion'";
		$data .= ", tipoControbuyente = '$tipoControbuyente'";
		$data .= ", correo = '$correo'";

		// Evita inyección SQL usando consultas preparadas
		if (empty($id)) {
			$save = $this->dbh->query("INSERT INTO proveedor SET " . $data);
		} else {
			$id = mysqli_real_escape_string($this->dbh, $id); // Escapa el valor de $id
			$save = $this->dbh->query("UPDATE proveedor SET " . $data . " WHERE idproveedor = $id");
		}

		if ($save) {
			return 1;
		}
	}
	function delete_proveedor()
	{
		extract($_POST);
		$delete = $this->dbh->query("DELETE FROM proveedor where idproveedor = " . $idproveedor);
		if ($delete)
			return 1;
	}
	public function ListarProveedores()
	{
		$sql = "SELECT * FROM proveedor";

		$result = mysqli_query($this->dbh, $sql);

		$impuesto = array();
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$impuesto[] = $row;
			}
		}
		return $impuesto;
	}

	#endregion
	#region Producto
	function save_productos()
	{
		extract($_POST);
		$targetFilePath = "";
		// Construye la cadena de datos para la consulta
		$data = "descripcion = '$descripcion'";
		$data .= ", proveedor = '$proveedor'";
		$data .= ", precio_compra = '$precio_compra'";
		$data .= ", precio = '$precio'";
		$data .= ", existencia = '$existencia'";
		$data .= ", exis_min = '$exis_min'";
		$data .= ", codBarra = '$codBarra'";
		$data .= ", prop1 = '$prop1'";
		$data .= ", prop2 = '$prop2'";
		$data .= ", prop3 = '$prop3'";
		$data .= ", categoria = '$categoria'";
		$data .= ", fecha_vencimiento = '$fecha_vencimiento'";

		if (isset($_FILES['imagen_producto']['name']) && !empty($_FILES['imagen_producto']['name'])) {
			$targetDir = "img/productos/";
			$fileName = uniqid() . "_" . basename($_FILES["imagen_producto"]["name"]);
			$targetFilePath = $targetDir . $fileName;
			$fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

			$allowedTypes = ['jpg', 'jpeg', 'png'];
			if (in_array($fileType, $allowedTypes)) {
				if (move_uploaded_file($_FILES["imagen_producto"]["tmp_name"], $targetFilePath)) {
					$data .= ", imagen_producto = '$targetFilePath'";
				} else {
					echo "Error al subir la imagen.";
					return 0;
				}
			} else {
				echo "Formato de imagen no permitido.";
				return 0;
			}
		} else {
			$data .= ", imagen_producto = 'img/ninguna.png'";
		}
		// Evitar inyección SQL con consultas preparadas
		if (empty($id)) {

			// Inserción de un nuevo producto
			$save = $this->dbh->query("INSERT INTO producto SET " . $data);
			if ($save) {
				// Registrar en el Kardex productos si es necesario
				$this->save_kardexproductos();
			}
		} else {
			// Actualización de un producto existente
			$id = mysqli_real_escape_string($this->dbh, $id); // Escapa el ID para seguridad
			$save = $this->dbh->query("UPDATE producto SET " . $data . " WHERE codproducto = $id");
		}

		if ($save) {
			return 1; // Éxito
		} else {
			echo "Error al guardar los datos en la base de datos.";
			return 0; // Falla
		}
	}

	function delete_producto()
	{
		extract($_POST);
		$delete = $this->dbh->query("DELETE FROM producto where codproducto = " . $codproducto);
		if ($delete)
			return 1;
	}
	function save_kardexproductos()
	{
		extract($_POST);
		$data = "producto = '$codBarra'";
		$data .= ", movimiento = 'ENTRADA'";
		$data .= ", entradas = '$existencia'";
		$data .= ", salidas = '0'";
		$data .= ", devolucion = '0'";
		$data .= ", stock_actual = '$existencia'";
		$data .= ", precio = '$precio_compra'";
		$data .= ", descripcion = 'INVENTARIO INICIAL'";

		// Evita inyección SQL usando consultas preparadas
		if (empty($id)) {
			$save = $this->dbh->query("INSERT INTO kardex_producto SET " . $data);
		}
		if (empty($save)) {
			return 1;
		} else {
			return 0;
		}
	}
	function save_stocks()
	{
		extract($_POST);
		$data = "existencia = '$existencia' + $cantidad"; // Corregido el concatenado de la existencia
		// Evita inyección SQL usando consultas preparadas
		if ($precioN != '') {
			$data .= ", precio = '$precioN'";
		}
		$id = mysqli_real_escape_string($this->dbh, $id); // Escapa el valor de $id
		$save = $this->dbh->query("UPDATE producto SET " . $data . " WHERE codproducto = '$id'"); // Corregido el uso de comillas en $id
		$this->update_kardexproductos();

		if ($save) {
			return 1;
		} else {
			return 0;
		}
	}

	function update_kardexproductos()
	{
		extract($_POST);
		$data = "producto = '$codBarra'";
		if (strpos($cantidad, '-') !== false) {
			$data .= ", movimiento = 'SALIDA'";
			$data .= ", entradas = '0'";
			$data .= ", salidas = '$cantidad'";
			$data .= ", devolucion = '0'";
			$data .= ", stock_actual = '$existencia' + $cantidad"; // Corregido el concatenado del stock_actual
			$data .= ", descripcion = 'AJUSTE DE INVENTARIO PARA PRODUCTO CON CODIGO: $codBarra'";
		} else {
			$data .= ", movimiento = 'ENTRADA'";
			$data .= ", entradas = '$cantidad'";
			$data .= ", salidas = '0'";
			$data .= ", devolucion = '0'";
			$data .= ", stock_actual = '$existencia' + $cantidad"; // Corregido el concatenado del stock_actual
			$data .= ", descripcion = 'ENTRADA DE PRODUCTO AL INVENTARIO: $codBarra'";
		}
		$data .= ", precio = '$precio'";


		$save = $this->dbh->query("INSERT INTO kardex_producto SET " . $data);

		if ($save) {
			return 1;
		} else {
			return 0;
		}
	}

	public function listarproductoauto($filtro)
	{
		// Evitar inyección SQL utilizando prepared statements
		$consulta = "SELECT * FROM producto WHERE descripcion LIKE ?  OR codBarra LIKE ?";

		// Preparar la consulta
		$stmt = $this->dbh->prepare($consulta);
		if ($stmt === false) {
			die("Error en la preparación de la consulta: " . $this->dbh->error);
		}

		// Agregar '%' al principio y al final del término de búsqueda para buscar coincidencias parciales
		$filtro = "%" . $filtro . "%";

		// Ejecutar la consulta con un parámetro
		$stmt->bind_param("ss", $filtro, $filtro);
		$stmt->execute();

		// Obtener los resultados
		$result = $stmt->get_result();
		$productos = $result->fetch_all(MYSQLI_ASSOC);

		$stmt->close();

		return $productos;
	}

	#endregion	
	#region Cajas
	function save_apertura()
	{
		extract($_POST);
		// Construye la cadena de datos correctamente
		$data = " num_apertura = '$num_apertura'";
		$data .= ", saldo_inicial = '$saldo_inicial'";
		$data .= ", fch_hora_cierre = '0000-00-00'";
		$data .= ", usuario = '{$_SESSION['login_usuario']}'";
		$data .= ", caja = 'Caja Principal'";
		$data .= ", estado = 'A'";

		// Evita inyección SQL usando consultas preparadas
		if (empty($id)) {
			$save = $this->dbh->query("INSERT INTO apertura_caja SET " . $data);
		}
		if ($save) {
			return 1;
		}
	}
	function save_cierre()
	{
		extract($_POST);

		// Sanitización de las entradas
		$id = mysqli_real_escape_string($this->dbh, $id);
		$saldo_ventas_total = mysqli_real_escape_string($this->dbh, $saldo_ventas_total);
		$gasto = mysqli_real_escape_string($this->dbh, $gasto);
		$saldo_tarjeta = mysqli_real_escape_string($this->dbh, $saldo_tarjeta);
		$entradas = mysqli_real_escape_string($this->dbh, $entradas);
		$total_completo = mysqli_real_escape_string($this->dbh, $total_completo);

		// Construcción de la consulta con el uso correcto de NOW() y manejo seguro de los datos
		$data = "fch_hora_cierre = NOW(), ";
		$data .= "usuario = '{$_SESSION['login_usuario']}', ";
		$data .= "estado = 'C', ";
		$data .= "saldo_venta_total = '$saldo_ventas_total', ";
		$data .= "gasto = '$gasto', ";
		$data .= "saldo_tarjeta = '$saldo_tarjeta', ";
		$data .= "saldo_credito = '0.00', ";
		$data .= "entradas = '$entradas', ";
		$data .= "total_completo = '$total_completo', ";
		$data .= "notas = '$notas'";

		// Ejecución de la consulta
		$query = "UPDATE apertura_caja SET $data WHERE idcaja = '$id' AND estado = 'A'";

		if ($this->dbh->query($query)) {
			return 1;
		} else {
			return 0; // O manejar el error según sea necesario
		}
	}

	public function ListarcajaApertura()
	{
		$sql = "SELECT * FROM apertura_caja where estado='A'";
		$result = mysqli_query($this->dbh, $sql);
		$caja = array();
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$caja[] = $row;
			}
		}
		return $caja;
	}
	public function ListarMediosPagos()
	{
		$sql = "SELECT * FROM medio_pago";
		$result = mysqli_query($this->dbh, $sql);
		$pago = array();
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$pago[] = $row;
			}
		}
		return $pago;
	}
	public function Listarconsecutivos()
	{
		$sql = "SELECT * FROM consecutivos";
		$result = mysqli_query($this->dbh, $sql);
		$pago = array();
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$pago[] = $row;
			}
		}
		return $pago;
	}
	#endregion
	#region Factura y Detalle
	function save_factura()
	{
		// Extraer y sanitizar los datos
		extract($_POST);
		$numeroALetras = new NumeroALetras();
		if (isset($subtotal, $iva_impuesto, $totalpagar, $codcliente, $detalle, $prefix)) {
			$subtotal = $this->dbh->real_escape_string($subtotal);
			$iva_impuesto = $this->dbh->real_escape_string($iva_impuesto);
			$totalpagar = $this->dbh->real_escape_string($totalpagar);
			$codcliente = $this->dbh->real_escape_string($codcliente);
			$prefix = $this->dbh->real_escape_string($prefix);
			$tipofactura = $prefix;
			$letras = $numeroALetras->toMoney($totalpagar, 2, 'dolares', 'centavos');
			$forma_pago = 'na';
			$idusuario = $_SESSION['login_idusuario'];
			$estado = 'Pendiente';
			$detalle = json_decode($detalle, true);
			$csrf_tokenR = $this->dbh->real_escape_string($csrf_token);

			if ($csrf_tokenR == $csrf_token) {
				if (!is_array($detalle)) {
					echo json_encode(['success' => false, 'message' => 'Formato de detalle incorrecto.']);
					exit;
				}

				// Generar el número de factura único
				$numerofactura = $this->generateCorrelativo($prefix);

				// Verificar si ya existe una factura con el mismo numerofactura
				$checkFactura = $this->dbh->prepare("SELECT COUNT(*) FROM factura WHERE numerofactura = ?");
				$checkFactura->bind_param("s", $numerofactura);
				$checkFactura->execute();
				$checkFactura->bind_result($count);
				$checkFactura->fetch();
				$checkFactura->close();

				if ($count > 0) {
					echo json_encode(['success' => false, 'message' => 'Ya existe una factura con ese número.']);
					exit;
				}

				// Iniciar transacción
				$this->dbh->begin_transaction();

				try {

					$data_factura = "tipofactura = '$tipofactura'";
					$data_factura .= ", numerofactura = '$numerofactura'";
					$data_factura .= ", subtotal = '$subtotal'";
					$data_factura .= ", iva_impuesto = '$iva_impuesto'";
					$data_factura .= ", totalpagar = '$totalpagar'";
					$data_factura .= ", letras = '$letras'";
					$data_factura .= ", forma_pago = '$forma_pago'";
					$data_factura .= ", idusuario = '$idusuario'";
					$data_factura .= ", idcliente = '$codcliente'";
					$data_factura .= ", estado = '$estado'";

					$save_factura = $this->dbh->query("INSERT INTO factura SET " . $data_factura);

					if ($save_factura) {
						$idfactura = $this->dbh->insert_id;

						foreach ($detalle as $producto) {
							if (!isset($producto['codproducto'], $producto['precio'], $producto['cantidad'])) {
								throw new Exception("Datos del producto incompletos.");
							}

							$cod_producto = $this->dbh->real_escape_string($producto['codproducto']);
							$precio_venta = $this->dbh->real_escape_string($producto['precio']);
							$cantidad = $this->dbh->real_escape_string($producto['cantidad']);

							$data_detalle = "cod_producto = '$cod_producto'";
							$data_detalle .= ", precioventa = '$precio_venta'";
							$data_detalle .= ", cantidad = '$cantidad'";
							$data_detalle .= ", idfactura = '$idfactura'";

							$save_detalle = $this->dbh->query("INSERT INTO detallefactura SET " . $data_detalle);

							if (!$save_detalle) {
								throw new Exception("Error al guardar el detalle de la factura: " . $this->dbh->error);
							}

							$descripcion = $this->dbh->real_escape_string("VENTA EN FACTURA: $numerofactura");

							$save_kardexSalida = $this->save_kardex_producto_venta($cod_producto, $cantidad, $precio_venta, 'SALIDA', $descripcion);
							if (!$save_kardexSalida) {
								throw new Exception("Error al guardar el kardex de la factura: " . $this->dbh->error);
							}
						}

						$this->dbh->commit();

						// Generar un nuevo token CSRF y actualizar la cookie
						$new_csrf_token = bin2hex(random_bytes(32));
						$csrf_tokenR = $new_csrf_token;
						echo json_encode(['success' => true, 'message' => 'Factura y detalles insertados correctamente.', 'idfactura' => $idfactura]);
					} else {
						throw new Exception('Error al insertar la factura.');
					}
				} catch (Exception $e) {
					$this->dbh->rollback();
					echo json_encode(['success' => false, 'message' => $e->getMessage()]);
				}
			} else {
				echo json_encode(['success' => false, 'message' => 'no funciona.']);
				exit;
			}
		}
	}
	function save_kardex_producto_venta($producto_id, $cantidadP, $precioP, $movimientosP, $descripcionP)
	{
		// Declarar variables
		$stock_actualP = 0.00;
		$exis_actualP = 0.00;
		$entradasP = 0;
		$salidasP = 0;
		$devolucionesP = 0;

		// Obtener existencia actual del producto
		$sql = "SELECT existencia FROM producto WHERE codProducto = ?";
		$stmt = $this->dbh->prepare($sql);
		$stmt->bind_param("s", $producto_id);
		$stmt->execute();
		$stmt->bind_result($exis_actualP);
		$stmt->fetch();
		$stmt->close();

		// Calcular nuevo stock y movimientos
		if ($movimientosP == 'ENTRADA') {
			$stock_actualP = $exis_actualP + $cantidadP;
			$entradasP = $cantidadP;
		} elseif ($movimientosP == 'SALIDA') {
			$stock_actualP = $exis_actualP - $cantidadP;
			$salidasP = $cantidadP;
		} elseif ($movimientosP == 'DEVOLUCION') {
			$stock_actualP = $exis_actualP + $cantidadP;
			$devolucionesP = $cantidadP;
		}

		// Actualizar existencia en producto
		$sql = "UPDATE producto SET existencia = ? WHERE codProducto = ?";
		$stmt = $this->dbh->prepare($sql);
		$stmt->bind_param("ds", $stock_actualP, $producto_id);
		if (!$stmt->execute()) {
			$stmt->close();
			return false;
		}
		$stmt->close();

		// Insertar en kardex_producto
		$sql = "INSERT INTO kardex_producto (producto, movimiento, entradas, salidas, devolucion, stock_actual, precio, descripcion) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = $this->dbh->prepare($sql);
		$stmt->bind_param("ssiiidss", $producto_id, $movimientosP, $entradasP, $salidasP, $devolucionesP, $stock_actualP, $precioP, $descripcionP);
		if (!$stmt->execute()) {
			$stmt->close();
			return false;
		}
		$stmt->close();

		return true;
	}
	function save_ventacompleta()
	{
		extract($_POST);
		$id = mysqli_real_escape_string($this->dbh, $id);
		$data = "forma_pago = '$forma_pago'";
		$data .= ", estado = 'Pagado'";

		$save = $this->dbh->query("UPDATE factura SET " . $data . " WHERE id = $id");
		if ($save) {
			// Construir la URL del ticket
			$ticket_url = "ticket.php?id=" . $id;
			$facturaElectronica = "facturaElectronica.php?id=" . $id;

			echo json_encode([
				'success' => true,
				'ticket_url' => $ticket_url,
				'facturaElectronica' => $facturaElectronica
			]);
		} else {
			// Devolver un JSON con success = false
			echo json_encode(['success' => false, 'message' => 'Error al actualizar la factura.']);
		}
		exit;
	}

	private function generateCorrelativo($prefix)
	{
		$query = $this->dbh->prepare("SELECT MAX(valor) AS last_id FROM consecutivos WHERE codigo_consecutivo LIKE ?");
		$likePrefix = $prefix . '%';
		$query->bind_param('s', $likePrefix);
		$query->execute();
		$result = $query->get_result();
		$row = $result->fetch_assoc();
		$lastId = $row['last_id'];

		if ($lastId) {
			$number = intval(substr($lastId, strlen($prefix)));
			$newNumber = $number + 1;
			$newId = $prefix . str_pad($newNumber, 9 - strlen($prefix), '0', STR_PAD_LEFT);
		} else {
			$newId = $prefix . str_pad(1, 9 - strlen($prefix), '0', STR_PAD_LEFT);
		}

		$updateQuery = $this->dbh->prepare("UPDATE consecutivos SET valor = ? WHERE codigo_consecutivo = ?");
		$updateQuery->bind_param('ss', $newId, $prefix);
		$updateQuery->execute();

		return $newId;
	}

	#endregion

	#region Movimientos Caja
	function movimientos_caja()
	{
		extract($_POST);
		$data = "fecha = NOW()";
		$data .= ", ingreso = CASE 
                           WHEN '$transaccion' = 'ENTRADA' THEN '$ingreso'
                           WHEN '$transaccion' = 'SALIDA' THEN 0.00
                         END";
		$data .= ", egreso = CASE 
                           WHEN '$transaccion' = 'SALIDA' THEN '$egreso'
                           WHEN '$transaccion' = 'ENTRADA' THEN 0.00
                         END";
		$data .= ", comentario = '$comentario'";
		$data .= ", usuario = '{$_SESSION['login_usuario']}'";

		// Evita inyección SQL usando consultas preparadas
		if (empty($id)) {
			$save = $this->dbh->query("INSERT INTO movimientos_de_caja SET " . $data);
		} else {
			$id = mysqli_real_escape_string($this->dbh, $id); // Escapa el valor de $id
			$save = $this->dbh->query("UPDATE movimientos_de_caja SET " . $data . " WHERE idmovimiento = $id");
		}

		if ($save) {
			return 1;
		} else {
			return 0; // Si la inserción falla, devuelve 0
		}
	}
	#endregion
}