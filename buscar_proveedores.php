<?php
include 'conexionfin.php'; // conexión a tu base de datos

$term = isset($_GET['q']) ? $_GET['q'] : '';

$sql = "SELECT idproveedor, proveedor FROM proveedor WHERE proveedor LIKE ? LIMIT 20";
$stmt = $conexion->prepare($sql);
$searchTerm = "%{$term}%";
$stmt->bind_param("s", $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'id' => $row['idproveedor'],
        'text' => $row['proveedor']
    ];
}

echo json_encode(['results' => $items]);