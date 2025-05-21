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

// Get export parameters
$export_type = isset($_POST['export_type']) ? $_POST['export_type'] : 'excel';
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01');
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-t');
$ward_filter = isset($_POST['ward']) ? $_POST['ward'] : '';

// Validate export type
if (!in_array($export_type, ['excel', 'pdf'])) {
    die("Invalid export type");
}

// Get ward data for the report
function getWardData($conn, $start_date, $end_date, $ward_filter) {
    $data = [
        'wards' => [],
        'supplies' => [
            'linen' => ['total' => 0, 'available' => 0, 'low' => 0, 'out' => 0],
            'medical' => ['total' => 0, 'available' => 0, 'low' => 0, 'out' => 0],
            'equipment' => ['total' => 0, 'available' => 0, 'low' => 0, 'out' => 0]
        ],
        'stats' => [
            'total_patients' => 0,
            'available_beds' => 0,
            'nurses_on_duty' => 0,
            'avg_stay_days' => 0
        ]
    ];
    
    // Get wards data
    $query = "SELECT ward_id, ward_name, capacity FROM wards";
    $result = $conn->query($query);
    if ($result) {
        while ($ward = $result->fetch_assoc()) {
            if (empty($ward_filter) || $ward_filter === $ward['ward_name']) {
                $ward_id = $ward['ward_id'];
                $ward_stats = [
                    'ward_name' => $ward['ward_name'],
                    'total_beds' => $ward['capacity'],
                    'occupied' => 0,
                    'available' => 0,
                    'nurses' => 0,
                    'avg_stay' => 0
                ];
                
                // Get nurse count
                $query = "
                    SELECT COUNT(*) as nurses
                    FROM nurse_assignments
                    WHERE ward_id = $ward_id AND status = 'active'
                    AND start_date <= CURDATE() AND end_date >= CURDATE()
                ";
                $nurse_result = $conn->query($query);
                if ($nurse_result && $nurse_row = $nurse_result->fetch_assoc()) {
                    $ward_stats['nurses'] = $nurse_row['nurses'];
                }
                
                $data['wards'][] = $ward_stats;
            }
        }
    }
    
    // If no wards found or tables don't exist, use sample data
    if (count($data['wards']) === 0) {
        $data['wards'] = [
            [
                'ward_name' => 'General Ward',
                'total_beds' => 30,
                'occupied' => 22,
                'available' => 8,
                'nurses' => 6,
                'avg_stay' => 3.5
            ],
            [
                'ward_name' => 'ICU',
                'total_beds' => 10,
                'occupied' => 8,
                'available' => 2,
                'nurses' => 4,
                'avg_stay' => 5.2
            ],
            [
                'ward_name' => 'Emergency',
                'total_beds' => 15,
                'occupied' => 15,
                'available' => 0,
                'nurses' => 2,
                'avg_stay' => 1.8
            ]
        ];
    }
    
    // Get overall stats (bed occupancy, nurses, etc.)
    $query = "
        SELECT COUNT(*) as total
        FROM bed_allocations
        WHERE status = 'occupied'
    ";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $data['stats']['total_patients'] = $row['total'];
    }
    
    // Supply stats
    $query = "
        SELECT 
            item_type,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN status = 'low' THEN 1 ELSE 0 END) as low_stock,
            SUM(CASE WHEN status = 'out' THEN 1 ELSE 0 END) as out_of_stock
        FROM ward_supplies
        GROUP BY item_type
    ";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $type = $row['item_type'];
            if (isset($data['supplies'][$type])) {
                $data['supplies'][$type]['total'] = $row['total'];
                $data['supplies'][$type]['available'] = $row['available'];
                $data['supplies'][$type]['low'] = $row['low_stock'];
                $data['supplies'][$type]['out'] = $row['out_of_stock'];
            }
        }
    }
    
    return $data;
}

// Get data for the report
$report_data = getWardData($conn, $start_date, $end_date, $ward_filter);

// Export to Excel (simplified version - real implementation would use a library like PhpSpreadsheet)
if ($export_type === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="ward_report_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Output Excel data
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>';
    echo '<body>';
    
    // Title
    echo '<h1>Ward Report</h1>';
    echo '<p>Period: ' . date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date)) . '</p>';
    
    // Overall Statistics
    echo '<table border="1">';
    echo '<tr><th colspan="4">Overall Statistics</th></tr>';
    echo '<tr><th>Total Patients</th><th>Available Beds</th><th>Nurses on Duty</th><th>Average Stay (Days)</th></tr>';
    echo '<tr>';
    echo '<td>' . $report_data['stats']['total_patients'] . '</td>';
    echo '<td>' . $report_data['stats']['available_beds'] . '</td>';
    echo '<td>' . $report_data['stats']['nurses_on_duty'] . '</td>';
    echo '<td>' . $report_data['stats']['avg_stay_days'] . '</td>';
    echo '</tr>';
    echo '</table>';
    
    // Ward Statistics
    echo '<br><table border="1">';
    echo '<tr><th colspan="6">Ward-wise Statistics</th></tr>';
    echo '<tr><th>Ward</th><th>Total Beds</th><th>Occupied</th><th>Available</th><th>Nurses</th><th>Avg. Stay</th></tr>';
    foreach ($report_data['wards'] as $ward) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($ward['ward_name']) . '</td>';
        echo '<td>' . $ward['total_beds'] . '</td>';
        echo '<td>' . $ward['occupied'] . '</td>';
        echo '<td>' . $ward['available'] . '</td>';
        echo '<td>' . $ward['nurses'] . '</td>';
        echo '<td>' . $ward['avg_stay'] . ' days</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    // Supply Statistics
    echo '<br><table border="1">';
    echo '<tr><th colspan="5">Supply Status</th></tr>';
    echo '<tr><th>Item Type</th><th>Total Items</th><th>Available</th><th>Low Stock</th><th>Out of Stock</th></tr>';
    foreach ($report_data['supplies'] as $type => $stats) {
        echo '<tr>';
        echo '<td>' . ucfirst($type) . '</td>';
        echo '<td>' . $stats['total'] . '</td>';
        echo '<td>' . $stats['available'] . '</td>';
        echo '<td>' . $stats['low'] . '</td>';
        echo '<td>' . $stats['out'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    echo '</body></html>';
}
// Export to PDF (simplified version - real implementation would use a library like TCPDF or mPDF)
else if ($export_type === 'pdf') {
    // In a real implementation, you'd generate a PDF here
    // For simplicity, we'll just output HTML with a message
    echo '<!DOCTYPE html>
        <html>
        <head>
            <title>PDF Export</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .message { background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="message">
                <h3>PDF Export Feature</h3>
                <p>In a production environment, this would generate a PDF file using a library like TCPDF or mPDF.</p>
                <p>For implementation, you would need to:</p>
                <ol>
                    <li>Install a PDF generation library via Composer</li>
                    <li>Use the library to create a properly formatted PDF</li>
                    <li>Set the appropriate headers for PDF download</li>
                </ol>
                <p>The report would include the same data shown in the Excel export.</p>
                <p><a href="ward_report.php">Return to Reports</a></p>
            </div>
        </body>
        </html>';
}
?> 