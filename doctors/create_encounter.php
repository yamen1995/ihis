<?php
session_start();
require_once '../includes/db_connect.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_ID'])) {
    header("Location: /login.php");
    exit();
}

$doctor_id = $_SESSION['doctor_ID'];

// Get doctor information
$doctor_info = [];
try {
    $stmt = $conn->prepare("SELECT doctors.DR_ID, Creds.FName, Creds.LName 
                           FROM doctors 
                           JOIN Creds ON doctors.CR_ID = Creds.CR_ID 
                           WHERE doctors.CR_ID = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor_info = $stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    die("Error fetching doctor information: " . $e->getMessage());
}

$dr_id = $doctor_info['DR_ID'];

// Get all patients this doctor has had encounters with
$patients = [];
try {
    $stmt = $conn->prepare("SELECT DISTINCT p.PA_ID, c.FName, c.LName, c.DOB, c.Phone
                           FROM patients p
                           JOIN Creds c ON p.CR_ID = c.CR_ID
                           JOIN encounters e ON p.PA_ID = e.PA_ID
                           WHERE e.DR_ID = ?
                           ORDER BY c.LName, c.FName");
    $stmt->bind_param("i", $dr_id);
    $stmt->execute();
    $patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Error fetching patients: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pa_id = $_POST['patient_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    
    // Validate inputs
    if (empty($pa_id) || empty($date) || empty($time)) {
        $error = "All fields are required";
    } else {
        try {
            // Check if the selected time slot is available
            $stmt = $conn->prepare("SELECT EN_ID FROM encounters 
                                  WHERE DR_ID = ? AND Date = ? AND Time = ?");
            $stmt->bind_param("iss", $dr_id, $date, $time);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $error = "This time slot is already booked. Please choose another time.";
            } else {
                // Insert new encounter
                $stmt = $conn->prepare("INSERT INTO encounters (DR_ID, PA_ID, Date, Time) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $dr_id, $pa_id, $date, $time);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $success = "New encounter scheduled successfully!";
                    // Clear POST data to show empty form
                    $_POST = [];
                } else {
                    $error = "Failed to schedule encounter. Please try again.";
                }
            }
        } catch (Exception $e) {
            $error = "Error scheduling encounter: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedule New Encounter</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/doctor.css">
    <style>
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        select, input[type="date"], input[type="time"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn {
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .patient-list {
            margin-top: 30px;
        }
        
        .patient-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
            background: #f9f9f9;
        }
        
        .patient-name {
            font-weight: bold;
            font-size: 18px;
        }
    </style>
</head>
<body>
     <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>Doctor Dashboard</h1>
        </div>
    </header>
    
    <div class="container">
        <h1>Schedule New appointment</h1>
        <p>Dr. <?= htmlspecialchars($doctor_info['FName'] . ' ' . $doctor_info['LName']) ?></p>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="patient_id">Select Patient:</label>
                <select id="patient_id" name="patient_id" required>
                    <option value="">-- Select Patient --</option>
                    <?php foreach ($patients as $patient): ?>
                        <option value="<?= $patient['PA_ID'] ?>" 
                            <?= isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['PA_ID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($patient['LName'] . ', ' . $patient['FName']) ?> 
                            (DOB: <?= date('m/d/Y', strtotime($patient['DOB'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" 
                       value="<?= isset($_POST['date']) ? htmlspecialchars($_POST['date']) : '' ?>" 
                       min="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="time">Time:</label>
                <input type="time" id="time" name="time" 
                       value="<?= isset($_POST['time']) ? htmlspecialchars($_POST['time']) : '09:00' ?>" 
                       min="08:00" max="17:00" required>
                <small>Clinic hours: 8:00 AM to 5:00 PM</small>
            </div>
            
            <button type="submit" class="btn">Schedule Encounter</button>
        </form>
        
        <div class="patient-list">
            <h2>Your Patients</h2>
            
            <?php if (empty($patients)): ?>
                <p>You haven't had any encounters with patients yet.</p>
            <?php else: ?>
                <?php foreach ($patients as $patient): ?>
                    <div class="patient-card">
                        <div class="patient-name">
                            <?= htmlspecialchars($patient['LName'] . ', ' . $patient['FName']) ?>
                        </div>
                        <div>DOB: <?= date('m/d/Y', strtotime($patient['DOB'])) ?></div>
                        <div>Phone: <?= htmlspecialchars($patient['Phone']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
     <footer>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0;">
            <div style="display: flex; align-items: center;">
                <img src="../images/jadara.png" alt="IhIS Logo" style="height: 80px;border-radius: 50%;margin-right: 10px;">
                <span>jadara-uni<br>database application design</span>
            </div>
            <div>
                &copy; <?= date('Y') ?> IHIS - home page
            </div>
            <div style="text-align: right;">
                <div>Developed by [يامن محمد رفعت تحسين]</div>
                <div>[حسن محمد حسن فوالجه]</div>
            </div>
        </div>
    </footer>
    
    <script>
        // Set default date to today if not set
        document.addEventListener('DOMContentLoaded', function() {
            if (!document.getElementById('date').value) {
                document.getElementById('date').valueAsDate = new Date();
            }
        });
    </script>
</body>
</html>