<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include 'config.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Item ID is required']);
    exit();
}

$stmt = $conn->prepare("SELECT item_id, quantity, price, status FROM homis_inventory WHERE item_id = ?");
$stmt->bind_param("i", $_GET['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Item not found']);
    exit();
}

$item = $result->fetch_assoc();
header('Content-Type: application/json');
echo json_encode($item); 