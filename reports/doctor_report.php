<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Default date range (last 30 days)
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch doctor statistics
$doctors = [];
try {
    $doctors = $conn->query("
        SELECT 
            d.DR_ID,
            CONCAT(c.FName, ' ', c.LName) AS doctor_name,
            d.Speciality,
            COUNT(e.EN_ID) AS appointment_count,
            COUNT(DISTINCT e.PA_ID) AS unique_patients
        FROM doctors d
        JOIN creds c ON d.CR_ID = c.CR_ID
        LEFT JOIN encounters e ON d.DR_ID = e.DR_ID
            AND e.Date BETWEEN '$start_date' AND '$end_date'
        GROUP BY d.DR_ID
        ORDER BY appointment_count DESC
    ")->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Error fetching doctor statistics: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Statistics Report</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
    <style>
        .doctor-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .doctor-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .doctor-stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0056b3;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>Doctor Performance Report</h1>
        </div>
    </header>

    <div class="content-container">
        <div class="date-filter">
            <form method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="action-btn view">Generate Report</button>
                    </div>
                </div>
            </form>
        </div>
        
        <h2>Doctor Statistics (<?= date('M j, Y', strtotime($start_date)) ?> - <?= date('M j, Y', strtotime($end_date)) ?>)</h2>
        
        <div class="doctor-list">
            <?php foreach ($doctors as $doctor): ?>
            <div class="doctor-card">
                <div class="doctor-header">
                    <h3>Dr. <?= htmlspecialchars($doctor['doctor_name']) ?></h3>
                    <span class="specialty"><?= htmlspecialchars($doctor['Speciality']) ?></span>
                </div>
                
                <div class="doctor-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?= $doctor['appointment_count'] ?></div>
                        <div class="stat-label">Appointments</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-number"><?= $doctor['unique_patients'] ?></div>
                        <div class="stat-label">Unique Patients</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-number">
                            <?= $doctor['appointment_count'] > 0 ? 
                                round($doctor['unique_patients']/$doctor['appointment_count']*100, 1) . '%' : 'N/A' ?>
                        </div>
                        <div class="stat-label">Patient Retention</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
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