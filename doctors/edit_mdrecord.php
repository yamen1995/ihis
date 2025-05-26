<?php
require_once '../includes/db_connect.php';
session_start();
$doctor_id = $_SESSION['doctor_ID'];
$sql = "SELECT * FROM doctors,Creds WHERE doctors.CR_ID=$doctor_id and doctors.CR_ID=Creds.CR_ID";
    $result=$conn->query($sql);
    $doctor_info = $result->fetch_array(); 
   $dr_id=$doctor_info['DR_ID'];
$mdr_id = isset($_GET['mdr_id']) ? intval($_GET['mdr_id']) : 0;

// Fetch medical record with verification
try {
    $stmt = $conn->prepare("
        SELECT m.*, e.DR_ID, e.Date, e.Time,
               CONCAT(c.FName, ' ', c.LName) AS patient_name
        FROM md_records m
        JOIN encounters e ON m.EN_ID = e.EN_ID
        JOIN patients p ON e.PA_ID = p.PA_ID
        JOIN creds c ON p.CR_ID = c.CR_ID
        WHERE m.MDR_ID = ? AND e.DR_ID = ?
    ");
    $stmt->bind_param("ii", $mdr_id, $dr_id);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    
    if (!$record) {
        die("Medical record not found or you don't have permission to access it.");
    }
} catch (Exception $e) {
    die("Error fetching medical record: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $summary = $_POST['summary'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    
    try {
        $stmt = $conn->prepare("
            UPDATE md_records 
            SET Summery = ?, Diagnosis = ?
            WHERE MDR_ID = ?
        ");
        $stmt->bind_param("ssi", $summary, $diagnosis, $mdr_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $_SESSION['success_message'] = "Medical record updated successfully!";
            header("Location: doctor_appointments.php");
            exit();
        }
    } catch (Exception $e) {
        $error_message = "Error updating medical record: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Medical Record</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .patient-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        textarea {
            width: 100%;
            min-height: 150px;
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
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>Edit Medical Record</h1>
        </div>
    </header>

    <div class="form-container">
        <div class="patient-info">
            <h3>Patient Information</h3>
            <div class="info-row">
                <span class="info-label">Patient:</span>
                <span><?= htmlspecialchars($record['patient_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Appointment Date:</span>
                <span><?= date('M j, Y', strtotime($record['Date'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Appointment Time:</span>
                <span><?= date('h:i A', strtotime($record['Time'])) ?></span>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="summary">Clinical Summary:</label>
                <textarea id="summary" name="summary" required><?= htmlspecialchars($record['Summery']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="diagnosis">Diagnosis:</label>
                <textarea id="diagnosis" name="diagnosis" required><?= htmlspecialchars($record['Diagnosis']) ?></textarea>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Update Record</button>
                <a href="doctor_appointments.php" class="btn btn-secondary">Cancel</a>
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