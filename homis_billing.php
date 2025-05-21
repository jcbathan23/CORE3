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
            case 'create_bill':
                $stmt = $conn->prepare("INSERT INTO homis_bills (patient_name, service_type, amount, description, due_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdss", 
                    $_POST['patient_name'],
                    $_POST['service_type'],
                    $_POST['amount'],
                    $_POST['description'],
                    $_POST['due_date']
                );
                $stmt->execute();
                break;

            case 'record_payment':
                $stmt = $conn->prepare("INSERT INTO homis_payments (bill_id, amount, payment_method, payment_date, notes) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("idsss", $_POST['bill_id'], $_POST['payment_amount'], $_POST['payment_method'], $_POST['payment_date'], $_POST['payment_notes']);
                
                // Update bill status to Paid
                $conn->begin_transaction();
                try {
                    $stmt->execute();
                    $stmt = $conn->prepare("UPDATE homis_bills SET status = 'Paid' WHERE bill_id = ?");
                    $stmt->bind_param("i", $_POST['bill_id']);
                    $stmt->execute();
                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                }
                break;

            case 'delete_bill':
                // Begin transaction
                $conn->begin_transaction();
                try {
                    // First delete associated payments
                    $stmt = $conn->prepare("DELETE FROM homis_payments WHERE bill_id = ?");
                    $stmt->bind_param("i", $_POST['bill_id']);
                    $stmt->execute();
                    
                    // Then delete the bill
                    $stmt = $conn->prepare("DELETE FROM homis_bills WHERE bill_id = ?");
                    $stmt->bind_param("i", $_POST['bill_id']);
                    $stmt->execute();
                    
                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                }
                break;
        }
    }
}

// Fetch billing statistics
$sql = "SELECT 
            COUNT(*) as total_bills,
            SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as paid_amount,
            SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN status = 'Overdue' THEN amount ELSE 0 END) as overdue_amount
        FROM homis_bills";
$stats = $conn->query($sql)->fetch_assoc();

// Fetch all bills with patient information
$sql = "SELECT * FROM homis_bills ORDER BY created_at DESC";
$bills = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOMIS Billing</title>
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
                            <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>HOMIS Billing</h5>
                        </div>
                        <div class="card-body">
                            <!-- Billing Overview -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card stats-card bg-primary text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Total Bills</h6>
                                            <h3 class="mb-0">₱<?php echo number_format($stats['total_bills']); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-success text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Paid</h6>
                                            <h3 class="mb-0">₱<?php echo number_format($stats['paid_amount'], 2); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-warning text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Pending</h6>
                                            <h3 class="mb-0">₱<?php echo number_format($stats['pending_amount'], 2); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card stats-card bg-danger text-white">
                                        <div class="card-body">
                                            <h6 class="card-title">Overdue</h6>
                                            <h3 class="mb-0">₱<?php echo number_format($stats['overdue_amount'], 2); ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Create Bill Form -->
                            <form method="POST" class="mb-4">
                                <input type="hidden" name="action" value="create_bill">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Patient Name</label>
                                        <input type="text" name="patient_name" class="form-control" placeholder="Enter patient name">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Service Type</label>
                                        <select name="service_type" class="form-select" required>
                                            <option value="">Select Service</option>
                                            <option value="consultation">Consultation</option>
                                            <option value="procedure">Procedure</option>
                                            <option value="medication">Medication</option>
                                            <option value="room">Room</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Amount</label>
                                        <input type="number" name="amount" class="form-control" step="0.01" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Due Date</label>
                                        <input type="date" name="due_date" class="form-control" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" rows="2" required></textarea>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Create Bill
                                </button>
                            </form>

                            <!-- Bills Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Bill ID</th>
                                            <th>Patient</th>
                                            <th>Service Type</th>
                                            <th>Amount</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($bill = $bills->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $bill['bill_id']; ?></td>
                                            <td><?php echo htmlspecialchars($bill['patient_name']); ?></td>
                                            <td><?php echo ucfirst(htmlspecialchars($bill['service_type'])); ?></td>
                                            <td>₱<?php echo number_format($bill['amount'], 2); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($bill['due_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $bill['status'] === 'Paid' ? 'success' : 
                                                        ($bill['status'] === 'Pending' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo htmlspecialchars($bill['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="viewBill(<?php echo $bill['bill_id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if($bill['status'] !== 'Paid'): ?>
                                                <button class="btn btn-sm btn-success" onclick="recordPayment(<?php echo $bill['bill_id']; ?>)">
                                                    <i class="fas fa-money-bill"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-danger" onclick="deleteBill(<?php echo $bill['bill_id']; ?>)">
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

    <!-- View Bill Modal -->
    <div class="modal fade" id="viewBillModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bill Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="billDetails"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Record Payment Modal -->
    <div class="modal fade" id="recordPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="paymentForm">
                        <input type="hidden" name="action" value="record_payment">
                        <input type="hidden" name="bill_id" id="payment_bill_id">
                        <div class="mb-3">
                            <label class="form-label">Payment Amount</label>
                            <input type="number" name="payment_amount" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Insurance">Insurance</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="payment_notes" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Record Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewBill(billId) {
            fetch(`get_bill_details.php?id=${billId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('billDetails').innerHTML = `
                        <div class="mb-3">
                            <label class="form-label fw-bold">Bill Information</label>
                            <p>Bill ID: ${data.bill_id}<br>
                            Patient: ${data.patient_name}<br>
                            Service Type: ${data.service_type}<br>
                            Amount: ₱${parseFloat(data.amount).toFixed(2)}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Payment Information</label>
                            <p>Due Date: ${data.due_date}<br>
                            Status: ${data.status}<br>
                            ${data.payment_date ? `Payment Date: ${data.payment_date}` : ''}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <p>${data.description}</p>
                        </div>
                    `;
            new bootstrap.Modal(document.getElementById('viewBillModal')).show();
                });
        }

        function recordPayment(billId) {
            document.getElementById('payment_bill_id').value = billId;
            new bootstrap.Modal(document.getElementById('recordPaymentModal')).show();
        }

        function deleteBill(billId) {
            if (confirm('Are you sure you want to delete this bill?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_bill">
                    <input type="hidden" name="bill_id" value="${billId}">
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