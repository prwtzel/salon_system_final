<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // ✅ Start session for login check
include 'db.php'; 
include 'includes/header.php'; 

// 🔐 Require customer login
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php?msg=login_required");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name']; // stored on login

$success = "";

if (isset($_POST['submit'])) {
    $service = $_POST['service'];
    $stylist = $_POST['stylist'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    if (!empty($service) && !empty($stylist) && !empty($date) && !empty($time)) {

        $conn->query("INSERT INTO appointments 
        (customer_id, customer_name, service_id, stylist_id, appointment_date, appointment_time)
        VALUES ('$customer_id','$customer_name','$service','$stylist','$date','$time')");

        $success = "✅ Appointment booked successfully!";
    } else {
        echo "<div class='alert alert-danger'>All fields are required!</div>";
    }
}
?>