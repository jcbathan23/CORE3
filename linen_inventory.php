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

// Check if linen_inventory table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'linen_inventory'");
if ($tableCheck->num_rows == 0) {
    // Use JavaScript for redirection
    echo "<script>window.location.href = 'linen_laundry.php';</script>";
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $item_name = $_POST['item_name'];
                $quantity = $_POST['quantity'];
                $condition = $_POST['condition'];
                $status = $_POST['status'];

                $sql = "INSERT INTO linen_inventory (item_name, quantity, `condition`, status, created_at) 
                        VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siss", $item_name, $quantity, $condition, $status);
                $stmt->execute();
                break;

            case 'update':
                $linen_id = $_POST['linen_id'];
                $item_name = $_POST['item_name'];
                $quantity = $_POST['quantity'];
                $condition = $_POST['condition'];
                $status = $_POST['status'];

                $sql = "UPDATE linen_inventory 
                        SET item_name=?, quantity=?, `condition`=?, status=?, updated_at=NOW() 
                        WHERE linen_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sissi", $item_name, $quantity, $condition, $status, $linen_id);
                $stmt->execute();
                break;

            case 'delete':
                $linen_id = $_POST['linen_id'];
                $sql = "DELETE FROM linen_inventory WHERE linen_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $linen_id);
                $stmt->execute();
                break;

            case 'send_to_laundry':
                $linen_id = $_POST['linen_id'];
                $sent_date = $_POST['sent_date'];
                $notes = $_POST['notes'];

                // Start transaction
                $conn->begin_transaction();

                try {
                    // Insert laundry record
                    $sql = "INSERT INTO linen_laundry (linen_id, sent_date, status, notes, created_at) 
                            VALUES (?, ?, 'Sent', ?, NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iss", $linen_id, $sent_date, $notes);
                    $stmt->execute();

                    // Update linen status
                    $sql = "UPDATE linen_inventory SET status = 'In Laundry', updated_at=NOW() WHERE linen_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $linen_id);
                    $stmt->execute();

                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                    echo "Error: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch all linen items
$sql = "SELECT * FROM linen_inventory ORDER BY item_name";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linen Inventory</title>
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
                            <h5 class="mb-0">Linen Inventory Management</h5>
                        </div>
                        <div class="card-body">
                            <!-- Add Linen Form -->
                            <form method="POST" action="" class="mb-4">
                                <input type="hidden" name="action" value="add">
                                <div class="row">
                                <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="item_name" class="form-label">Item Name</label>
                                            <input type="text" class="form-control" id="item_name" name="item_name" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="quantity" class="form-label">Quantity</label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" required min="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="condition" class="form-label">Condition</label>
                                            <select class="form-select" id="condition" name="condition" required>
                                                <option value="New">New</option>
                                                <option value="Good">Good</option>
                                                <option value="Fair">Fair</option>
                                                <option value="Poor">Poor</option>
                                            </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="Available">Available</option>
                                                <option value="In Use">In Use</option>
                                                <option value="In Laundry">In Laundry</option>
                                                <option value="Discarded">Discarded</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Item</button>
                            </form>

                            <!-- Linen Inventory Table -->
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Item ID</th>
                                            <th>Item Name</th>
                                            <th>Quantity</th>
                                            <th>Condition</th>
                                            <th>Status</th>
                                            <th>Last Washed</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo isset($row['linen_id']) ? $row['linen_id'] : 'N/A'; ?></td>
                                            <td><?php echo isset($row['item_name']) ? htmlspecialchars($row['item_name']) : 'N/A'; ?></td>
                                            <td><?php echo isset($row['quantity']) ? $row['quantity'] : '0'; ?></td>
                                            <td><?php echo isset($row['condition']) ? htmlspecialchars($row['condition']) : 'N/A'; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo isset($row['status']) ? 
                                                        ($row['status'] === 'Available' ? 'success' : 
                                                        ($row['status'] === 'In Use' ? 'primary' : 
                                                        ($row['status'] === 'In Laundry' ? 'warning' : 'danger'))) : 'secondary'; 
                                                ?>">
                                                    <?php echo isset($row['status']) ? htmlspecialchars($row['status']) : 'Unknown'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo isset($row['last_washed_date']) && $row['last_washed_date'] ? date('Y-m-d', strtotime($row['last_washed_date'])) : 'N/A'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editLinen(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if(isset($row['status']) && $row['status'] === 'Available'): ?>
                                                <button class="btn btn-sm btn-warning" onclick="sendToLaundry(<?php echo $row['linen_id']; ?>)">
                                                    <i class="fas fa-tshirt"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-danger" onclick="deleteLinen(<?php echo $row['linen_id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

    <!-- Edit Linen Modal -->
    <div class="modal fade" id="editLinenModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Linen Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editLinenForm" method="POST" action="">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="linen_id" id="edit_linen_id">
                        <div class="mb-3">
                            <label for="edit_item_name" class="form-label">Item Name</label>
                            <input type="text" class="form-control" id="edit_item_name" name="item_name" required>
                        </div>
                    <div class="mb-3">
                            <label for="edit_quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="edit_quantity" name="quantity" required min="0">
                    </div>
                    <div class="mb-3">
                            <label for="edit_condition" class="form-label">Condition</label>
                            <select class="form-select" id="edit_condition" name="condition" required>
                                <option value="New">New</option>
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Poor">Poor</option>
                            </select>
                    </div>
                    <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="Available">Available</option>
                                <option value="In Use">In Use</option>
                                <option value="In Laundry">In Laundry</option>
                                <option value="Discarded">Discarded</option>
                            </select>
                    </div>
                        <button type="submit" class="btn btn-primary">Update Item</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Send to Laundry Modal -->
    <div class="modal fade" id="laundryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send to Laundry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="laundryForm" method="POST" action="">
                        <input type="hidden" name="action" value="send_to_laundry">
                        <input type="hidden" name="linen_id" id="laundry_linen_id">
                        <div class="mb-3">
                            <label for="sent_date" class="form-label">Sent Date</label>
                            <input type="datetime-local" class="form-control" id="sent_date" name="sent_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning">Send to Laundry</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteLinenModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this item?</p>
                    <form id="deleteLinenForm" method="POST" action="">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="linen_id" id="delete_linen_id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editLinen(linen) {
            document.getElementById('edit_linen_id').value = linen.linen_id;
            document.getElementById('edit_item_name').value = linen.item_name;
            document.getElementById('edit_quantity').value = linen.quantity;
            document.getElementById('edit_condition').value = linen.condition;
            document.getElementById('edit_status').value = linen.status;
            
            new bootstrap.Modal(document.getElementById('editLinenModal')).show();
        }

        function sendToLaundry(linenId) {
            document.getElementById('laundry_linen_id').value = linenId;
            document.getElementById('sent_date').value = new Date().toISOString().slice(0, 16);
            new bootstrap.Modal(document.getElementById('laundryModal')).show();
        }

        function deleteLinen(linenId) {
            document.getElementById('delete_linen_id').value = linenId;
            new bootstrap.Modal(document.getElementById('deleteLinenModal')).show();
        }

        document.addEventListener('DOMContentLoaded', function() {
            var sidebar = document.getElementById('sidebar');
            var sidebarToggle = document.getElementById('sidebarToggle');
            var backdrop = document.getElementById('sidebar-backdrop');
            function closeSidebar() {
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('show-backdrop');
                backdrop.style.display = 'none';
            }
            function openSidebar() {
                sidebar.classList.remove('collapsed');
                sidebar.classList.add('show-backdrop');
                backdrop.style.display = 'block';
            }
            sidebarToggle.addEventListener('click', function() {
                if (sidebar.classList.contains('collapsed')) {
                    openSidebar();
                } else {
                    closeSidebar();
                }
            });
            backdrop.addEventListener('click', function() {
                closeSidebar();
            });
            // Sidebar starts open on page load
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('show-backdrop');
            backdrop.style.display = 'none';
        });
    </script>
</body>
</html> 