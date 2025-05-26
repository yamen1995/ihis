<?php
require_once '../includes/db_connect.php';
session_start();
$doctor_id = $_SESSION['doctor_ID'];
$error_message = '';
$success_message = '';

// Fetch current doctor information
try {
    $stmt = $conn->prepare("
        SELECT d.*, c.*
        FROM doctors d
        JOIN creds c ON d.CR_ID = c.CR_ID
        WHERE d.CR_ID = ?
    ");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor_info = $stmt->get_result()->fetch_assoc();
    
    if (!$doctor_info) {
        die("Doctor information not found.");
    }
} catch (Exception $e) {
    die("Error fetching doctor information: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $specialty = $_POST['specialty'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $sec_code = $_POST['sec_code'] ?? '';
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Update creds table
        $stmt = $conn->prepare("
            UPDATE creds 
            SET Phone = ?, Sec_Code = ?, updated_at = NOW()
            WHERE CR_ID = ?
        ");
        $stmt->bind_param("ssi", $phone, $sec_code, $doctor_info['CR_ID']);
        $stmt->execute();
        
        // Update doctors table
        $stmt = $conn->prepare("
            UPDATE doctors 
            SET Speciality = ?
            WHERE DR_ID = ?
        ");
        $stmt->bind_param("si", $specialty, $doctor_id);
        $stmt->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "Doctor information updated successfully!";
        header("Location: doctor_dashboard.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error updating information: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Doctor Information</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-primary {
            background-color: #0056b3;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .read-only {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>Edit Doctor Information</h1>
        </div>
    </header>

    <div class="form-container">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label>Name:</label>
                <div class="read-only">
                    Dr. <?= htmlspecialchars($doctor_info['FName'] . ' ' . $doctor_info['LName']) ?>
                </div>
            </div>
            
            <div class="form-group">
                <label>Gender:</label>
                <div class="read-only">
                    <?= htmlspecialchars($doctor_info['Gender']) ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="specialty">Specialty:</label>
                <input type="text" id="specialty" name="specialty" 
                       value="<?= htmlspecialchars($doctor_info['Speciality']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?= htmlspecialchars($doctor_info['Phone']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="sec_code">Security Code:</label>
                <input type="text" id="sec_code" name="sec_code" 
                       value="<?= htmlspecialchars($doctor_info['Sec_Code']) ?>" required>
                <small>Used for verification purposes</small>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="../doctor_dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
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