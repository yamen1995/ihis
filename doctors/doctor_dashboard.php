<?php
session_start();
require_once '../includes/db_connect.php';

$doctor_id = $_SESSION['doctor_ID'];

// Get current appointment (matching current date and time)
$current_appointment = [];
try {
    $current_time = date('H:i:s');
    $current_date = date('Y-m-d');
   // Get doctor's information
    $sql = "SELECT * FROM doctors,Creds WHERE doctors.CR_ID=$doctor_id and doctors.CR_ID=Creds.CR_ID";
    $result=$conn->query($sql);
    $doctor_info = $result->fetch_array(); 
   $dr_id=$doctor_info['DR_ID'];
    $stmt = $conn->prepare("
        SELECT e.*, 
               p.PA_ID,
               CONCAT(c.FName, ' ', c.LName) AS patient_name,
               c.DOB,
               c.Phone,
               p.Med_History,
               p.IS_Active
        FROM encounters e
        JOIN patients p ON e.PA_ID = p.PA_ID
        JOIN creds c ON p.CR_ID = c.CR_ID
        WHERE e.DR_ID = ?
        AND e.Date = ?
        AND e.Time BETWEEN SUBTIME(?, '01:00:00') AND ADDTIME(?, '01:00:00')
        ORDER BY ABS(TIMEDIFF(e.Time, ?))
        LIMIT 1
    ");
    $stmt->bind_param("issss", $dr_id, $current_date, $current_time, $current_time, $current_time);
    $stmt->execute();
    $current_appointment = $stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    die("Error fetching current appointment: " . $e->getMessage());
}



// Count upcoming appointments
$upcoming_count = 0;
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS count
        FROM encounters
        WHERE DR_ID = ? 
        AND (Date > CURDATE() OR (Date = CURDATE() AND Time > CURTIME()))
    ");
    $stmt->bind_param("i", $dr_id);
    $stmt->execute();
    $upcoming_count = $stmt->get_result()->fetch_assoc()['count'];
} catch (Exception $e) {
    die("Error counting upcoming appointments: " . $e->getMessage());
}

// Count past appointments
$past_count = 0;
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS count
        FROM encounters
        WHERE DR_ID = ? 
        AND (Date < CURDATE() OR (Date = CURDATE() AND Time < CURTIME()))
    ");
    $stmt->bind_param("i", $dr_id);
    $stmt->execute();
    $past_count = $stmt->get_result()->fetch_assoc()['count'];
} catch (Exception $e) {
    die("Error counting past appointments: " . $e->getMessage());
}

// Count active patients
$active_patients = 0;
try {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT e.PA_ID) AS count
        FROM encounters e
        JOIN patients p ON e.PA_ID = p.PA_ID
        WHERE e.DR_ID = ? AND p.IS_Active = 1
    ");
    $stmt->bind_param("i", $dr_id);
    $stmt->execute();
    $active_patients = $stmt->get_result()->fetch_assoc()['count'];
} catch (Exception $e) {
    die("Error counting active patients: " . $e->getMessage());
}
$inactive_patients = 0;
try {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT e.PA_ID) AS count
        FROM encounters e
        JOIN patients p ON e.PA_ID = p.PA_ID
        WHERE e.DR_ID = ? AND p.IS_Active = 0
    ");
    $stmt->bind_param("i", $dr_id);
    $stmt->execute();
    $inactive_patients = $stmt->get_result()->fetch_assoc()['count'];
} catch (Exception $e) {
    die("Error counting active patients: " . $e->getMessage());
}
?>

