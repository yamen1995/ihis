<?php
require_once '../includes/db_connect.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);
    
    if ($patient_id) {
        try {
            // First get current status
            $stmt = $conn->prepare("SELECT IS_Active FROM Patients WHERE PA_ID = ?");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $current_status = $result->fetch_assoc()['IS_Active'];
                $new_status = $current_status ? 0 : 1;
                
                // Toggle status
                $stmt = $conn->prepare("UPDATE Patients SET IS_Active = ? WHERE PA_ID = ?");
                $stmt->bind_param("ii", $new_status, $patient_id);
                $stmt->execute();
                
                $_SESSION['success_message'] = "Patient " . ($new_status ? "activated" : "deactivated") . " successfully!";
            } else {
                $_SESSION['error_message'] = "Patient not found.";
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error updating patient status: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Invalid patient ID.";
    }
    
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}
?>