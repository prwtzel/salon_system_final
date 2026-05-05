<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

/* =========================
   📅 FILTER (Daily / Monthly)
========================= */
$type = $_GET['type'] ?? 'monthly';
$search = $_GET['search'] ?? '';

$date_today = date("Y-m-d");
$month = date("Y-m");

/* =========================
   📌 CONDITION BUILDER
========================= */
$where = "";

if ($type == 'daily') {
    $where .= "DATE(appointment_date) = '$date_today'";
} else {
    $where .= "DATE_FORMAT(appointment_date, '%Y-%m') = '$month'";
}

if (!empty($search)) {
    $where .= " AND (customer_name LIKE '%$search%' 
                OR appointment_date LIKE '%$search%')";
}

/* =========================
   💰 REVENUE
========================= */
$revenue = $conn->query("
    SELECT SUM(services.price) AS total
    FROM appointments
    JOIN services ON appointments.service_id = services.id
    WHERE $where
")->fetch_assoc()['total'];

$revenue = $revenue ? $revenue : 0;

/* =========================
   📊 BOOKINGS COUNT
========================= */
$bookings = $conn->query("
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE $where
")->fetch_assoc()['total'];

/* =========================
   📋 FETCH DATA
========================= */
$data = $conn->query("
    SELECT appointments.*, services.service_name, services.price
    FROM appointments
    JOIN services ON appointments.service_id = services.id
    WHERE $where
    ORDER BY appointment_date DESC
");

/* =========================
   🧹 CLEAR REPORTS
========================= */
if (isset($_GET['clear'])) {
    $conn->query("DELETE FROM appointments");
    header("Location: reports.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Reports</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

<h3>📊 Reports</h3>

<!-- FILTER + SEARCH -->
<form method="GET" class="row mb-3">

    <div class="col-md-3">
        <select name="type" class="form-control">
            <option value="monthly" <?= $type == 'monthly' ? 'selected' : '' ?>>Monthly</option>
            <option value="daily" <?= $type == 'daily' ? 'selected' : '' ?>>Daily</option>
        </select>
    </div>

    <div class="col-md-5">
        <input type="text" name="search" class="form-control"
               placeholder="Search name or date..."
               value="<?= $search ?>">
    </div>

    <div class="col-md-4">
        <button class="btn btn-primary">Filter</button>
        <a href="reports.php" class="btn btn-secondary">Reset</a>
    </div>

</form>

<!-- BUTTONS -->
<div class="mb-3">
    <a href="dashboard.php" class="btn btn-dark">Dashboard</a>

    <a href="reports.php?clear=1"
       onclick="return confirm('Are you sure you want to clear ALL reports?')"
       class="btn btn-danger">
       Clear Reports
    </a>
</div>

<div class="row">

<!-- REVENUE -->
<div class="col-md-6">
    <div class="card p-4 text-center shadow">
        <h5><?= $type == 'daily' ? 'Daily' : 'Monthly' ?> Revenue</h5>
        <h2 class="text-success">₱<?= $revenue ?></h2>
    </div>
</div>

<!-- BOOKINGS -->
<div class="col-md-6">
    <div class="card p-4 text-center shadow">
        <h5><?= $type == 'daily' ? 'Daily' : 'Monthly' ?> Bookings</h5>
        <h2 class="text-primary"><?= $bookings ?></h2>
    </div>
</div>

</div>

<!-- TABLE -->
<div class="card mt-4 p-3 shadow">
    <h5>📋 Appointment Records</h5>

    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>Name</th>
                <th>Service</th>
                <th>Price</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>

        <?php if($data->num_rows > 0): ?>
            <?php while($row = $data->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['customer_name'] ?></td>
                    <td><?= $row['service_name'] ?></td>
                    <td>₱<?= $row['price'] ?></td>
                    <td><?= $row['appointment_date'] ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" class="text-center">No data found</td>
            </tr>
        <?php endif; ?>

        </tbody>
    </table>
</div>

</div>

</body>
</html>