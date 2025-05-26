<?php
session_start();
require_once '../includes/db_connect.php';
if (isset($_POST['login-btn'])) {
    // Sanitize and validate inputs
    $username = trim($_POST['phone_number']);
    $password = trim($_POST['security_code']);
    
    // Check if inputs are empty
    if (empty($username) || empty($password)) {
        die("Phone number and security code are required");
    }

    // Use prepared statement to prevent SQL injection
    $sql = "SELECT * FROM Creds WHERE Phone = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Debugging: Check if any rows were returned
    if ($result->num_rows === 0) {
        die("No user found with that phone number");
    }
    
    $user = $result->fetch_assoc();
    
    // Debugging: Output the user data for inspection
    echo "<pre>User Data: ";
    print_r($user);
    echo "</pre>";
    
    // Verify credentials
    if ($user && $user['Sec_Code'] === $password && $user['PC_Type'] === "DO") {
        // Login successful
        $_SESSION['doctor_ID'] = $user['CR_ID'];
        $_SESSION['user_type'] = 'doctor';
        header('Location: doctor_dashboard.php');
        exit();
    } else {
        // Login failed
        die("Invalid security code or user type");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IhIS - doctor Login</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>doctor Login</h1>
        </div>
    </header>

    <div class="login-container">
        <form method="POST" action="doctor_login.php">
            <div class="form-group">
                <label for="phone_number">Phone Number</label>
                <input type="text" id="phone_number" name="phone_number" required>
            </div>
            
            <div class="form-group">
                <label for="security_code">Security Code</label>
                <input type="password" id="security_code" name="security_code" required>
            </div>
            
            <button type="submit" name="login-btn">Login</button>
        </form>
    </div>

    <footer>
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0;">
        <!-- Left side - Company logo and name -->
        <div style="display: flex; align-items: center;">
            <img src="../images/jadara.png" alt="IhIS Logo" style="height: 80px;border-radius: 50%;margin-right: 10px;">
            <span>jadara-uni
            <br> database application design</span>
        </div>
        
        <!-- Center - Year and system name -->
        <div>
            &copy; <?= date('Y') ?> IHIS - home page
        </div>
        
        <!-- Right side - Your name and college -->
        <div style="text-align: right;">
            <div>Developed by [يامن محمد رفعت تحسين]</div>
            <div>[حسن محمد حسن فوالجه]</div>
        </div>
    </div>
</footer>
</body>
</html>
<?php
$conn->close();
?>