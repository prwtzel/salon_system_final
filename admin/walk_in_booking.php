<?php
include '../db.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

$success = "";

// SAVE WALK-IN BOOKING
if (isset($_POST['book_walkin'])) {

    $customer_name = $_POST['customer_name'];
    $service = $_POST['service'];
    $stylist = $_POST['stylist'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    $conn->query("
        INSERT INTO appointments 
        (customer_name, service_id, stylist_id, appointment_date, appointment_time, status)
        VALUES 
        ('$customer_name','$service','$stylist','$date','$time','Approved')
    ");

    $success = "Walk-in appointment booked successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Walk-in Booking</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* =========================
   BACKGROUND IMAGE
========================= */
body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;

    background: url('../assets/img/bg.jpg') no-repeat center center fixed;
    background-size: cover;
}

/* DARK OVERLAY */
.overlay {
    background: rgba(0,0,0,0.65);
    min-height: 100vh;
    padding: 30px;
}

/* GLASS FORM */
.container-box {
    background: rgba(255,255,255,0.92);
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    max-width: 600px;
    margin: auto;
}

/* TITLE STYLE */
h3 {
    color: white;
    text-align: center;
    margin-bottom: 20px;
}
</style>

</head>

<body>

<div class="overlay">

<div class="container">

<h3>🚶 Walk-in Booking (Admin)</h3>

<div class="container-box">

    <a href="dashboard.php" class="btn btn-secondary mb-3">Dashboard</a>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success text-center">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Customer Name</label>
        <input type="text" name="customer_name" class="form-control mb-3" required>

        <label>Service</label>
        <select name="service" class="form-control mb-3" required>
            <?php
            $services = $conn->query("SELECT * FROM services");
            while($s = $services->fetch_assoc()):
            ?>
                <option value="<?= $s['id'] ?>">
                    <?= $s['service_name'] ?> (₱<?= $s['price'] ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <label>Stylist</label>
        <select name="stylist" class="form-control mb-3" required>
            <?php
            $stylists = $conn->query("SELECT * FROM stylists");
            while($st = $stylists->fetch_assoc()):
            ?>
                <option value="<?= $st['id'] ?>">
                    <?= $st['name'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Date</label>
        <input type="date" name="date" class="form-control mb-3" required>

        <label>Time</label>
        <input type="time" name="time" class="form-control mb-3" required>

        <button name="book_walkin" class="btn btn-success w-100">
            Book Walk-in
        </button>

    </form>

</div>

</div>
</div>

</body>
</html>