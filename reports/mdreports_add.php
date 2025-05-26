<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Verify the user is a doctor
if ($_SESSION['user_type'] !== 'doctor') {
    header('Location: ../unauthorized.php');
    exit();
}

$doctor_id = $_SESSION['user_id'];
$encounter_id = isset($_GET['encounter_id']) ? intval($_GET['encounter_id']) : 0;

// Fetch encounter details to verify ownership
$encounter = [];
$patient_info = [];
try {
    $stmt = $conn->prepare("
        SELECT e.*, 
               CONCAT(c.FName, ' ', c.LName) AS patient_name,
               c.DOB,
               c.Gender,
               p.PA_ID
        FROM encounters e
        JOIN patients p ON e.PA_ID = p.PA_ID
        JOIN creds c ON p.CR_ID = c.CR_ID
        WHERE e.EN_ID = ? AND e.DR_ID = ?
    ");
    $stmt->bind_param("ii", $encounter_id, $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die("Invalid appointment or you don't have permission to access it.");
    }
    
    $encounter = $result->fetch_assoc();
    $patient_info = [
        'name' => $encounter['patient_name'],
        'dob' => $encounter['DOB'],
        'gender' => $encounter['Gender'],
        'pa_id' => $encounter['PA_ID']
    ];
} catch (Exception $e) {
    die("Error fetching appointment details: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $summary = $_POST['summary'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO md_records (EN_ID, Summary, Diagnosis, Created_At)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iss", $encounter_id, $summary, $diagnosis);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $_SESSION['success_message'] = "Medical report created successfully!";
            header("Location: doctor_dashboard.php");
            exit();
        }
    } catch (Exception $e) {
        $error_message = "Error creating medical report: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Medical Report</title>
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
            <h1>Create Medical Report</h1>
        </div>
    </header>

    <div class="form-container">
        <div class="patient-info">
            <h3>Patient Information</h3>
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span><?= htmlspecialchars($patient_info['name']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Date of Birth:</span>
                <span><?= date('M j, Y', strtotime($patient_info['dob'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Gender:</span>
                <span><?= htmlspecialchars($patient_info['gender']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Appointment Date:</span>
                <span><?= date('M j, Y', strtotime($encounter['Date'])) ?></span>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="summary">Clinical Summary:</label>
                <textarea id="summary" name="summary" required placeholder="Enter clinical summary..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="diagnosis">Diagnosis:</label>
                <textarea id="diagnosis" name="diagnosis" required placeholder="Enter diagnosis..."></textarea>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Save Report</button>
                <a href="doctor_dashboard.php" class="btn btn-secondary">Cancel</a>
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