<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/IHIS/includes/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/IHIS/includes/auth_check.php';




$encounter_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_message = '';
$success_message = '';

// Fetch encounter details
try {
    $stmt = $conn->prepare("
        SELECT e.*, 
               CONCAT(dc.FName, ' ', dc.LName) AS doctor_name,
               CONCAT(pc.FName, ' ', pc.LName) AS patient_name,
               d.Speciality
        FROM encounters e
        JOIN doctors d ON e.DR_ID = d.DR_ID
        JOIN creds dc ON d.CR_ID = dc.CR_ID
        JOIN patients p ON e.PA_ID = p.PA_ID
        JOIN creds pc ON p.CR_ID = pc.CR_ID
        WHERE e.EN_ID = ?
    ");
    $stmt->bind_param("i", $encounter_id);
    $stmt->execute();
    $encounter = $stmt->get_result()->fetch_assoc();
    
    if (!$encounter) {
        die("Appointment not found or you don't have permission to access it.");
    }
} catch (Exception $e) {
    die("Error fetching appointment: " . $e->getMessage());
}

// Fetch available doctors
$doctors = [];
try {
    $stmt = $conn->prepare("
        SELECT d.DR_ID, CONCAT(c.FName, ' ', c.LName) AS name, d.Speciality
        FROM doctors d
        JOIN creds c ON d.CR_ID = c.CR_ID
        ORDER BY c.LName, c.FName
    ");
    $stmt->execute();
    $doctors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error_message = "Error fetching doctors: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_POST['doctor_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    
    try {
        $stmt = $conn->prepare("
            UPDATE encounters 
            SET DR_ID = ?, Date = ?, Time = ?
            WHERE EN_ID = ?
        ");
        $stmt->bind_param("issi", $doctor_id, $date, $time, $encounter_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $_SESSION['success_message'] = "Appointment updated successfully!";
            header("Location: view.php");
            exit();
        } else {
            $error_message = "No changes made to the appointment.";
        }
    } catch (Exception $e) {
        $error_message = "Error updating appointment: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Appointment</title>
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
        .appointment-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
            color: #555;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, input[type="date"], input[type="time"] {
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
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>Edit Appointment</h1>
        </div>
    </header>

    <div class="form-container">
        <div class="appointment-info">
            <h3>Appointment Details</h3>
            <div class="info-row">
                <span class="info-label">Patient:</span>
                <span><?= htmlspecialchars($encounter['patient_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Current Doctor:</span>
                <span>Dr. <?= htmlspecialchars($encounter['doctor_name']) ?> (<?= htmlspecialchars($encounter['Speciality']) ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label">Original Date:</span>
                <span><?= date('M j, Y', strtotime($encounter['Date'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Original Time:</span>
                <span><?= date('h:i A', strtotime($encounter['Time'])) ?></span>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="doctor_id">Select Doctor:</label>
                <select id="doctor_id" name="doctor_id" required>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?= $doctor['DR_ID'] ?>" <?= $doctor['DR_ID'] == $encounter['DR_ID'] ? 'selected' : '' ?>>
                            Dr. <?= htmlspecialchars($doctor['name']) ?> (<?= htmlspecialchars($doctor['Speciality']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="date">Appointment Date:</label>
                <input type="date" id="date" name="date" 
                       value="<?= htmlspecialchars($encounter['Date']) ?>" required
                       min="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="form-group">
                <label for="time">Appointment Time:</label>
                <input type="time" id="time" name="time" 
                       value="<?= htmlspecialchars(substr($encounter['Time'], 0, 5)) ?>" required
                       min="08:00" max="18:00">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Update Appointment</button>
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