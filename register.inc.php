<?php
session_start();
$servername = "localhost:3306";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password is empty
$dbname = "core3";

// Create connection
$Connection = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($Connection->connect_error) {
    die("Connection failed: " . $Connection->connect_error);
}

// Check if account_type column exists, if not create it
$check_column = $Connection->query("SHOW COLUMNS FROM login LIKE 'account_type'");
if ($check_column->num_rows == 0) {
    $Connection->query("ALTER TABLE login ADD COLUMN account_type VARCHAR(50) DEFAULT 'user'");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Email = mysqli_real_escape_string($Connection, trim($_POST["email"]));
    $Password = mysqli_real_escape_string($Connection, trim($_POST["password"]));
    $cpassword = mysqli_real_escape_string($Connection, trim($_POST["cpassword"]));
    $user_role = mysqli_real_escape_string($Connection, trim($_POST["user_role"]));

    // Check if passwords match
    if ($cpassword === $Password) {
        // Check if any fields are empty
        if (empty($Email) || empty($Password) || empty($cpassword)) {
            $_SESSION['errorpo'] = "All fields are required";
            header("location: register.php");
            $Connection->close();
            exit();
        } else {
            // First check if email already exists using prepared statement
            $check_stmt = $Connection->prepare("SELECT * FROM login WHERE email = ?");
            $check_stmt->bind_param("s", $Email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $_SESSION['errorpo'] = "Email already exists! Please use a different email.";
                header("location: register.php");
                $Connection->close();
                exit();
            }
            $check_stmt->close();

            // If email doesn't exist, proceed with registration using prepared statement
            $insert_stmt = $Connection->prepare("INSERT INTO login (email, password, account_type) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $Email, $Password, $user_role);
            
            // Execute the prepared statement
            if ($insert_stmt->execute()) {
                $_SESSION['status'] = "Account Successfully Registered";
                header("location: loginDefault.php");
                $Connection->close();
                exit();
            } else {
                $_SESSION['errorpo'] = "Registration failed! Please try again. Error: " . $Connection->error;
                header("location: register.php");
                $Connection->close();
                exit();
            }
            $insert_stmt->close();
        }
    } else {
        $_SESSION['errorpo'] = "Password did not match!";
        header("location: register.php");
        $Connection->close();
        exit();
    }
}
?>