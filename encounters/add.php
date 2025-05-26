<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Fetch doctors and patients
$doctors = [];
$patients = [];

try {
    $doctors = $conn->query("
        SELECT d.DR_ID, c.FName, c.LName, d.Speciality 
        FROM doctors d
        JOIN creds c ON d.CR_ID = c.CR_ID
        ORDER BY c.LName, c.FName
    ")->fetch_all(MYSQLI_ASSOC);

    $patients = $conn->query("
        SELECT p.PA_ID, c.FName, c.LName 
        FROM patients p
        JOIN creds c ON p.CR_ID = c.CR_ID
        WHERE p.IS_Active = 1
        ORDER BY c.LName, c.FName
    ")->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    die("Error fetching data: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'DR_ID' => $_POST['doctor_id'],
        'PA_ID' => $_POST['patient_id'],
        'Date' => $_POST['date'],
        'Time' => $_POST['time']
    ];

    try {
        $stmt = $conn->prepare("
            INSERT INTO encounters (DR_ID, PA_ID, Date, Time)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiss", $data['DR_ID'], $data['PA_ID'], $data['Date'], $data['Time']);
        $stmt->execute();
        
        header("Location: view.php?success=Appointment created successfully");
        exit();
    } catch (Exception $e) {
        $error = "Error creating appointment: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Appointment</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
    <style>
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group select, 
        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>New Appointment</h1>
        </div>
    </header>

    <div class="content-container">
        <h2>Schedule New Appointment</h2>
        <a href="view.php" class="back-btn">&larr; Back to Appointments</a>
        
        <?php if(isset($error)): ?>
            <div class="error-message"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" class="patient-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Patient *</label>
                    <select name="patient_id" required>
                        <option value="">-- Select Patient --</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= $patient['PA_ID'] ?>">
                                <?= htmlspecialchars($patient['LName'] . ', ' . $patient['FName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Doctor *</label>
                    <select name="doctor_id" required>
                        <option value="">-- Select Doctor --</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?= $doctor['DR_ID'] ?>" data-specialty="<?= htmlspecialchars($doctor['Speciality']) ?>">
                                Dr. <?= htmlspecialchars($doctor['LName'] . ', ' . $doctor['FName']) ?> (<?= htmlspecialchars($doctor['Speciality']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="date" required 
                           min="<?= date('Y-m-d') ?>" 
                           value="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label>Time *</label>
                    <input type="time" name="time" required 
                           min="08:00" max="18:00" 
                           value="09:00">
                </div>
            </div>
            
            <button type="submit" class="submit-btn">Schedule Appointment</button>
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

    <script>
        // Enhance time selection
        document.querySelector('input[name="time"]').addEventListener('change', function() {
            const time = this.value;
            const hours = parseInt(time.split(':')[0]);
            
            if (hours < 8 || hours > 17) {
                alert('Clinic hours are 8:00 AM to 5:00 PM');
                this.value = '09:00';
            }
        });
    </script>
</body>
</html>