<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: loginDefault.php");
    exit();
}

// Get filter parameters
$format = $_GET['format'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'financial';
$department = $_GET['department'] ?? 'all';

// Validate format
if ($format !== 'excel' && $format !== 'pdf') {
    die('Invalid export format');
}

// For Excel/CSV export
if ($format === 'excel') {
    // Generate filename
    $filename = 'Financial_Report_' . date('Y-m-d', strtotime($start_date)) . '_to_' . date('Y-m-d', strtotime($end_date)) . '.csv';

    // Set headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Create output stream
    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Write header information
    fputcsv($output, ['HOSPITAL FINANCIAL REPORT']);
    fputcsv($output, ['Period: ' . date('F d, Y', strtotime($start_date)) . ' to ' . date('F d, Y', strtotime($end_date))]);
    $deptText = $department === 'all' ? 'All Departments' : ucfirst($department) . ' Department';
    fputcsv($output, ['Department: ' . $deptText]);
    fputcsv($output, []); // Empty line

    // Write Financial Overview
    fputcsv($output, ['FINANCIAL OVERVIEW']);
    $overviewData = [
        ['Total Revenue', '₱1,500,000'],
        ['Total Expenses', '₱800,000'],
        ['Net Income', '₱700,000'],
        ['Pending Payments', '₱200,000']
    ];
    foreach ($overviewData as $row) {
        fputcsv($output, $row);
    }
    fputcsv($output, []); // Empty line

    // Write Financial Summary
    fputcsv($output, ['FINANCIAL SUMMARY']);
    fputcsv($output, ['Category', 'Revenue', 'Expenses', 'Net', '% of Total']);
    $summaryData = [
        ['Inpatient Services', '₱800,000', '₱400,000', '₱400,000', '57.1%'],
        ['Outpatient Services', '₱500,000', '₱250,000', '₱250,000', '35.7%'],
        ['Emergency Services', '₱200,000', '₱150,000', '₱50,000', '7.2%']
    ];
    foreach ($summaryData as $row) {
        fputcsv($output, $row);
    }
    fputcsv($output, []); // Empty line

    // Write Service Statistics
    fputcsv($output, ['SERVICE STATISTICS']);
    fputcsv($output, ['Service Type', 'Total Cases', 'Average Cost', 'Total Revenue', 'Success Rate']);
    $serviceData = [
        ['General Consultation', '1,200', '₱500', '₱600,000', '98%'],
        ['Specialized Treatment', '500', '₱2,000', '₱1,000,000', '95%'],
        ['Emergency Care', '300', '₱1,500', '₱450,000', '92%']
    ];
    foreach ($serviceData as $row) {
        fputcsv($output, $row);
    }

    // Close the output stream
    fclose($output);
    exit;
}

// For PDF export (placeholder for future implementation)
if ($format === 'pdf') {
    // PDF export code will be implemented later
    die('PDF export functionality is under development');
}
?> 