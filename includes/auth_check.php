<?php
session_start();

if (!isset($_SESSION['staff_ID'])) {
    header('Location: staff_login.php');
    exit();
}
?>