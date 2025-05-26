<?php
require_once '../includes/db_connect.php';

// Pagination setup
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1; // Ensure page is never less than 1

// Calculate offset
$start = ($page - 1) * $per_page;

// Fetch total records
$sql = "SELECT COUNT(*) AS count FROM patients";
$result = $conn->query($sql);
if (!$result) {
    die("Error counting patients: " . $conn->error);
}
$total = $result->fetch_assoc();
$pages = ceil($total['count'] / $per_page);

// Ensure current page doesn't exceed total pages
if ($page > $pages && $pages > 0) {
    $page = $pages;
    $start = ($page - 1) * $per_page;
}

// Fetch patients with pagination
$patients = [];
$stmt = "
    SELECT creds.*, patients.*
    FROM creds, patients 
    WHERE creds.CR_ID = patients.CR_ID
    ORDER BY creds.DOA DESC
    LIMIT $start, $per_page
";

$result = $conn->query($stmt);
if ($result) {
    $patients = $result->fetch_all(MYSQLI_ASSOC);
} else {
    die("Error fetching patients: " . $conn->error);
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Patients</title>
    <link rel="stylesheet" href="/ihis/css/styles.css">
    <link rel="stylesheet" href="/ihis/css/staff.css">
</head>
<body>
<header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>View Patients</h1>
        </div>
    </header>
    <div class="content-container">
        <h2>Patient Records</h2>
        <div class="action-bar">
            <a href="add.php" class="action-btn add">+ Add New Patient</a>
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search patients...">
                <button type="submit" class="search-btn">Search</button>
            </form>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>DOB</th>
                    <th>Arrival Date</th>
                    <th>Phone</th>
                     <th>security code</th>
                    <th>Status</th>
                    <th>Assigned Staff</th>
                    <th>Room</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patients as $patient): ?>
                <tr>
                    <td><?= $patient['PA_ID'] ?></td>
                    <td><?= htmlspecialchars($patient['FName'] . ' ' . $patient['LName']) ?></td>
                    <td><?= htmlspecialchars($patient['Gender']) ?></td>
                    <td><?= date('M j, Y', strtotime($patient['DOB'])) ?></td>
                    <td><?= date('M j, Y H:i', strtotime($patient['DOA'])) ?></td>
                    <td><?= htmlspecialchars($patient['Phone']) ?></td>
                    <td><?= htmlspecialchars($patient['Sec_Code']) ?></td>
                    <td>
                        <span class="status-badge <?= $patient['IS_Active'] ? 'active' : 'inactive' ?>">
                            <?= $patient['IS_Active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td><?= $patient['ST_ID'] ?? 'Unassigned' ?></td>
                    <td><?= $patient['RO_ID'] ?? 'Unassigned' ?></td>
                    <td class="action-cells">
                        <a href="edit.php?id=<?= $patient['PA_ID'] ?>" class="action-btn edit">Edit</a>
                         <form method="POST" action="toggle_patient_status.php" class="inline-form" 
                  onsubmit="return confirm('Are you sure you want to <?= $patient['IS_Active'] ? 'deactivate' : 'activate' ?> this patient?');">
                <input type="hidden" name="patient_id" value="<?= $patient['PA_ID'] ?>">
                <button type="submit" class="action-btn <?= $patient['IS_Active'] ? 'btn-deactivate' : 'btn-activate' ?>">
                    <?= $patient['IS_Active'] ? 'Deactivate' : 'Activate' ?>
                </button>
            </form>
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
            
            <?php 
            // Show page numbers (limit to 5 around current page)
            $start_page = max(1, $page - 2);
            $end_page = min($pages, $page + 2);
            
            for($i = $start_page; $i <= $end_page; $i++): ?>
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