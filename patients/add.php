<?php
require_once '../includes/db_connect.php';
function generateSecurityCode() {
   $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = 'PA'; // Prefix
    
    for ($i = 0; $i < 10; $i++) { // 8 more characters = total 10 with prefix
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString; // Example: PA3B8X9Z2Y1
}
$staff_members = [];
$rooms = [];
    $staff_members = $conn->query("
        SELECT staffs.ST_ID, creds.FName, creds.LName 
        FROM staffs, creds 
        WHERE staffs.CR_ID=creds.CR_ID
    ")->fetch_all(MYSQLI_ASSOC);
    $rooms = $conn->query("
        SELECT RO_ID, Ro_Num 
        FROM rooms 
        WHERE Is_Occupied = 0 OR RO_ID = " . ($patient['RO_ID'] ?? 0)
    )->fetch_all(MYSQLI_ASSOC);
$security_code = '';
$show_modal = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $security_code = generateSecurityCode();
    $data = [
        '_FName' => $_POST['first_name'],
        '_LName' => $_POST['last_name'],
        '_DOB' => $_POST['date_of_birth'],
        '_DOA' => $_POST['date_of_arrival'],
        '_Phone' => $_POST['phone'],
        '_Address' => $_POST['address'],
        '_Med_History' => $_POST['medical_history'],
        '_Gender' => $_POST['gender'],
        '_IS_Active' => isset($_POST['is_active']) ? 1 : 0,
        '_ST_ID' => $_POST['staff_id'] ?: NULL,
        '_RO_ID' => $_POST['room_id'] ?: NULL
    ];
$type = "pa";

        $conn->query("
           INSERT INTO creds (FName, LName, DOB, DOA, Phone, Address, SEC_Code, Gender, PC_Type)
            VALUES ('$data[_FName]', '$data[_LName]', '$data[_DOB]', '$data[_DOA]', '$data[_Phone]', '$data[_Address]', '$data[_Gender]','$security_code', '$type')");
        $conn->multi_query("INSERT INTO patients (CR_ID)
            SELECT CR_ID FROM creds WHERE FName='$data[_FName]';
            UPDATE patients set Med_History='$data[_Med_History]', IS_Active='$data[_IS_Active]', ST_ID='$data[_ST_ID]', RO_ID='$data[_RO_ID]'
           WHERE  CR_ID = SELECT CR_ID FROM creds WHERE FName='$data[_FName]';  
        ");
 $show_modal = true;
}
?>
        



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Patient</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
</head>
<body>
<header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>Add New Patient</h1>
        </div>
    </header>
    <div class="content-container">
        <h2>Add New Patient</h2>
        <a href="view.php" class="back-btn">&larr; Back to Patients</a>
        
        <?php if(isset($error)): ?>
            <div class="error-message"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" class="patient-form">
          
            <div class="form-row">
                <div class="form-group" style ="margin: auto;">
                    <label>First Name *</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group" style ="margin: auto;">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" required>
                </div>
            
            
            
                <div class="form-group"style ="margin: auto;">
                    <label>Date of Birth *</label>
                    <input type="date" name="date_of_birth" required>
                </div>
                <div class="form-group"style ="margin: auto;">
                    <label>Date of Arrival *</label>
                    <input type="date" name="date_of_arrival" required>
                </div>
            </div>
            <div class="form-row">
            <div class="form-group"style ="margin: auto;">
                <label>Phone Number *</label>
                <input type="tel" name="phone" required>
            </div>
            
            <div class="form-group"style ="margin: auto;">
                <label>Address *</label>
                <textarea name="address" rows="3" required></textarea>
            </div>
            </div>
            <div class="form-row">
                <div class="form-group"style ="margin: auto;">
                  <div class="form-row">
                    <label>Status</label>
                    Active Patient
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" checked> 
                    </label> 
                    </div>
                    <div class="form-group"style ="margin: auto;">
                  <div class="form-row">
                    <label>gender</label>
                    chose gender
                    <label class="checkbox-label">
                        male<input type="radio" name="gender" value="m" checked>
                        female<input type="radio" name="gender" value="f"> 
                    </label> 
                    </div>
                  <div class="form-row">
                <div class="form-group"style ="margin: auto;">
                    <label>Assigned Staff</label>
                    <select name="staff_id">
                        <option value="">-- Select Staff --</option>
                        <?php foreach ($staff_members as $staff): ?>
                            <option value="<?= $staff['ST_ID'] ?>"><?= htmlspecialchars($staff['FName']." ".$staff['LName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"style ="margin: auto;">
                    <label>Assigned Room</label>
                    <select name="room_id">
                        <option value="">-- Select Room --</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= $room['RO_ID'] ?>" <?= ($room['RO_ID'] == ($patient['RO_ID'] ?? '')) ? 'selected' : '' ?>>
                                Room <?= htmlspecialchars($room['Ro_Num']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
                </div>
            
            
            <div class="form-group"style ="margin: auto;">
                <label>Medical History</label>
                <textarea name="medical_history" rows="5" coulmons="50"></textarea>
            </div>
            </div>
            </div>
            
            <button type="submit" class="submit-btn">Add Patient</button>
        </form>
    </div>
    <?php if ($show_modal): ?>
    <div id="securityCodeModal" class="modal" style="display:block;">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3>Patient Security Code Generated</h3>
            <div class="code-display">
                <p>This is the patient's security code:</p>
                <div class="code"><?= $security_code ?></div>
                <p class="notice">Please provide this code to the patient.</p>
                <p class="notice">The patient can visit the hospital to reset if forgotten.</p>
            </div>
            <button class="confirm-btn" onclick="window.location.href='view.php'">I've Recorded the Code</button>
        </div>
    </div>
    <?php endif; ?>
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
<script>
        // Close modal when X is clicked
        document.querySelector('.close-btn')?.addEventListener('click', function() {
            document.getElementById('securityCodeModal').style.display = 'none';
            window.location.href = 'view.php';
        });

        // Close when clicking outside modal
        window.onclick = function(event) {
            const modal = document.getElementById('securityCodeModal');
            if (event.target == modal) {
                modal.style.display = 'none';
                window.location.href = 'view.php';
            }
        }
    </script>
</body>
</html>