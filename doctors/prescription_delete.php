<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/IHIS/includes/db_connect.php';

// Verify user is logged in and is either a doctor or admin
$prescription_id = isset($_GET['pr_id']) ? intval($_GET['pr_id']) : 0;

if ($prescription_id <= 0) {
    $_SESSION['error_message'] = "Invalid prescription ID";
    header('Location: prescriptions_view.php');
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Verify prescription exists and belongs to the doctor (if doctor)
    if ($_SESSION['user_type'] === 'doctor') {
        $stmt = $conn->prepare("
            SELECT p.PR_ID 
            FROM prescriptions p
            JOIN md_records m ON p.MDR_ID = m.MDR_ID
            JOIN encounters e ON m.EN_ID = e.EN_ID
            WHERE p.PR_ID = ? AND e.DR_ID = ?
        ");
        $stmt->bind_param("ii", $prescription_id, $_SESSION['doctor_ID']);
    } else {
        // Admin can delete any prescription
        $stmt = $conn->prepare("SELECT PR_ID FROM prescriptions WHERE PR_ID = ?");
        $stmt->bind_param("i", $prescription_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = "Prescription not found or you don't have permission to delete it";
        header('Location: prescriptions_view.php');
        exit();
    }

    // Delete the prescription
    $stmt = $conn->prepare("DELETE FROM prescriptions WHERE PR_ID = ?");
    $stmt->bind_param("i", $prescription_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    $_SESSION['success_message'] = "Prescription deleted successfully";
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Error deleting prescription: " . $e->getMessage();
}

// Redirect back to prescriptions view
$redirect_url = 'prescriptions_view.php';
if (isset($_GET['mdr_id']) && intval($_GET['mdr_id']) > 0) {
    $redirect_url .= '?mdr_id=' . intval($_GET['mdr_id']);
}

header("Location: $redirect_url");
exit();
?>