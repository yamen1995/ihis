<?php
require_once '../includes/db_connect.php';
session_start();
$patient_id = $_SESSION['user_id'];

// Fetch patient information
$doctors = [];
$appointments = [];
$prescriptions = [];


    // Get basic patient info
    $stmt = $conn->query("
        SELECT p.*, c.*
        FROM patients p
        JOIN creds c ON p.CR_ID = c.CR_ID
        WHERE p.CR_ID = '$patient_id'
    ");
    
    $patient_info = $stmt->fetch_array();
    $pa_id=$patient_info['PA_ID'];

    // Get doctors who treated this patient
   $stmt = $conn->prepare("
        SELECT DISTINCT d.DR_ID, CONCAT(c.FName, ' ', c.LName) AS doctor_name, 
               c.Phone, d.Speciality
        FROM encounters e
        JOIN doctors d ON e.DR_ID = d.DR_ID
        JOIN creds c ON d.CR_ID = c.CR_ID
        WHERE e.PA_ID = ?
        ORDER BY e.Date DESC
    ");
    $stmt->bind_param("i", $pa_id);
    $stmt->execute();
    $doctors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get appointments (past and upcoming)
     $stmt = $conn->prepare("
        SELECT e.*, CONCAT(c.FName, ' ', c.LName) AS doctor_name, d.Speciality
        FROM encounters e
        JOIN doctors d ON e.DR_ID = d.DR_ID
        JOIN creds c ON d.CR_ID = c.CR_ID
        WHERE e.PA_ID = ?
        ORDER BY e.Date DESC, e.Time DESC
    ");
    $stmt->bind_param("i", $pa_id);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


    // Get prescriptions
     $stmt = $conn->prepare("
        SELECT pr.*,e.Date, CONCAT(c.FName, ' ', c.LName) AS doctor_name
        FROM prescriptions pr
        JOIN md_records m ON pr.MDR_ID = m.MDR_ID
        JOIN encounters e ON m.EN_ID = e.EN_ID
        JOIN doctors d ON e.DR_ID = d.DR_ID
        JOIN creds c ON d.CR_ID = c.CR_ID
        WHERE e.PA_ID = ?
        ORDER BY e.Date DESC
    ");
    $stmt->bind_param("i", $pa_id);
    $stmt->execute();
    $prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
    <style>
        .dashboard-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
        }
        .welcome-message {
            margin-bottom: 30px;
        }
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
        }
        .dashboard-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-title {
            font-size: 1.3rem;
            color: #0056b3;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
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
        .doctor-item, .appointment-item, .prescription-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .doctor-item:last-child, 
        .appointment-item:last-child,
        .prescription-item:last-child {
            border-bottom: none;
        }
        .no-records {
            color: #666;
            font-style: italic;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-left: 10px;
        }
        .status-upcoming {
            background-color: #d4edda;
            color: #155724;
        }
        .status-past {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>Patient Dashboard</h1>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="welcome-message">
            <h2>Welcome, <?= htmlspecialchars($patient_info['FName'] . ' ' . $patient_info['LName']) ?></h2>
            <p>Here's your health information at a glance</p>
        </div>

        <div class="dashboard-cards">
            <!-- Patient Information Card -->
            
            <div class="dashboard-card">
                <h3 class="card-title">My Information</h3>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span><?= htmlspecialchars($patient_info['FName'] . ' ' . $patient_info['LName']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date of Birth:</span>
                    <span><?= date('M j, Y', strtotime($patient_info['DOB'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Gender:</span>
                    <span><?= htmlspecialchars($patient_info['Gender']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span><?= htmlspecialchars($patient_info['Phone']) ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Medical History:</span>
                    <span><?= !empty($patient_info['Med_History']) ? 
                        nl2br(htmlspecialchars($patient_info['Med_History'])) : 'None recorded' ?></span>
                </div>
            </div>

            <!-- My Doctors Card -->
            <div class="dashboard-card">
                <h3 class="card-title">My Doctors</h3>
                <?php if (!empty($doctors)): ?>
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="doctor-item">
                            <h4><?= htmlspecialchars($doctor['doctor_name']) ?></h4>
                            <div class="info-row">
                                <span class="info-label">Specialty:</span>
                                <span><?= htmlspecialchars($doctor['Speciality']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Contact:</span>
                                <span><?= htmlspecialchars($doctor['Phone']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-records">No doctors found in your records</p>
                <?php endif; ?>
            </div>
             
            
            <!-- My Appointments Card -->
            <div class="dashboard-card">
                <h3 class="card-title">My Appointments</h3>
                <?php if (!empty($appointments)): ?>
                    <?php foreach ($appointments as $appt): 
                        $is_past = strtotime($appt['Date'] . ' ' . $appt['Time']) < time();
                    ?>
                        <div class="appointment-item">
                            <div style="display: flex; justify-content: space-between;">
                                <h4>Dr. <?= htmlspecialchars($appt['doctor_name']) ?></h4>
                                <span class="status-badge <?= $is_past ? 'status-past' : 'status-upcoming' ?>">
                                    <?= $is_past ? 'Past' : 'Upcoming' ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Date:</span>
                                <span><?= date('M j, Y', strtotime($appt['Date'])) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Time:</span>
                                <span><?= date('h:i A', strtotime($appt['Time'])) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Specialty:</span>
                                <span><?= htmlspecialchars($appt['Speciality']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-records">No appointments found</p>
                <?php endif; ?>
            </div>

            <!-- My Prescriptions Card -->
            <div class="dashboard-card">
                <h3 class="card-title">My Prescriptions</h3>
                <?php if (!empty($prescriptions)): ?>
                    <?php foreach ($prescriptions as $rx): ?>
                        <div class="prescription-item">
                            <h4><?= htmlspecialchars($rx['Drug']) ?></h4>
                            <div class="info-row">
                                <span class="info-label">Prescribed by:</span>
                                <span>Dr. <?= htmlspecialchars($rx['doctor_name']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Dosage:</span>
                                <span><?= htmlspecialchars($rx['Dosage']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">medicine name:</span>
                                <span><?= htmlspecialchars($rx['Drug']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Prescribed on:</span>
                                <span><?= date('M j, Y', strtotime($rx['Date'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-records">No prescriptions found</p>
                <?php endif; ?>
            </div>
         </div>
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