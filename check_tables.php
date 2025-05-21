<?php
include 'config.php';

// Get all tables from the database
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

// Check for medical package related tables
$medicalPackageTables = array_filter($tables, function($table) {
    return strpos($table, 'medical_') === 0 || strpos($table, 'package_') === 0;
});

// Output results
echo "<h1>Medical Package Tables</h1>";
if (count($medicalPackageTables) > 0) {
    echo "<ul>";
    foreach ($medicalPackageTables as $table) {
        echo "<li>" . $table . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No medical package tables found. Need to create them.</p>";
}

// Show all tables for reference
echo "<h2>All Tables</h2>";
echo "<ul>";
foreach ($tables as $table) {
    echo "<li>" . $table . "</li>";
}
echo "</ul>";
?> 