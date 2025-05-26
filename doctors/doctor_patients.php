<?php
require_once '../includes/db_connect.php';

session_start();
$doctor_id = $_SESSION['doctor_ID'];
$filter = $_GET['status'] ?? 'active'; // active, inactive, all

// Build query based on filter
$sql_where = "";
switch ($filter) {
    case 'active':
        $sql_where = "AND p.IS_Active = 1";
        break;
    case 'inactive':
        $sql_where = "AND p.IS_Active = 0";
        break;
    case 'all':
    default:
        $sql_where = "";
}

try {
    $stmt = $conn->prepare("
        SELECT 
            p.PA_ID,
            p.Med_History,
            p.IS_Active,
            CONCAT(c.FName, ' ', c.LName) AS patient_name,
            c.DOB,
            c.Gender,
            c.Phone,
            COUNT(e.EN_ID) AS encounter_count,
            MAX(e.Date) AS last_visit
        FROM patients p
        JOIN creds c ON p.CR_ID = c.CR_ID
        LEFT JOIN encounters e ON p.PA_ID = e.PA_ID 
        WHERE e.DR_ID = ? AND 1=1
        $sql_where
        GROUP BY p.PA_ID
        ORDER BY last_visit DESC
    ");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Error fetching patients: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Patients</title>
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
        .patients-table {
            width: 100%;
            border-collapse: collapse;
        }
        .patients-table th, 
        .patients-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .patients-table th {
            background-color: #f8f9fa;
        }
        .status-active {
            color: green;
            font-weight: bold;
        }
        .status-inactive {
            color: #666;
        }
        .medical-history {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .no-records {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>My Patients</h1>
        </div>
    </header>

    <div class="container">
        <div class="filter-tabs">
            <a href="?status=active" class="filter-tab <?= $filter === 'active' ? 'active' : '' ?>">Active</a>
            <a href="?status=inactive" class="filter-tab <?= $filter === 'inactive' ? 'active' : '' ?>">Inactive</a>
            <a href="?status=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">All</a>
        </div>

        <?php if (empty($patients)): ?>
            <div class="no-records">
                <p>No patients found</p>
            </div>
        <?php else: ?>
            <table class="patients-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Contact</th>
                        <th>Medical History</th>
                        <th>Visits</th>
                        <th>Last Visit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $patient): 
                        $age = $patient['DOB'] ? date_diff(date_create($patient['DOB']), date_create('today'))->y : 'N/A';
                        $last_visit = $patient['last_visit'] ? date('M j, Y', strtotime($patient['last_visit'])) : 'Never';
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($patient['patient_name']) ?></td>
                            <td><?= htmlspecialchars($patient['Gender']) ?></td>
                            <td><?= $age ?></td>
                            <td><?= htmlspecialchars($patient['Phone']) ?></td>
                            <td class="medical-history" title="<?= htmlspecialchars($patient['Med_History']) ?>">
                                <?= $patient['Med_History'] ? htmlspecialchars(substr($patient['Med_History'], 0, 50)) . '...' : 'None' ?>
                            </td>
                            <td><?= $patient['encounter_count'] ?></td>
                            <td><?= $last_visit ?></td>
                            <td class="<?= $patient['IS_Active'] ? 'status-active' : 'status-inactive' ?>">
                                <?= $patient['IS_Active'] ? 'Active' : 'Inactive' ?>
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