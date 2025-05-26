<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Handle room status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id'])) {
    $room_id = (int)$_POST['room_id'];
    try {
        $conn->query("UPDATE rooms SET Is_Occupied = NOT Is_Occupied WHERE RO_ID = $room_id");
    } catch (Exception $e) {
        die("Error updating room status: " . $e->getMessage());
    }
}

// Fetch all rooms with patient count and bed info
$rooms = [];
$available_beds = 0;
$total_beds = 0;

try {
    $rooms = $conn->query("
        SELECT r.*, 
               COUNT(p.PA_ID) AS patient_count,
               GROUP_CONCAT(CONCAT(pc.FName, ' ', pc.LName) SEPARATOR ', ') AS patients,
               (r.Bed_num - COUNT(p.PA_ID)) AS available_beds
        FROM rooms r
        LEFT JOIN patients p ON r.RO_ID = p.RO_ID AND p.IS_Active = 1
        LEFT JOIN creds pc ON p.CR_ID = pc.CR_ID
        GROUP BY r.RO_ID
        ORDER BY r.Ro_Num
    ")->fetch_all(MYSQLI_ASSOC);

    // Calculate total and available beds
    foreach ($rooms as $room) {
        $total_beds += $room['Bed_num'];
        $available_beds += $room['available_beds'];
    }

} catch (Exception $e) {
    die("Error fetching rooms: " . $e->getMessage());
}

// Get rooms that need attention (nearly full or empty)
$attention_rooms = array_filter($rooms, function($room) {
    $occupancy = $room['patient_count'] / $room['Bed_num'];
    return ($occupancy > 0.8 || $occupancy < 0.2) && $room['Bed_num'] > 0;
});

// Sort by most critical first
usort($attention_rooms, function($a, $b) {
    $a_priority = $a['patient_count'] / $a['Bed_num'];
    $b_priority = $b['patient_count'] / $b['Bed_num'];
    return abs($a_priority - 0.5) < abs($b_priority - 0.5) ? 1 : -1;
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Management</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/staff.css">
    <style>
        .room-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 5px solid transparent;
        }
        .room-card.attention {
            border-left-color: #ffc107;
            background-color: #fffcf5;
        }
        .room-card.critical {
            border-left-color: #dc3545;
            background-color: #fff5f5;
        }
        .room-info {
            flex: 1;
        }
        .room-status {
            width: 150px;
            text-align: center;
        }
        .status-indicator {
            display: inline-block;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-available {
            background-color: #28a745;
        }
        .status-occupied {
            background-color: #dc3545;
        }
        .toggle-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 5px;
        }
        .toggle-available {
            background-color: #dc3545;
            color: white;
        }
        .toggle-occupied {
            background-color: #28a745;
            color: white;
        }
        .patient-list {
            margin-top: 10px;
            font-size: 0.9em;
            color: #6c757d;
        }
        .bed-info {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        .bed-count {
            font-size: 0.9em;
        }
        .bed-count .number {
            font-weight: bold;
            color: #0056b3;
        }
        .summary-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
        }
        .summary-item {
            text-align: center;
            flex: 1;
        }
        .summary-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0056b3;
        }
        .attention-list {
            margin-bottom: 30px;
        }
        .attention-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="../images/ihis-logo.png" alt="IhIS Logo" class="logo-img">
            <h1>Room & Bed Management</h1>
        </div>
    </header>

    <div class="content-container">
        <div class="summary-card">
            <div class="summary-item">
                <div class="summary-number"><?= count($rooms) ?></div>
                <div>Total Rooms</div>
            </div>
            <div class="summary-item">
                <div class="summary-number"><?= $total_beds ?></div>
                <div>Total Beds</div>
            </div>
            <div class="summary-item">
                <div class="summary-number"><?= $available_beds ?></div>
                <div>Available Beds</div>
            </div>
            <div class="summary-item">
                <div class="summary-number"><?= round(($total_beds - $available_beds)/$total_beds*100, 1) ?>%</div>
                <div>Occupancy Rate</div>
            </div>
        </div>

        <?php if (!empty($attention_rooms)): ?>
        <div class="attention-list">
            <div class="attention-header">
                <h2>Rooms Needing Attention</h2>
                <small>Suggested rooms to adjust</small>
            </div>
            
            <?php foreach ($attention_rooms as $room): 
                $occupancy = $room['patient_count'] / $room['Bed_num'];
                $is_critical = $occupancy > 0.9 || $occupancy < 0.1;
            ?>
            <div class="room-card <?= $is_critical ? 'critical' : 'attention' ?>">
                <div class="room-info">
                    <h3>Room <?= htmlspecialchars($room['Ro_Num']) ?></h3>
                    <div class="room-meta">
                        <span class="status-indicator <?= $room['Is_Occupied'] ? 'status-occupied' : 'status-available' ?>"></span>
                        <span><?= $room['Is_Occupied'] ? 'Occupied' : 'Available' ?></span>
                        <span> • Beds: <?= $room['Bed_num'] ?></span>
                    </div>
                    <div class="bed-info">
                        <div class="bed-count">
                            <span class="number"><?= $room['patient_count'] ?></span> patients
                        </div>
                        <div class="bed-count">
                            <span class="number"><?= $room['available_beds'] ?></span> beds available
                        </div>
                        <div class="bed-count">
                            <span class="number"><?= round($occupancy*100) ?>%</span> occupied
                        </div>
                    </div>
                    <?php if ($room['patient_count'] > 0): ?>
                    <div class="patient-list">
                        Patients: <?= htmlspecialchars($room['patients']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <form method="POST" class="room-status">
                    <input type="hidden" name="room_id" value="<?= $room['RO_ID'] ?>">
                    <button type="submit" class="toggle-btn <?= $room['Is_Occupied'] ? 'toggle-available' : 'toggle-occupied' ?>">
                        <?= $room['Is_Occupied'] ? 'Mark Available' : 'Mark Occupied' ?>
                    </button>
                    <?php if ($occupancy > 0.8): ?>
                    <div class="suggestion">Suggest: Add more beds or move patients</div>
                    <?php elseif ($occupancy < 0.2): ?>
                    <div class="suggestion">Suggest: Consolidate patients</div>
                    <?php endif; ?>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <h2>All Rooms</h2>
        
        <div class="room-list">
            <?php foreach ($rooms as $room): 
                $occupancy = $room['Bed_num'] > 0 ? $room['patient_count'] / $room['Bed_num'] : 0;
            ?>
            <div class="room-card">
                <div class="room-info">
                    <h3>Room <?= htmlspecialchars($room['Ro_Num']) ?></h3>
                    <div class="room-meta">
                        <span class="status-indicator <?= $room['Is_Occupied'] ? 'status-occupied' : 'status-available' ?>"></span>
                        <span><?= $room['Is_Occupied'] ? 'Occupied' : 'Available' ?></span>
                        <span> • Beds: <?= $room['Bed_num'] ?></span>
                    </div>
                    <div class="bed-info">
                        <div class="bed-count">
                            <span class="number"><?= $room['patient_count'] ?></span> patients
                        </div>
                        <div class="bed-count">
                            <span class="number"><?= $room['available_beds'] ?></span> beds available
                        </div>
                        <div class="bed-count">
                            <span class="number"><?= round($occupancy*100) ?>%</span> occupied
                        </div>
                    </div>
                    <?php if ($room['patient_count'] > 0): ?>
                    <div class="patient-list">
                        Patients: <?= htmlspecialchars($room['patients']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <form method="POST" class="room-status">
                    <input type="hidden" name="room_id" value="<?= $room['RO_ID'] ?>">
                    <button type="submit" class="toggle-btn <?= $room['Is_Occupied'] ? 'toggle-available' : 'toggle-occupied' ?>">
                        <?= $room['Is_Occupied'] ? 'Mark Available' : 'Mark Occupied' ?>
                    </button>
                </form>
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