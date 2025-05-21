<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include("Connections.php");

// Initialize variables
$email = $password = "";
$emailErr = $passwordErr = "";
$loginError = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    if (empty($_POST["email"])) {
        $emailErr = "Email is required";
    } else {
        $email = mysqli_real_escape_string($Connection, trim($_POST["email"]));
    }
    
    if (empty($_POST["password"])) {
        $passwordErr = "Password required";
    } else {
        $password = mysqli_real_escape_string($Connection, trim($_POST["password"]));
    }

    if ($password && $email) {
        // Check if account_type column exists, if not create it
        $check_column = mysqli_query($Connection, "SHOW COLUMNS FROM login LIKE 'account_type'");
        if (mysqli_num_rows($check_column) == 0) {
            mysqli_query($Connection, "ALTER TABLE login ADD COLUMN account_type VARCHAR(50) DEFAULT 'user'");
        }

        // Use prepared statement for login
        $stmt = $Connection->prepare("SELECT id, email, password, account_type FROM login WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($password == $row["password"]) {
                // Set session variables
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['account_type'] = $row['account_type'] ?? 'user';
                
                // Debug information
                error_log("Login successful. User ID: " . $_SESSION['user_id'] . ", Email: " . $_SESSION['email'] . ", Account Type: " . $_SESSION['account_type']);
                
                header("Location: index.php");
                exit();
            } else {
                $passwordErr = "Incorrect password";
            }
        } else {
            $emailErr = "Email not found";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.2/css/all.css" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="logo.png">
    <link rel="stylesheet" href="login.css?v=1.0">
</head>
<body>    
    <div class="logo">
        <img src="logo.png" alt="Logo" class="logo">
    </div>

    <form method="POST" class="login-form">
        <?php if (!empty($loginError)) { ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $loginError; ?>
            </div>
        <?php } ?>
        
        <?php if (isset($_SESSION['status'])) { ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo "<strong>" . $_SESSION['status'] . "</strong>"; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div> 
            <?php unset($_SESSION['status']); ?>
        <?php } ?>

        <h1 class="login-title">Login</h1>
        <div class="input-box">
            <i class='bx bxs-user'></i>
            <input type="text" name="email" placeholder="Username" value="<?php echo htmlspecialchars($email); ?>">
        </div>
        <span class="errorMessage text-danger">
            <?php echo $emailErr; ?>
        </span>
                      
        <div class="input-box">
            <i class='bx bxs-lock-alt'></i>
            <input type="password" name="password" placeholder="Password" value="<?php echo htmlspecialchars($password); ?>">
        </div>
        <span class="errorMessage text-danger">
            <?php echo $passwordErr; ?>
        </span>

        <div class="remember-forgot-box">
            <label for="remember">
                <input type="checkbox" id="remember">
                Remember me
            </label>
            <a href="#">Forgot Password?</a>
        </div>
        <button type="submit" class="login-btn">Login</button>
        <p class="register">
            Don't have an account?
            <a href="register.php">Register</a>
        </p>
    </form>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>