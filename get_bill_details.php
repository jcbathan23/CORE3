<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized');
}

include 'config.php';

if (!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Missing bill ID');
}

$bill_id = (int)$_GET['id'];

// Fetch bill details with patient information and latest payment
$sql = "SELECT b.*, 
               CONCAT(p.first_name, ' ', p.last_name) as patient_name,
               (SELECT payment_date FROM homis_payments WHERE bill_id = b.bill_id ORDER BY payment_date DESC LIMIT 1) as payment_date
        FROM homis_bills b 
        JOIN patients p ON b.patient_id = p.patient_id 
        WHERE b.bill_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('HTTP/1.1 404 Not Found');
    exit('Bill not found');
}

$bill = $result->fetch_assoc();

// Format dates
$bill['due_date'] = date('Y-m-d', strtotime($bill['due_date']));
if ($bill['payment_date']) {
    $bill['payment_date'] = date('Y-m-d', strtotime($bill['payment_date']));
}

header('Content-Type: application/json');
echo json_encode($bill); 