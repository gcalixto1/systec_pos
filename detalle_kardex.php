<?php
// Incluir el archivo de conexión a la base de datos
include('conexionfin.php');

// Verificar si se ha proporcionado el código del producto
if (isset($_GET['codBarra'])) {
    $codproducto = $_GET['codBarra'];

    // Realizar la consulta SQL
    $qry = $conexion->query("SELECT *,producto.descripcion as producto_nombre FROM (producto LEFT JOIN kardex_producto ON producto.codBarra=kardex_producto.producto) 
    LEFT JOIN categoria ON producto.categoria=categoria.categoria_id 
    LEFT JOIN proveedor ON producto.proveedor=proveedor.idproveedor 
    WHERE producto.codBarra = '$codproducto' LIMIT 10");
    echo "<style>
    .bg-entrada {
        background-color: #065000; /* Color mostaza */
        color:white;
        font-weight: bold;
    }
    
    .bg-salida {
        background-color: #D5A604; /* Color vino */
        color:black;
        font-weight: bold;
    }
    
    .bg-devolucion {
        background-color: #7C51B4; /* Color morado */
        color:white;
        font-weight: bold;
    }
    .legend {
        display: flex;
        justify-content: center;
        margin-bottom: 10px;
    }
    
    .legend-item {
        display: inline-block;
        margin-right: 20px;
        padding: 5px 10px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
    }
          </style>";
    // Verificar si se encontraron resultados
    if ($qry->num_rows > 0) {
        echo "<div class='legend'>
        <span class='legend-item bg-entrada'><i class='fa fa-plus-square'></i> Entrada</span>
        <span class='legend-item bg-salida'><i class='fa fa-minus-square'></i> Salida</span>
        <span class='legend-item bg-devolucion'>(<i class='fa fa-retweet'></i>) Devolución</span>
    </div>";
        echo "<table class='table table-responsive' id='borrower-list'>";
        echo "<colgroup>
        <col width='5%'>
        <col width='20%'>
        <col width='35%'>
        <col width='20%'>
        <col width='10%'>
    </colgroup>";
        echo "<thead>";
        echo "<tr>";
        echo "<th class='text-center'>#</th>";
        echo "<th class='text-center'>fecha del Movimiento</th>";
        echo "<th class='text-center'>Descripcion del Movimiento</th>";
        echo "<th class='text-center'>Movimiento</th>";
        echo "<th class='text-center'>Stock Actual</th>";
        // Agrega aquí más columnas si es necesario
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        // Iterar sobre los resultados y mostrar cada fila en la tabla
        $contador = 1;
        while ($row = $qry->fetch_assoc()) {

            $entrada_class = ($row['entradas'] != 0) ? 'bg-entrada' : '';
            $salida_class = ($row['salidas'] != 0) ? 'bg-salida' : '';
            $devolucion_class = ($row['devolucion'] != 0) ? 'bg-devolucion' : '';

            // Aplicar las clases a la fila
            echo "<tr class='$entrada_class $salida_class $devolucion_class'>";
            echo "<td>" . $contador++ . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($row['fecha_trans'])) . "</td>";
            echo "<td>" . $row['descripcion'] . "</td>";
            echo "<td>";
            echo ($row['entradas'] != 0 ? 'Entrada : ' . $row['entradas'] . ' <i class="fa fa-plus-square"></i>' : '');
            echo ($row['salidas'] != 0 ? 'Salida : ' . $row['salidas'] . ' <i class="fa fa-minus-square"></i>' : '');
            echo ($row['devolucion'] != 0 ? 'Devolucion : ' . $row['devolucion'] . ' (<i class="fa fa-retweet"></i>)' : '');
            echo "</td>";
            echo "<td>" . $row['stock_actual'] . "</td>";
            // Agrega aquí más columnas si es necesario
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";
    } else {
        // Mostrar un mensaje si no se encontraron resultados
        echo "<p>No se encontraron resultados para el producto con código: $codproducto</p>";
    }
}
