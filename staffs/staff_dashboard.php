<?php
session_start();
$S_Id = $_SESSION['staff_ID'];
require_once '../includes/db_connect.php';
$sql = "SELECT * FROM Creds WHERE CR_ID=$S_Id";
$result=$conn->query($sql);
$user = $result->fetch_array();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IhIS - Staff Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>Staff Dashboard</h1>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="welcome-message">
            <h2>Welcome, <?php echo htmlspecialchars($user['FName'] ." ". $user['LName']); ?> </h2>
        </div>
        
        <div class="dashboard-menu">
             <div class="menu-card">
                <h3>Patient Records</h3>
                <p>create and manage patient records</p>
                <div class="card-actions">
                    <a href="../patients/view.php" class="action-btn view">View All</a>
                    <a href="../patients/add.php" class="action-btn add">Add New</a>
                </div>
                
            </div>
            
            <div class="menu-card">
                <h3>Appointments</h3>
                <p>Schedule and manage patient appointments</p>
                <div class="card-actions">
                    <a href="../encounters/view.php" class="action-btn view">View All</a>
                    <a href="../encounters/add.php" class="action-btn add">Schedule New</a>
                </div>
            </div>
            
            <div class="menu-card">
                <h3>Reports and rooms</h3>
                <p>Generate system reports and analytics</p>
                <div class="report-options">
                 <div class="form-row">
                    <a href="../reports/rooms.php" class="action-btn view">rooms</a>
                    <a href="../reports/patient_report.php" class="action-btn add">Patient Statistics</a>
                 </div>                
                    <a href="../reports/doctor_report.php" class="action-btn add">Doctors reports</a>
                    
                </div>
            </div>
        </div>
        
        <a href="logout.php" class="logout-btn">Logout</a>
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

