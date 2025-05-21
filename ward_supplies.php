<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: loginDefault.php");
    exit();
}

// Include database connection
include 'config.php';

// Check if ward_supplies table exists, if not create it
$tableCheck = $conn->query("SHOW TABLES LIKE 'ward_supplies'");
if ($tableCheck->num_rows == 0) {
    // Create the table with the updated structure
    $createTable = "CREATE TABLE `ward_supplies` (
      `supply_id` int(11) NOT NULL AUTO_INCREMENT,
      `item_type` enum('linen','medical','equipment') NOT NULL,
      `item_name` varchar(100) NOT NULL,
      `quantity` int(11) NOT NULL DEFAULT 0,
      `unit` enum('pieces','boxes','sets') NOT NULL,
      `status` enum('available','low','out') NOT NULL DEFAULT 'available',
      `notes` text DEFAULT NULL,
      `created_by` int(11) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`supply_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if (!$conn->query($createTable)) {
        echo "Error creating table: " . $conn->error;
    }
    
    // Add sample supplies only if the table was just created
    $supplies = [
        ['linen', 'Bed Sheets', 50, 'pieces', 'available'],
        ['linen', 'Pillow Cases', 40, 'pieces', 'available'],
        ['linen', 'Blankets', 30, 'pieces', 'available'],
        ['medical', 'Disposable Gloves', 10, 'boxes', 'available'],
        ['medical', 'Face Masks', 15, 'boxes', 'available'],
        ['medical', 'Disposable Syringes', 8, 'boxes', 'low'],
        ['equipment', 'Blood Pressure Monitors', 5, 'pieces', 'available'],
        ['equipment', 'Digital Thermometers', 8, 'pieces', 'available']
    ];
    
    $stmt = $conn->prepare("INSERT INTO ward_supplies (item_type, item_name, quantity, unit, status, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($supplies as $supply) {
        $created_by = $_SESSION['user_id'];
        $stmt->bind_param("ssissi", $supply[0], $supply[1], $supply[2], $supply[3], $supply[4], $created_by);
        $stmt->execute();
    }
    $stmt->close();
}

// Process form submission for adding supply
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['update_supply'])) {
    $item_type = $_POST['item_type'];
    $item_name = $_POST['item_name'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $status = $_POST['status'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $created_by = $_SESSION['user_id'];
    
    // Prepare and execute SQL statement
    $stmt = $conn->prepare("INSERT INTO ward_supplies (item_type, item_name, quantity, unit, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisssi", $item_type, $item_name, $quantity, $unit, $status, $notes, $created_by);
    
    if ($stmt->execute()) {
        $success_message = "Supply added successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Process form submission for updating supply
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_supply'])) {
    $supply_id = $_POST['supply_id'];
    $item_type = $_POST['item_type'];
    $item_name = $_POST['item_name'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $status = $_POST['status'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Prepare and execute SQL statement
    $stmt = $conn->prepare("UPDATE ward_supplies SET item_type = ?, item_name = ?, quantity = ?, unit = ?, status = ?, notes = ? WHERE supply_id = ?");
    $stmt->bind_param("ssisssi", $item_type, $item_name, $quantity, $unit, $status, $notes, $supply_id);
    
    if ($stmt->execute()) {
        $success_message = "Supply updated successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Process supply deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $supply_id = $_GET['delete'];
    
    // Prepare and execute delete statement
    $stmt = $conn->prepare("DELETE FROM ward_supplies WHERE supply_id = ?");
    $stmt->bind_param("i", $supply_id);
    
    if ($stmt->execute()) {
        $success_message = "Supply deleted successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Fetch all supplies
$supplies = [];
$query = "SELECT * FROM ward_supplies ORDER BY item_type, item_name";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $supplies[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ward Supplies</title>
    <link rel="icon" href="logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../components/tm.css">
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
                            <h5 class="mb-0">Ward Supplies Management</h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($success_message)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo $success_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($error_message)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $error_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Supply Form -->
                            <form method="POST" class="mb-4">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Item Type</label>
                                        <select name="item_type" class="form-select" required>
                                            <option value="">Select Type</option>
                                            <option value="linen">Linen</option>
                                            <option value="medical">Medical Supplies</option>
                                            <option value="equipment">Equipment</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Item Name</label>
                                        <input type="text" name="item_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="quantity" class="form-control" min="0" required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">Unit</label>
                                        <select name="unit" class="form-select" required>
                                            <option value="pieces">Pieces</option>
                                            <option value="boxes">Boxes</option>
                                            <option value="sets">Sets</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select" required>
                                            <option value="available">Available</option>
                                            <option value="low">Low Stock</option>
                                            <option value="out">Out of Stock</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Supply</button>
                            </form>

                            <!-- Supplies Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Item Type</th>
                                            <th>Item Name</th>
                                            <th>Quantity</th>
                                            <th>Unit</th>
                                            <th>Last Updated</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($supplies) > 0): ?>
                                            <?php foreach ($supplies as $supply): ?>
                                                <tr>
                                                    <td><?php echo ucfirst($supply['item_type']); ?></td>
                                                    <td><?php echo htmlspecialchars($supply['item_name']); ?></td>
                                                    <td><?php echo $supply['quantity']; ?></td>
                                                    <td><?php echo ucfirst($supply['unit']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($supply['updated_at'])); ?></td>
                                                    <td>
                                                        <?php if ($supply['status'] === 'available'): ?>
                                                            <span class="badge bg-success">Available</span>
                                                        <?php elseif ($supply['status'] === 'low'): ?>
                                                            <span class="badge bg-warning">Low Stock</span>
                                                        <?php elseif ($supply['status'] === 'out'): ?>
                                                            <span class="badge bg-danger">Out of Stock</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" 
                                                                onclick="editSupply(<?php echo $supply['supply_id']; ?>, 
                                                                '<?php echo $supply['item_type']; ?>', 
                                                                '<?php echo addslashes($supply['item_name']); ?>', 
                                                                <?php echo $supply['quantity']; ?>, 
                                                                '<?php echo $supply['unit']; ?>', 
                                                                '<?php echo $supply['status']; ?>', 
                                                                '<?php echo addslashes($supply['notes'] ?? ''); ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="?delete=<?php echo $supply['supply_id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Are you sure you want to delete this supply item?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No supplies found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Supply Modal -->
    <div class="modal fade" id="editSupplyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Supply</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editSupplyForm">
                        <input type="hidden" name="supply_id" id="edit_supply_id">
                        <input type="hidden" name="update_supply" value="1">
                        <div class="mb-3">
                            <label class="form-label">Item Type</label>
                            <select name="item_type" id="edit_item_type" class="form-select" required>
                                <option value="linen">Linen</option>
                                <option value="medical">Medical Supplies</option>
                                <option value="equipment">Equipment</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Item Name</label>
                            <input type="text" name="item_name" id="edit_item_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="edit_quantity" class="form-control" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unit</label>
                            <select name="unit" id="edit_unit" class="form-select" required>
                                <option value="pieces">Pieces</option>
                                <option value="boxes">Boxes</option>
                                <option value="sets">Sets</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="available">Available</option>
                                <option value="low">Low Stock</option>
                                <option value="out">Out of Stock</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Supply</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editSupply(id, type, name, quantity, unit, status, notes) {
            document.getElementById('edit_supply_id').value = id;
            document.getElementById('edit_item_type').value = type;
            document.getElementById('edit_item_name').value = name;
            document.getElementById('edit_quantity').value = quantity;
            document.getElementById('edit_unit').value = unit;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_notes').value = notes;
            
            new bootstrap.Modal(document.getElementById('editSupplyModal')).show();
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