// ... [previous PHP code remains the same] ...
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
    <link rel="stylesheet" href="../css/doctor.css">
    <style>
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        .dashboard-card {
            height: 100%;
            display: flex;
            flex-direction: column;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card-content {
            flex: 1;
            padding: 15px;
        }
        
        @media (max-width: 1000px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
        }
        
        /* Additional styling for better visual hierarchy */
        .card-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            margin: 0;
            font-size: 1.2rem;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background-color: #007bff;
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
            <h1>Doctor Dashboard</h1>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="welcome-message">
            <h2>Welcome, Dr. <?= htmlspecialchars($doctor_info['FName'] . ' ' . $doctor_info['LName']) ?></h2>
            <p>Specialty: <?= htmlspecialchars($doctor_info['Speciality']) ?></p>
        </div>

        <div class="dashboard-cards">
            <!-- Row 1 -->
            <div class="dashboard-card">
                <!-- Current Appointment Card Content -->
                <div class="card-header">
                    <h3 class="card-title">Current Appointment</h3>
                    <div class="card-actions">
                        <?php if ($current_appointment): ?>
                            <a href="../reports/mdreports_add.php?encounter_id=<?= $current_appointment['EN_ID'] ?>" 
                               class="action-btn btn-primary">Create Report</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-content">
                    <?php if ($current_appointment): ?>
                        <div class="patient-info">
                            <div class="info-row">
                                <span class="info-label">Patient:</span>
                                <span><?= htmlspecialchars($current_appointment['patient_name']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Date of Birth:</span>
                                <span><?= date('M j, Y', strtotime($current_appointment['DOB'])) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Phone:</span>
                                <span><?= htmlspecialchars($current_appointment['Phone']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Appointment Time:</span>
                                <span><?= date('h:i A', strtotime($current_appointment['Time'])) ?></span>
                            </div>
                        </div>

                        <div class="medical-history">
                            <h4>Medical History</h4>
                            <p><?= !empty($current_appointment['Med_History']) ? 
                                nl2br(htmlspecialchars($current_appointment['Med_History'])) : 'No medical history recorded' ?></p>
                        </div>
                    <?php else: ?>
                        <p>No current appointment scheduled at this time.</p>
                    <?php endif; ?>
                </div>
            </div>
           
            <div class="dashboard-card">
                <!-- Doctor Information Card Content -->
                <div class="card-header">
                    <h3 class="card-title">My Information</h3>
                    <div class="card-actions">
                        <a href="doctor_edit.php?id=<?= $doctor_id ?>" class="action-btn btn-secondary">Edit</a>
                    </div>
                </div>
                <div class="card-content">
                    <div class="patient-info">
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <span>Dr. <?= htmlspecialchars($doctor_info['FName'] . ' ' . $doctor_info['LName']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Specialty:</span>
                            <span><?= htmlspecialchars($doctor_info['Speciality']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Gender:</span>
                            <span><?= htmlspecialchars($doctor_info['Gender'] ?? 'Not provided') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span><?= htmlspecialchars($doctor_info['Phone'] ?? 'Not provided') ?></span>
                        </div>
                    </div>

                    <div class="quick-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?= $upcoming_count ?></div>
                            <div class="stat-label">Upcoming</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= $past_count ?></div>
                            <div class="stat-label">Past</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= $active_patients ?></div>
                            <div class="stat-label">Active Patients</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2 -->
            <div class="dashboard-card">
                <!-- Appointments Card Content -->
                <div class="card-header">
                    <h3 class="card-title">Appointments</h3>
                </div>
                <div class="card-content">
                    <div class="quick-stats">
                        <a href="doctor_appointments.php?type=upcoming" class="stat-item">
                            <div class="stat-number"><?= $upcoming_count ?></div>
                            <div class="stat-label">Upcoming</div>
                        </a>
                        <a href="doctor_appointments.php?type=past" class="stat-item">
                            <div class="stat-number"><?= $past_count ?></div>
                            <div class="stat-label">Past</div>
                        </a>
                        <a href="doctor_appointments.php?type=all" class="stat-item">
                            <div class="stat-number"><?= $upcoming_count + $past_count ?></div>
                            <div class="stat-label">All</div>
                        </a>
                    </div>

                    <div class="card-actions" style="margin-top: 20px; justify-content: center;">
                        <a href="create_encounter.php" class="action-btn btn-primary">Schedule New</a>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <!-- Patients Card Content -->
                <div class="card-header">
                    <h3 class="card-title">My Patients</h3>
                </div>
                <div class="card-content">
                    <div class="quick-stats">
                        <a href="doctor_patients.php?status=active" class="stat-item">
                            <div class="stat-number"><?= $active_patients ?></div>
                            <div class="stat-label">Active</div>
                        </a>
                        <a href="doctor_patients.php?status=inactive" class="stat-item">
                            <div class="stat-number"><?= $inactive_patients ?></div>
                            <div class="stat-label">Inactive</div>
                        </a>
                        <a href="doctor_patients.php?status=all" class="stat-item">
                            <div class="stat-number"><?= $active_patients + $inactive_patients ?></div>
                            <div class="stat-label">All</div>
                        </a>
                    </div>

                    <div style="margin-top: 20px;">
                        <h4>Recent Patients</h4>
                        <p>View your patient roster and medical histories</p>
                    </div>
                </div>
            </div>

            <!-- Row 3 -->
            <div class="dashboard-card">
                <!-- Prescriptions Card Content -->
                <div class="card-header">
                    <h3 class="card-title">Prescriptions</h3>
                    <div class="card-actions">
                        <a href="prescription_add.php" class="action-btn btn-primary">New Prescription</a>
                    </div>
                </div>
                <div class="card-content">
                    <div class="prescription-list">
                        <h4>Recent Prescriptions</h4>
                        <p>Sample prescription items would appear here</p>
                        
                        <!-- Example prescription items -->
                        <div class="prescription-item">
                            <span>Amoxicillin 500mg</span>
                            <span>Patient: John Doe</span>
                            <span>05/15/2023</span>
                        </div>
                        <div class="prescription-item">
                            <span>Lisinopril 10mg</span>
                            <span>Patient: Jane Smith</span>
                            <span>05/10/2023</span>
                        </div>
                    </div>

                    <div class="card-actions" style="margin-top: 20px; justify-content: center;">
                        <a href="prescriptions_view.php" class="action-btn btn-secondary">View All</a>
                    </div>
                </div>
            </div>
            
            <!-- Empty card to maintain grid layout if odd number of cards -->
            <div class="dashboard-card" style="visibility: hidden;"></div>
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
</body>
</html>?>