<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Default date range (last 30 days)
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch patient statistics
$stats = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) AS total_patients,
            SUM(CASE WHEN p.IS_Active = 1 THEN 1 ELSE 0 END) AS active_patients,
            SUM(CASE WHEN p.IS_Active = 0 THEN 1 ELSE 0 END) AS inactive_patients,
            COUNT(DISTINCT e.PA_ID) AS patients_with_appointments,
            AVG(DATEDIFF(NOW(), c.DOB)) AS avg_age
        FROM patients p
        JOIN creds c ON p.CR_ID = c.CR_ID
        LEFT JOIN encounters e ON p.PA_ID = e.PA_ID
        WHERE c.DOA BETWEEN ? AND ?
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    die("Error fetching patient statistics: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Statistics Report</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
    <style>
        .report-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #0056b3;
            margin: 10px 0;
        }
        .stat-label {
            color: #6c757d;
            font-size: 1rem;
        }
        .date-filter {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>Patient Statistics Report</h1>
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
        
        <div class="report-container">
            <h2>Patient Statistics (<?= date('M j, Y', strtotime($start_date)) ?> - <?= date('M j, Y', strtotime($end_date)) ?>)</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['total_patients'] ?? 0 ?></div>
                    <div class="stat-label">Total Patients</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['active_patients'] ?? 0 ?></div>
                    <div class="stat-label">Active Patients</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['inactive_patients'] ?? 0 ?></div>
                    <div class="stat-label">Inactive Patients</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['patients_with_appointments'] ?? 0 ?></div>
                    <div class="stat-label">Patients With Appointments</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?= round($stats['avg_age']/365, 1) ?? 0 ?></div>
                    <div class="stat-label">Average Age (Years)</div>
                </div>
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