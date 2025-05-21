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

// Get report parameters
$format = isset($_GET['format']) ? $_GET['format'] : 'excel';
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01');
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-t');
$report_type = isset($_POST['report_type']) ? $_POST['report_type'] : 'packages';

// Set headers for file download
if ($format === 'excel') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="medical_package_report_' . $report_type . '_' . $start_date . '_to_' . $end_date . '.csv"');
    
    // Create output handle
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers based on report type
    if ($report_type === 'packages') {
        fputcsv($output, ['Medical Packages Report', 'From: ' . $start_date, 'To: ' . $end_date]);
        fputcsv($output, []);
        fputcsv($output, ['Package ID', 'Package Name', 'Description', 'Price', 'Duration (days)', 'Services', 'Created Date']);
        
        // Fetch data
        $query = "SELECT * FROM medical_packages WHERE 1=1";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Output data
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['package_id'],
                $row['package_name'],
                $row['description'],
                '₱' . number_format($row['price'], 2),
                $row['duration'],
                $row['services'],
                date('M d, Y', strtotime($row['created_at']))
            ]);
        }
    } elseif ($report_type === 'patient_packages') {
        fputcsv($output, ['Patient Packages Report', 'From: ' . $start_date, 'To: ' . $end_date]);
        fputcsv($output, []);
        fputcsv($output, ['ID', 'Patient', 'Package', 'Start Date', 'End Date', 'Status', 'Assigned Date']);
        
        // Fetch data
        $query = "SELECT pp.*, p.patient_name, mp.package_name 
                  FROM patient_packages pp
                  LEFT JOIN patients p ON pp.patient_id = p.patient_id
                  LEFT JOIN medical_packages mp ON pp.package_id = mp.package_id
                  WHERE pp.start_date BETWEEN ? AND ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Output data
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['patient_package_id'],
                $row['patient_name'],
                $row['package_name'],
                date('M d, Y', strtotime($row['start_date'])),
                date('M d, Y', strtotime($row['end_date'])),
                ucfirst($row['status']),
                date('M d, Y', strtotime($row['created_at']))
            ]);
        }
    } elseif ($report_type === 'payments') {
        fputcsv($output, ['Package Payments Report', 'From: ' . $start_date, 'To: ' . $end_date]);
        fputcsv($output, []);
        fputcsv($output, ['Payment ID', 'Patient', 'Package', 'Amount', 'Payment Date', 'Payment Method', 'Notes']);
        
        // Fetch data
        $query = "SELECT pp.*, p.patient_name, mp.package_name 
                  FROM package_payments pp
                  LEFT JOIN patient_packages pt ON pp.patient_package_id = pt.patient_package_id
                  LEFT JOIN patients p ON pt.patient_id = p.patient_id
                  LEFT JOIN medical_packages mp ON pt.package_id = mp.package_id
                  WHERE pp.payment_date BETWEEN ? AND ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Output data
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['payment_id'],
                $row['patient_name'],
                $row['package_name'],
                '₱' . number_format($row['amount'], 2),
                date('M d, Y', strtotime($row['payment_date'])),
                ucfirst(str_replace('_', ' ', $row['payment_method'])),
                $row['notes']
            ]);
        }
    } elseif ($report_type === 'services') {
        fputcsv($output, ['Service Utilization Report', 'From: ' . $start_date, 'To: ' . $end_date]);
        fputcsv($output, []);
        fputcsv($output, ['Service ID', 'Patient', 'Package', 'Service Name', 'Service Date', 'Status', 'Notes']);
        
        // Fetch data
        $query = "SELECT ps.*, p.patient_name, mp.package_name 
                  FROM package_services ps
                  LEFT JOIN patient_packages pt ON ps.patient_package_id = pt.patient_package_id
                  LEFT JOIN patients p ON pt.patient_id = p.patient_id
                  LEFT JOIN medical_packages mp ON pt.package_id = mp.package_id
                  WHERE ps.service_date BETWEEN ? AND ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Output data
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['service_id'],
                $row['patient_name'],
                $row['package_name'],
                $row['service_name'],
                date('M d, Y', strtotime($row['service_date'])),
                ucfirst($row['status']),
                $row['notes']
            ]);
        }
    }
    
    // Close output handle
    fclose($output);
    
} elseif ($format === 'pdf') {
    // PDF generation would require a library like TCPDF, FPDF, or mPDF
    // This is a placeholder for where that implementation would go
    
    // For now, just output a message
    echo "PDF export functionality would be implemented here with a PDF generation library.";
    echo "Report Type: $report_type<br>";
    echo "Date Range: $start_date to $end_date<br>";
}

// Close database connection
$conn->close();
?> 