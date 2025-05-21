<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: loginDefault.php");
    exit();
}
include_once('admin.php');
include 'config.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $laundry_id = $_POST['laundry_id'];
                $status = $_POST['status'];
                $return_date = $_POST['return_date'];
                $notes = $_POST['notes'];

                // Start transaction
                $conn->begin_transaction();

                try {
                    // Update laundry record
                    $sql = "UPDATE linen_laundry 
                            SET status=?, return_date=?, notes=?, updated_at=NOW() 
                            WHERE laundry_id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssi", $status, $return_date, $notes, $laundry_id);
                    $stmt->execute();

                    // If returned, update linen inventory status
                    if ($status === 'Returned') {
                        $sql = "UPDATE linen_inventory i 
                                JOIN linen_laundry l ON i.linen_id = l.linen_id 
                                SET i.status = 'Available', 
                                    i.last_washed_date = ?,
                                    i.updated_at = NOW()
                                WHERE l.laundry_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $return_date, $laundry_id);
                        $stmt->execute();
                    }

                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                    echo "Error: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch all laundry records with linen details
$sql = "SELECT l.*, i.item_name, i.quantity, i.condition 
        FROM linen_laundry l 
        JOIN linen_inventory i ON l.linen_id = i.linen_id 
        ORDER BY l.sent_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laundry Tracking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .main-content {
            margin-left: 300px;
            transition: margin-left 0.3s;
            padding: 20px;
            width: calc(100% - 300px);
        }
        
        #sidebar.collapsed ~ .main-content {
            margin-left: 0;
            width: 100%;
        }

        .container-fluid {
            padding: 0;
            margin: 0;
            width: 100%;
        }

        .card {
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include_once 'index.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Laundry Tracking Management</h5>
                        </div>
                        <div class="card-body">
                            <!-- Laundry Records Table -->
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Laundry ID</th>
                                            <th>Item Name</th>
                                            <th>Quantity</th>
                                            <th>Condition</th>
                                            <th>Sent Date</th>
                                            <th>Return Date</th>
                                            <th>Status</th>
                                            <th>Notes</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['laundry_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                            <td><?php echo $row['quantity']; ?></td>
                                            <td><?php echo htmlspecialchars($row['condition']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($row['sent_date'])); ?></td>
                                            <td><?php echo $row['return_date'] ? date('Y-m-d', strtotime($row['return_date'])) : 'Pending'; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $row['status'] === 'Sent' ? 'warning' : 
                                                        ($row['status'] === 'Processing' ? 'info' : 
                                                        ($row['status'] === 'Returned' ? 'success' : 'secondary')); 
                                                ?>">
                                                    <?php echo htmlspecialchars($row['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                                            <td>
                                                <?php if($row['status'] !== 'Returned'): ?>
                                                <button class="btn btn-sm btn-primary" onclick="updateStatus(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Laundry Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateStatusForm" method="POST" action="">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="laundry_id" id="update_laundry_id">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Sent">Sent</option>
                                <option value="Processing">Processing</option>
                                <option value="Returned">Returned</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="return_date" class="form-label">Return Date</label>
                            <input type="date" class="form-control" id="return_date" name="return_date">
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(laundry) {
            document.getElementById('update_laundry_id').value = laundry.laundry_id;
            document.getElementById('status').value = laundry.status;
            document.getElementById('return_date').value = laundry.return_date || '';
            document.getElementById('notes').value = laundry.notes || '';
            
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }
    </script>
</body>
</html> 