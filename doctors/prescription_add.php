<?php
require_once '../includes/db_connect.php';
session_start();

// Verify doctor is logged in
if (!isset($_SESSION['doctor_ID'])) {
    header('Location: ../unauthorized.php');
    exit();
}

$doctor_id = $_SESSION['doctor_ID'];
$mdr_id = isset($_GET['mdr_id']) ? intval($_GET['mdr_id']) : 0;

// Get doctor info
$sql = "SELECT * FROM doctors, Creds WHERE doctors.CR_ID = ? AND doctors.CR_ID = Creds.CR_ID";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$doctor_info = $stmt->get_result()->fetch_assoc();
$dr_id = $doctor_info['DR_ID'];

// Get list of medical records for dropdown (if no MDR_ID provided)
$medical_records = [];
if (!$mdr_id) {
    try {
        $stmt = $conn->prepare("
            SELECT m.MDR_ID, CONCAT(c.FName, ' ', c.LName) AS patient_name, m.Diagnosis
            FROM md_records m
            JOIN encounters e ON m.EN_ID = e.EN_ID
            JOIN patients p ON e.PA_ID = p.PA_ID
            JOIN creds c ON p.CR_ID = c.CR_ID
            WHERE e.DR_ID = ?
            ORDER BY patient_name ASC
        ");
        $stmt->bind_param("i", $dr_id);
        $stmt->execute();
        $medical_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // If only one record exists, auto-select it
        if (count($medical_records) === 1) {
            $mdr_id = $medical_records[0]['MDR_ID'];
            header("Location: ?mdr_id=$mdr_id");
            exit();
        }
    } catch (Exception $e) {
        die("Error fetching medical records: " . $e->getMessage());
    }
}

// Verify medical record belongs to doctor (if MDR_ID provided)
if ($mdr_id) {
    try {
        $stmt = $conn->prepare("
            SELECT m.MDR_ID, CONCAT(c.FName, ' ', c.LName) AS patient_name, m.Diagnosis
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
            die("Invalid medical record or you don't have permission to access it.");
        }
    } catch (Exception $e) {
        die("Error verifying medical record: " . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mdr_id = $_POST['mdr_id'] ?? $mdr_id;
    $drug = $_POST['drug'] ?? '';
    $dosage = $_POST['dosage'] ?? '';
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO prescriptions (MDR_ID, Drug, Dosage)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss", $mdr_id, $drug, $dosage);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $_SESSION['success_message'] = "Prescription added successfully!";
            header("Location: prescriptions_view.php?mdr_id=$mdr_id");
            exit();
        }
    } catch (Exception $e) {
        $error_message = "Error adding prescription: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Prescription</title>
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
        .patient-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], select, textarea {
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
        .record-selector {
            margin-bottom: 20px;
            padding: 15px;
            background: #f0f8ff;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>Add New Prescription</h1>
        </div>
    </header>

    <div class="form-container">
        <?php if ($mdr_id && isset($record)): ?>
            <div class="patient-info">
                <h3>Prescribing for: <?= htmlspecialchars($record['patient_name']) ?></h3>
                <p>Diagnosis: <?= htmlspecialchars($record['Diagnosis']) ?></p>
                <p>Medical Record ID: <?= $mdr_id ?></p>
            </div>
        <?php elseif (!empty($medical_records)): ?>
            <div class="record-selector">
                <h3>Select Patient Medical Record</h3>
                <form method="get" action="">
                    <div class="form-group">
                        <label for="mdr_id">Patient Records:</label>
                        <select id="mdr_id" name="mdr_id" required onchange="this.form.submit()">
                            <option value="">-- Select a patient record --</option>
                            <?php foreach ($medical_records as $mr): ?>
                                <option value="<?= $mr['MDR_ID'] ?>">
                                    <?= htmlspecialchars($mr['patient_name']) ?> - 
                                    <?= htmlspecialchars($mr['Diagnosis']) ?> (ID: <?= $mr['MDR_ID'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <?php exit(); // Stop further rendering until record is selected ?>
        <?php else: ?>
            <div class="alert alert-info">No medical records found for your patients.</div>
            <a href="doctor_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <?php exit(); ?>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="mdr_id" value="<?= $mdr_id ?>">
            
            <div class="form-group">
                <label for="drug">Drug Name:</label>
                <input type="text" id="drug" name="drug" required>
            </div>
            
            <div class="form-group">
                <label for="dosage">Dosage:</label>
                <input type="text" id="dosage" name="dosage" required placeholder="e.g., 500mg twice daily">
            </div>
            
            <div class="form-group">
                <label for="instructions">Special Instructions:</label>
                <textarea id="instructions" name="instructions" rows="4"></textarea>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Save Prescription</button>
                <a href="<?= $mdr_id ? "prescriptions_view.php?mdr_id=$mdr_id" : "doctor_dashboard.php" ?>" 
                   class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <footer>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0;">
            <div style="display: flex; align-items: center;">
                <img src="../images/jadara.png" alt="IhIS Logo" style="height: 80px;border-radius: 50%;margin-right: 10px;">
                <span>jadara-uni<br>database application design</span>
            </div>
            <div>&copy; <?= date('Y') ?> IHIS - home page</div>
            <div style="text-align: right;">
                <div>Developed by [يامن محمد رفعت تحسين]</div>
                <div>[حسن محمد حسن فوالجه]</div>
            </div>
        </div>
    </footer>
</body>
</html>