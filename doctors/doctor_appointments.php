<?php
require_once '../includes/db_connect.php';
session_start();
$doctor_id = $_SESSION['doctor_ID'];
 $sql = "SELECT * FROM doctors,Creds WHERE doctors.CR_ID=$doctor_id and doctors.CR_ID=Creds.CR_ID";
    $result=$conn->query($sql);
    $doctor_info = $result->fetch_array(); 
   $dr_id=$doctor_info['DR_ID'];
$filter = $_GET['type'] ?? 'upcoming'; // upcoming, past, all
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

// Build query based on filter
$sql_where = "";
switch ($filter) {
    case 'past':
        $sql_where = "AND (e.Date < '$current_date' OR (e.Date = '$current_date' AND e.Time < '$current_time'))";
        break;
    case 'upcoming':
        $sql_where = "AND (e.Date > '$current_date' OR (e.Date = '$current_date' AND e.Time > '$current_time'))";
        break;
    case 'all':
    default:
        $sql_where = "";
}

try {
    $stmt = $conn->prepare("
        SELECT e.*, 
               CONCAT(c.FName, ' ', c.LName) AS patient_name,
               c.Phone,
               m.MDR_ID,
               m.Summery,
               m.Diagnosis
        FROM encounters e
        JOIN patients p ON e.PA_ID = p.PA_ID
        JOIN creds c ON p.CR_ID = c.CR_ID
        LEFT JOIN md_records m ON e.EN_ID = m.EN_ID
        WHERE e.DR_ID = ?
        $sql_where
        ORDER BY e.Date DESC, e.Time DESC
    ");
    $stmt->bind_param("i", $dr_id);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Error fetching appointments: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Appointments</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-tab {
            padding: 8px 15px;
            border-radius: 5px;
            background: #f0f0f0;
            text-decoration: none;
            color: #333;
        }
        .filter-tab.active {
            background: #0056b3;
            color: white;
        }
        .appointments-table {
            width: 100%;
            border-collapse: collapse;
        }
        .appointments-table th, 
        .appointments-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .appointments-table th {
            background-color: #f8f9fa;
        }
        .action-btn {
            padding: 5px 10px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .btn-primary {
            background-color: #0056b3;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .no-records {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .medical-summary {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>My Appointments</h1>
        </div>
    </header>

    <div class="container">
        <div class="filter-tabs">
            <a href="?type=upcoming" class="filter-tab <?= $filter === 'upcoming' ? 'active' : '' ?>">Upcoming</a>
            <a href="?type=past" class="filter-tab <?= $filter === 'past' ? 'active' : '' ?>">Past</a>
            <a href="?type=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">All</a>
        </div>

        <?php if (empty($appointments)): ?>
            <div class="no-records">
                <p>No appointments found</p>
            </div>
        <?php else: ?>
            <table class="appointments-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>Contact</th>
                        <th>Medical Record</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($appt['Date'])) ?></td>
                            <td><?= date('h:i A', strtotime($appt['Time'])) ?></td>
                            <td><?= htmlspecialchars($appt['patient_name']) ?></td>
                            <td><?= htmlspecialchars($appt['Phone']) ?></td>
                            <td class="medical-summary">
                                <?php if ($appt['MDR_ID']): ?>
                                    <strong>Diagnosis:</strong> <?= htmlspecialchars(substr($appt['Diagnosis'], 0, 50)) ?>...
                                <?php else: ?>
                                    <em>No record created</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $is_past = strtotime($appt['Date'] . ' ' . $appt['Time']) < time();
                                ?>
                                <?php if ($is_past): ?>
                                    <?php if ($appt['MDR_ID']): ?>
                                        <a href="edit_mdrecord.php?mdr_id=<?= $appt['MDR_ID'] ?>" 
                                           class="action-btn btn-secondary">Edit Record</a>
                                    <?php else: ?>
                                        <a href="mdreports_add.php?encounter_id=<?= $appt['EN_ID'] ?>" 
                                           class="action-btn btn-primary">Create Record</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="action-btn" style="background:#ccc;cursor:not-allowed">Future</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
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