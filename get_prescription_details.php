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

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $prescription_id = $_GET['id'];
    
    // Get prescription details
    $stmt = $conn->prepare("SELECT * FROM pharmacy_prescriptions WHERE prescription_id = ?");
    $stmt->bind_param("i", $prescription_id);
    $stmt->execute();
    $prescription = $stmt->get_result()->fetch_assoc();
    
    if (!$prescription) {
        echo "Prescription not found";
        exit();
    }
    
    // Get prescription items
    $stmt = $conn->prepare("SELECT * FROM pharmacy_prescription_items WHERE prescription_id = ?");
    $stmt->bind_param("i", $prescription_id);
    $stmt->execute();
    $items = $stmt->get_result();
    
    // Format the output
    ?>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Patient Information</label>
            <p>
                Name: <?php echo htmlspecialchars($prescription['patient_name']); ?><br>
                Patient ID: <?php echo htmlspecialchars($prescription['patient_id']); ?><br>
                <?php if ($prescription['status'] === 'filled'): ?>
                Dispensed By: <?php echo htmlspecialchars($prescription['dispensed_by']); ?><br>
                Dispensing Date: <?php echo date('M d, Y', strtotime($prescription['dispensing_date'])); ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Prescription Information</label>
            <p>
                Prescription ID: P<?php echo str_pad($prescription['prescription_id'], 3, '0', STR_PAD_LEFT); ?><br>
                Doctor: <?php echo htmlspecialchars($prescription['doctor']); ?><br>
                Date: <?php echo date('M d, Y', strtotime($prescription['prescription_date'])); ?><br>
                Status: 
                <?php 
                    if ($prescription['status'] === 'filled') {
                        echo '<span class="badge bg-success">Filled</span>';
                    } elseif ($prescription['status'] === 'pending') {
                        echo '<span class="badge bg-warning">Pending</span>';
                    } elseif ($prescription['status'] === 'rejected') {
                        echo '<span class="badge bg-danger">Rejected</span>';
                    }
                ?>
            </p>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label fw-bold">Prescribed Medicines</label>
        <table class="table">
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items->num_rows > 0): ?>
                    <?php while ($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['medicine_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['dosage']); ?></td>
                            <td><?php echo htmlspecialchars($item['frequency']); ?></td>
                            <td><?php echo htmlspecialchars($item['duration']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">No medicines found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($prescription['notes'])): ?>
    <div class="mb-3">
        <label class="form-label fw-bold">Notes</label>
        <p><?php echo nl2br(htmlspecialchars($prescription['notes'])); ?></p>
    </div>
    <?php endif; ?>
    <?php
} else {
    echo "Invalid prescription ID";
}

// Close database connection
$conn->close();
?> 