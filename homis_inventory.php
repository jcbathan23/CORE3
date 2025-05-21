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
            case 'add_item':
                $stmt = $conn->prepare("INSERT INTO homis_inventory (item_name, category, quantity, unit, price, supplier, expiry_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisdsss", 
                    $_POST['item_name'],
                    $_POST['category'],
                    $_POST['quantity'],
                    $_POST['unit'],
                    $_POST['price'],
                    $_POST['supplier'],
                    $_POST['expiry_date'],
                    $_POST['status']
                );
                
                // Begin transaction
                $conn->begin_transaction();
                try {
                    $stmt->execute();
                    $item_id = $conn->insert_id;
                    
                    // Record initial stock transaction
                    $stmt = $conn->prepare("INSERT INTO homis_inventory_transactions (item_id, transaction_type, quantity, notes) VALUES (?, 'In', ?, 'Initial stock')");
                    $stmt->bind_param("ii", $item_id, $_POST['quantity']);
                    $stmt->execute();
                    
                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                }
                break;

            case 'update_item':
                $stmt = $conn->prepare("UPDATE homis_inventory SET quantity = ?, price = ?, status = ? WHERE item_id = ?");
                $stmt->bind_param("idsi", 
                    $_POST['quantity'],
                    $_POST['price'],
                    $_POST['status'],
                    $_POST['item_id']
                );
                $stmt->execute();
                break;

            case 'delete_item':
                $stmt = $conn->prepare("DELETE FROM homis_inventory WHERE item_id = ?");
                $stmt->bind_param("i", $_POST['item_id']);
                $stmt->execute();
                break;
        }
    }
}

// Fetch inventory statistics
$sql = "SELECT 
            COUNT(*) as total_items,
            SUM(CASE WHEN status = 'In Stock' THEN 1 ELSE 0 END) as in_stock_items,
            SUM(CASE WHEN status = 'Low Stock' THEN 1 ELSE 0 END) as low_stock_items,
            SUM(CASE WHEN status = 'Out of Stock' THEN 1 ELSE 0 END) as out_of_stock_items
        FROM homis_inventory";
$stats = $conn->query($sql)->fetch_assoc();

// Fetch all inventory items
$sql = "SELECT * FROM homis_inventory ORDER BY created_at DESC";
$items = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOMIS Inventory</title>
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
            border: none;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .card-header {
            background-color: #4A628A;
            color: white;
            padding: 15px 20px;
        }

        .stats-card {
            border-radius: 10px;
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card .card-body {
            padding: 1.5rem;
        }

        .stats-card .card-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stats-card h3 {
            font-size: 2rem;
            font-weight: 600;
            margin: 0.5rem 0 0;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
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
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>HOMIS Inventory</h5>
                        </div>
                        <div class="card-body">
                            <!-- Inventory Overview -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card stats-card bg-primary text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Items</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['total_items']); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-success text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">In Stock</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['in_stock_items']); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-warning text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Low Stock</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['low_stock_items']); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-danger text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Out of Stock</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['out_of_stock_items']); ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Add Item Form -->
                            <form method="POST" class="mb-4">
                                <input type="hidden" name="action" value="add_item">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Item Name</label>
                                        <input type="text" name="item_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Category</label>
                                        <select name="category" class="form-select" required>
                                            <option value="">Select Category</option>
                                            <option value="Medicine">Medicine</option>
                                            <option value="Equipment">Equipment</option>
                                            <option value="Supplies">Supplies</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="quantity" class="form-control" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Unit</label>
                                        <select name="unit" class="form-select" required>
                                            <option value="Pieces">Pieces</option>
                                            <option value="Boxes">Boxes</option>
                                            <option value="Sets">Sets</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Price</label>
                                        <input type="number" name="price" class="form-control" step="0.01" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Supplier</label>
                                        <input type="text" name="supplier" class="form-control" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Expiry Date</label>
                                        <input type="date" name="expiry_date" class="form-control">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select" required>
                                            <option value="In Stock">In Stock</option>
                                            <option value="Low Stock">Low Stock</option>
                                            <option value="Out of Stock">Out of Stock</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add Item
                                </button>
                            </form>

                            <!-- Inventory Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Category</th>
                                            <th>Quantity</th>
                                            <th>Unit</th>
                                            <th>Price</th>
                                            <th>Supplier</th>
                                            <th>Expiry Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($item = $items->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                                            <td><?php echo number_format($item['quantity']); ?></td>
                                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($item['supplier']); ?></td>
                                            <td><?php echo $item['expiry_date'] ? date('Y-m-d', strtotime($item['expiry_date'])) : 'N/A'; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $item['status'] === 'In Stock' ? 'success' : 
                                                        ($item['status'] === 'Low Stock' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo htmlspecialchars($item['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editItem(<?php echo $item['item_id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteItem(<?php echo $item['item_id']; ?>)">
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

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editItemForm">
                        <input type="hidden" name="action" value="update_item">
                        <input type="hidden" name="item_id" id="edit_item_id">
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="In Stock">In Stock</option>
                                <option value="Low Stock">Low Stock</option>
                                <option value="Out of Stock">Out of Stock</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Item</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editItem(itemId) {
            fetch(`get_item_details.php?id=${itemId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_item_id').value = data.item_id;
                    document.querySelector('#editItemForm [name="quantity"]').value = data.quantity;
                    document.querySelector('#editItemForm [name="price"]').value = data.price;
                    document.querySelector('#editItemForm [name="status"]').value = data.status;
                    new bootstrap.Modal(document.getElementById('editItemModal')).show();
                });
        }

        function deleteItem(itemId) {
            if (confirm('Are you sure you want to delete this item?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_item">
                    <input type="hidden" name="item_id" value="${itemId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
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