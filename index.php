
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IhIS - Integrated Health Information System</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="images/ihis-logo.png" alt="IhIS Logo" class="logo-img" style="border-radius: 50%;">
            <h1>Integrated Health Information System (IhIS)</h1>
        </div>
    </header>

    <div style=" margin-left: auto !important;margin-right: auto !important; margin-top: 35px">
        <img src="images/healthcare-image.jpg" alt="Healthcare System" class="main-image" style="margin-bottom: 20px;">
        
        <div class="welcome-text"style="margin-bottom: 20px;">
            <h2>Welcome to IhIS</h2style="margin-bottom: 20px;">
            <p><h3>Your integrated solution for comprehensive healthcare management</h3></p>
        </div>
        
        <div class="login-button" style="margin-bottom: 35px;margin-left: 30px;">
            <button class="login-btn" style="margin-right: 90px;" onclick="location.href='staffs/staff_login.php'">Staff Login</button>
            <button class="login-btn" style="margin-right: 90px;" onclick="location.href='doctors/doctor_login.php'">Doctor Login</button>
            <button class="login-btn" style="margin-right: 90px;" onclick="location.href='patients/patient_login.php'">Patient Login</button>
        </div>
    </div>

    <footer>
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0;">
        <!-- Left side - Company logo and name -->
        <div style="display: flex; align-items: center;">
            <img src="images/jadara.png" alt="IhIS Logo" style="height: 80px;border-radius: 50%;margin-right: 10px;">
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