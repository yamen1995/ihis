<?php
session_start();
require_once '../includes/db_connect.php';
if (isset($_POST['login-btn'])){
$username = $_POST['phone_number'];
$password = $_POST['security_code'];
$sql = "SELECT * FROM Creds WHERE Phone=$username";
$result=$conn->query($sql);
$user = $result->fetch_array();
if($user['Sec_Code']==$password and $user['PC_Type']=="ST"){echo "log in successfuly";
 $_SESSION['staff_ID'] = $user['CR_ID'];
                header('Location: staff_dashboard.php');
                exit();
}
else{echo $user['Sec_Code'];}}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IhIS - Staff Login</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>Staff Login</h1>
        </div>
    </header>

    <div class="login-container">
        <form method="POST" action="staff_login.php">
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