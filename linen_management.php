<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: loginDefault.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linen Management</title>
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
                            <h5 class="mb-0">Linen Management</h5>
                        </div>
                        <div class="card-body">
                            <!-- Linen Status Overview -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Items</h6>
                                            <h3 class="mb-0">1,000</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Available</h6>
                                            <h3 class="mb-0">750</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">In Laundry</h6>
                                            <h3 class="mb-0">200</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-danger text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Damaged</h6>
                                            <h3 class="mb-0">50</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Linen Management Form -->
                            <form method="POST" class="mb-4">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Item Type</label>
                                        <select name="item_type" class="form-select" required>
                                            <option value="">Select Type</option>
                                            <option value="bed_sheet">Bed Sheet</option>
                                            <option value="pillow_case">Pillow Case</option>
                                            <option value="blanket">Blanket</option>
                                            <option value="towel">Towel</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="quantity" class="form-control" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select" required>
                                            <option value="available">Available</option>
                                            <option value="laundry">In Laundry</option>
                                            <option value="damaged">Damaged</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Location</label>
                                        <select name="location" class="form-select" required>
                                            <option value="">Select Location</option>
                                            <option value="ward_a">Ward A</option>
                                            <option value="ward_b">Ward B</option>
                                            <option value="laundry">Laundry Room</option>
                                            <option value="storage">Storage</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Item</button>
                            </form>

                            <!-- Linen Inventory Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Item Type</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                            <th>Location</th>
                                            <th>Last Updated</th>
                                            <th>Notes</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Bed Sheet</td>
                                            <td>100</td>
                                            <td><span class="badge bg-success">Available</span></td>
                                            <td>Ward A</td>
                                            <td>Jan 15, 2024</td>
                                            <td>New stock</td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editItem()">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteItem()">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Pillow Case</td>
                                            <td>50</td>
                                            <td><span class="badge bg-warning">In Laundry</span></td>
                                            <td>Laundry Room</td>
                                            <td>Jan 16, 2024</td>
                                            <td>Being washed</td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editItem()">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteItem()">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
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
                    <h5 class="modal-title">Edit Linen Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editItemForm">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="available">Available</option>
                                <option value="laundry">In Laundry</option>
                                <option value="damaged">Damaged</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <select name="location" class="form-select" required>
                                <option value="ward_a">Ward A</option>
                                <option value="ward_b">Ward B</option>
                                <option value="laundry">Laundry Room</option>
                                <option value="storage">Storage</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Item</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editItem() {
            new bootstrap.Modal(document.getElementById('editItemModal')).show();
        }

        function deleteItem() {
            if (confirm('Are you sure you want to delete this item?')) {
                // Handle delete action
            }
        }
    </script>
</body>
</html> 