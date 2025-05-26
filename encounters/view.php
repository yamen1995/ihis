<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Pagination setup
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Calculate offset
$start = ($page - 1) * $per_page;

// Fetch total appointments
$total = $conn->query("SELECT COUNT(*) FROM encounters")->fetch_row()[0];
$pages = ceil($total / $per_page);

// Fetch appointments with doctor and patient info
$appointments = [];
try {
    $stmt = $conn->prepare("
        SELECT e.*, 
               d.DR_ID, 
               CONCAT(dc.FName, ' ', dc.LName) AS doctor_name,
               d.Speciality,
               p.PA_ID,
               CONCAT(pc.FName, ' ', pc.LName) AS patient_name
        FROM encounters e
        JOIN doctors d ON e.DR_ID = d.DR_ID
        JOIN creds dc ON d.CR_ID = dc.CR_ID
        JOIN patients p ON e.PA_ID = p.PA_ID
        JOIN creds pc ON p.CR_ID = pc.CR_ID
        ORDER BY e.Date DESC, e.Time DESC
        LIMIT ?, ?
    ");
    $stmt->bind_param("ii", $start, $per_page);
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
    <title>Appointments</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
    <style>
        .appointment-actions {
            display: flex;
            gap: 5px;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-upcoming {
            background-color: #d4edda;
            color: #155724;
        }
        .status-completed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-cancelled {
            background-color: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>Appointment Management</h1>
        </div>
    </header>

    <div class="content-container">
        <div class="action-bar">
            <a href="add.php" class="action-btn add">+ New Appointment</a>
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search appointments...">
                <button type="submit" class="search-btn">Search</button>
            </form>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Specialty</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appt): ?>
                <tr>
                    <td><?= $appt['EN_ID'] ?></td>
                    <td><?= date('M j, Y', strtotime($appt['Date'])) ?></td>
                    <td><?= date('h:i A', strtotime($appt['Time'])) ?></td>
                    <td><?= htmlspecialchars($appt['patient_name']) ?></td>
                    <td><?= htmlspecialchars($appt['doctor_name']) ?></td>
                    <td><?= htmlspecialchars($appt['Speciality']) ?></td>
                    <td>
                        <?php 
                        $status = 'upcoming';
                        $status_class = 'status-upcoming';
                        if (strtotime($appt['Date'] . ' ' . $appt['Time']) < time()) {
                            $status = 'completed';
                            $status_class = 'status-completed';
                        }
                        ?>
                        <span class="status-badge <?= $status_class ?>"><?= ucfirst($status) ?></span>
                    </td>
                    <td class="appointment-actions">
                        <a href="edit_appointment.php?id=<?= $appt['EN_ID'] ?>" class="action-btn edit">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?= $page-1 ?>" class="page-link">&laquo; Previous</a>
            <?php endif; ?>
            
            <?php for($i = 1; $i <= $pages; $i++): ?>
                <a href="?page=<?= $i ?>" class="page-link <?= $page == $i ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if($page < $pages): ?>
                <a href="?page=<?= $page+1 ?>" class="page-link">Next &raquo;</a>
            <?php endif; ?>
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