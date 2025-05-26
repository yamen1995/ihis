<?php
require_once '../includes/db_connect.php';
session_start();
$doctor_id = $_SESSION['doctor_ID'];
 $sql = "SELECT * FROM doctors,Creds WHERE doctors.CR_ID=$doctor_id and doctors.CR_ID=Creds.CR_ID";
    $result=$conn->query($sql);
    $doctor_info = $result->fetch_array(); 
   $dr_id=$doctor_info['DR_ID'];
$mdr_id = isset($_GET['mdr_id']) ? intval($_GET['mdr_id']) : 0;

// Fetch prescriptions with verification
try {
    if ($mdr_id) {
        // View for specific medical record
        $stmt = $conn->prepare("
            SELECT p.*,e.Date, m.Summery, CONCAT(c.FName, ' ', c.LName) AS patient_name
            FROM prescriptions p
            JOIN md_records m ON p.MDR_ID = m.MDR_ID
            JOIN encounters e ON m.EN_ID = e.EN_ID
            JOIN patients pa ON e.PA_ID = pa.PA_ID
            JOIN creds c ON pa.CR_ID = c.CR_ID
            WHERE p.MDR_ID = ? AND e.DR_ID = ?
            ORDER BY e.Date DESC
        ");
        $stmt->bind_param("ii", $mdr_id, $dr_id);
    } else {
        // View all prescriptions for doctor
        $stmt = $conn->prepare("
            SELECT p.*,e.Date, m.Summery, CONCAT(c.FName, ' ', c.LName) AS patient_name
            FROM prescriptions p
            JOIN md_records m ON p.MDR_ID = m.MDR_ID
            JOIN encounters e ON m.EN_ID = e.EN_ID
            JOIN patients pa ON e.PA_ID = pa.PA_ID
            JOIN creds c ON pa.CR_ID = c.CR_ID
            WHERE e.DR_ID = ?
            ORDER BY e.Date DESC
        ");
        $stmt->bind_param("i", $dr_id);
    }
    
    $stmt->execute();
    $prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get patient info if viewing specific record
    $patient_info = [];
    if ($mdr_id && !empty($prescriptions)) {
        $patient_info = [
            'name' => $prescriptions[0]['patient_name'],
            'summery' => $prescriptions[0]['Summery']
        ];
    }
} catch (Exception $e) {
    die("Error fetching prescriptions: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Prescriptions</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .patient-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .prescriptions-table {
            width: 100%;
            border-collapse: collapse;
        }
        .prescriptions-table th, 
        .prescriptions-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .prescriptions-table th {
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
        .no-records {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1><?= $mdr_id ? 'Prescriptions for Medical Record' : 'All Prescriptions' ?></h1>
        </div>
    </header>

    <div class="container">
        <?php if ($mdr_id && !empty($patient_info)): ?>
            <div class="patient-info">
                <h3>Patient: <?= htmlspecialchars($patient_info['name']) ?></h3>
                <p><strong>Medical Summary:</strong> <?= htmlspecialchars(substr($patient_info['summery'], 0, 100)) ?>...</p>
            </div>
        <?php endif; ?>

        <div class="header-actions">
            <h2><?= $mdr_id ? 'Prescriptions' : 'All Prescriptions' ?></h2>
            <?php if ($mdr_id): ?>
                <a href="prescription_add.php?mdr_id=<?= $mdr_id ?>" class="action-btn btn-primary">Add New Prescription</a>
            <?php endif; ?>
        </div>

        <?php if (empty($prescriptions)): ?>
            <div class="no-records">
                <p>No prescriptions found</p>
                <?php if ($mdr_id): ?>
                    <a href="prescription_add.php?mdr_id=<?= $mdr_id ?>" class="action-btn btn-primary">Add First Prescription</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="prescriptions-table">
                <thead>
                    <tr>
                        <?php if (!$mdr_id): ?>
                            <th>Patient</th>
                        <?php endif; ?>
                        <th>Drug</th>
                        <th>Dosage</th>
                        <th>Prescribed On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prescriptions as $rx): ?>
                        <tr>
                            <?php if (!$mdr_id): ?>
                                <td><?= htmlspecialchars($rx['patient_name']) ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($rx['Drug']) ?></td>
                            <td><?= htmlspecialchars($rx['Dosage']) ?></td>
                            <td><?= date('M j, Y h:i A', strtotime($rx['Date'])) ?></td>
                            <td>
                                <a href="#" onclick="confirmDelete(<?= $rx['PR_ID'] ?>)" class="action-btn" style="background:#dc3545;color:white">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        function confirmDelete(prescriptionId) {
            if (confirm('Are you sure you want to delete this prescription?')) {
                window.location.href = 'prescription_delete.php?pr_id=' + prescriptionId;
            }
        }
    </script>

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