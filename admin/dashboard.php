<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

$today = date("Y-m-d");

$result1 = $conn->query("
    SELECT COUNT(*) AS total_customers 
    FROM appointments 
    WHERE appointment_date = '$today'
    AND status != 'Cancelled'
");
$row1 = $result1->fetch_assoc();
$customers = $row1['total_customers'] ?? 0;

$result2 = $conn->query("
    SELECT SUM(services.price) AS total_revenue
    FROM appointments
    JOIN services ON appointments.service_id = services.id
    WHERE appointment_date = '$today'
    AND appointments.status = 'Approved'
");
$row2 = $result2->fetch_assoc();
$revenue = $row2['total_revenue'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Salon Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<style>

/* =========================
   BACKGROUND IMAGE
========================= */
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;

    background: url('../assets/img/admin.jpg') no-repeat center center fixed;
    background-size: cover;
}

/* DARK OVERLAY */
body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    z-index: 0;
}

/* LAYOUT WRAPPERS */
.sidebar,
.main {
    position: relative;
    z-index: 2;
}

/* =========================
   SIDEBAR
========================= */
.sidebar {
    width: 260px;
    height: 100vh;
    position: fixed;

    background: rgba(20,20,20,0.75);
    backdrop-filter: blur(10px);

    color: white;
    padding: 20px;
}

.sidebar h4 {
    text-align: center;
    margin-bottom: 30px;
}

.sidebar a {
    display: block;
    padding: 10px;
    margin-bottom: 8px;

    color: white;
    text-decoration: none;

    border-radius: 8px;
    transition: 0.3s;
}

.sidebar a:hover {
    background: rgba(255,255,255,0.15);
}

/* =========================
   MAIN CONTENT
========================= */
.main {
    margin-left: 260px;
    padding: 25px;
}

/* TITLE */
.title {
    font-size: 28px;
    font-weight: bold;
    color: white;
    margin-bottom: 20px;
}

/* =========================
   DASHBOARD CARDS
========================= */
.card-box {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(12px);

    border-radius: 15px;
    padding: 25px;

    text-align: center;
    color: white;

    box-shadow: 0 10px 25px rgba(0,0,0,0.3);

    transition: 0.3s;
}

.card-box:hover {
    transform: translateY(-5px);
}

.card-box h5 {
    opacity: 0.9;
}

.card-box h1 {
    font-size: 42px;
    margin-top: 10px;
}

/* ICONS */
.icon-blue { color: #4facfe; }
.icon-green { color: #28a745; }

/* RESPONSIVE */
@media(max-width: 768px){
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .main {
        margin-left: 0;
    }
}

</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h4>💇 Salon Admin</h4>

    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="appointments.php"><i class="bi bi-calendar-check"></i> Appointments</a>
    <a href="services.php"><i class="bi bi-scissors"></i> Services</a>
    <a href="customers.php"><i class="bi bi-people"></i> Customers</a>
    <a href="stylists.php"><i class="bi bi-person-badge"></i> Stylists</a>
    <a href="reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
    <a href="walk_in_booking.php">🚶 Walk-in Booking</a>

    <a href="../logout.php" style="margin-top:20px; background:#ff4d6d; padding:10px; border-radius:8px; display:block; text-align:center;">
        Logout
    </a>
</div>

<!-- MAIN -->
<div class="main">

    <div class="title">📊 Today's Salon Overview</div>

    <div class="row g-4">

        <!-- CUSTOMERS -->
        <div class="col-md-6">
            <div class="card-box">
                <i class="bi bi-people-fill icon-blue" style="font-size:45px;"></i>
                <h5>Customers Today</h5>
                <h1><?= $customers ?></h1>
            </div>
        </div>

        <!-- REVENUE -->
        <div class="col-md-6">
            <div class="card-box">
                <i class="bi bi-cash-stack icon-green" style="font-size:45px;"></i>
                <h5>Revenue Today</h5>
                <h1>₱<?= number_format($revenue ?? 0, 2) ?></h1>
            </div>
        </div>

    </div>

</div>

</body>
</html>