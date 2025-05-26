<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Function to generate security code
function generateSecurityCode() {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = 'PA';
    for ($i = 0; $i < 10; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Get patient ID from URL
$patient_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch patient data
$patient = [];
$staff_members = [];
$rooms = [];

try {
    // Get patient details
    $stmt = $conn->prepare("
        SELECT p.*, c.FName, c.LName, c.DOB, c.DOA, c.Phone, c.Address, c.SEC_Code, c.CR_ID
        FROM patients p
        JOIN creds c ON p.CR_ID = c.CR_ID
        WHERE p.PA_ID = ?
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();

    // Get staff and room lists
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

} catch (Exception $e) {
    die("Error fetching data: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'FName' => $_POST['first_name'],
        'LName' => $_POST['last_name'],
        'Gender' => $_POST['gender'],
        'DOB' => $_POST['date_of_birth'],
        'DOA' => $_POST['date_of_arrival'],
        'Phone' => $_POST['phone'],
        'Address' => $_POST['address'],
        'Med_History' => $_POST['medical_history'],
        'IS_Active' => isset($_POST['is_active']) ? 1 : 0,
        'ST_ID' => $_POST['staff_id'] ?: NULL,
        'RO_ID' => $_POST['room_id'] ?: NULL,
        'CR_ID' => $patient['CR_ID']
    ];

    // Generate new security code if requested
    $new_code = isset($_POST['reset_code']) ? generateSecurityCode() : $patient['SEC_Code'];

    try {
        $conn->begin_transaction();

        // Update creds table
        $stmt = $conn->prepare("
            UPDATE creds SET
                FName = ?,
                LName = ?,
                Gender = ?,
                DOB = ?,
                DOA = ?,
                Phone = ?,
                Address = ?,
                SEC_Code = ?
            WHERE CR_ID = ?
        ");
        $stmt->bind_param("ssssssssi", 
            $data['FName'], $data['LName'], $data['Gender'],$data['DOB'], 
            $data['DOA'], $data['Phone'], $data['Address'],
            $new_code, $data['CR_ID']
        );
        $stmt->execute();

        // Update patients table
        $stmt = $conn->prepare("
            UPDATE patients SET
                Med_History = ?,
                IS_Active = ?,
                ST_ID = ?,
                RO_ID = ?
            WHERE PA_ID = ?
        ");
        $stmt->bind_param("siiii", 
            $data['Med_History'], $data['IS_Active'], 
            $data['ST_ID'], $data['RO_ID'], $patient_id
        );
        $stmt->execute();

        $conn->commit();

        // Show new code if reset
        if (isset($_POST['reset_code'])) {
            echo "<script>
                alert('Security Code Reset Successful! New Code: $new_code');
                window.location.href = 'edit.php?id=$patient_id';
            </script>";
        } else {
            header("Location: view.php?success=Patient updated successfully");
        }
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error updating patient: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Patient</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
    <style>
        .security-code-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #dee2e6;
        }
        .code-display {
            font-family: monospace;
            font-size: 1.2rem;
            letter-spacing: 2px;
            color: #0056b3;
            margin: 10px 0;
            padding: 10px;
            background-color: white;
            border: 1px dashed #adb5bd;
            display: inline-block;
        }
        .reset-code-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        .reset-code-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>Edit Patient</h1>
        </div>
    </header>

    <div class="content-container">
        <h2>Edit Patient Record</h2>
        <a href="view.php" class="back-btn">&larr; Back to Patients</a>
        
        <?php if(isset($error)): ?>
            <div class="error-message"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" class="patient-form">
            <div class="form-row">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($patient['FName'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($patient['LName'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Date of Birth *</label>
                    <input type="date" name="date_of_birth" value="<?= htmlspecialchars($patient['DOB'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Date of Arrival *</label>
                    <input type="date" name="date_of_arrival" value="<?= htmlspecialchars($patient['DOA'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($patient['Phone'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Address *</label>
                    <textarea name="address" rows="3" required><?= htmlspecialchars($patient['Address'] ?? '') ?></textarea>
                </div>
            </div>
            
            <div class="security-code-section">
                <h3>Security Code</h3>
                <div class="code-display"><?= htmlspecialchars($patient['SEC_Code'] ?? '') ?></div>
                <button type="button" class="reset-code-btn" onclick="if(confirm('Generate new security code?')){ 
                    document.getElementById('reset-code-input').value = '1'; 
                    this.form.submit(); 
                }">Reset Code</button>
                <input type="hidden" id="reset-code-input" name="reset_code" value="0">
                <p class="notice">Patient must visit the hospital to receive the new code.</p>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Status</label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" <?= ($patient['IS_Active'] ?? 1) ? 'checked' : '' ?>> 
                        Active Patient
                    </label>
                </div>
            </div>
                  <div class="form-row">
                    <label>gender</label>
                    chose gender
                    <label class="checkbox-label">
                        male<input type="radio" name="gender" value="m" checked>
                        female<input type="radio" name="gender" value="f"> 
                    </label> 
                    </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Assigned Staff</label>
                    <select name="staff_id">
                        <option value="">-- Select Staff --</option>
                        <?php foreach ($staff_members as $staff): ?>
                            <option value="<?= $staff['ST_ID'] ?>" <?= ($staff['ST_ID'] == ($patient['ST_ID'] ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($staff['FName'] . ' ' . $staff['LName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
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
            
            <div class="form-group">
                <label>Medical History</label>
                <textarea name="medical_history" rows="5"><?= htmlspecialchars($patient['Med_History'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="submit-btn">Update Patient</button>
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