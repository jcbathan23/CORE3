<?php
include 'config.php';

$sql = "SHOW COLUMNS FROM beds";
$result = $conn->query($sql);

if ($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?> 