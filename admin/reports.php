<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

/* =========================
   FILTER SYSTEM
========================= */
$type = $_GET['type'] ?? 'daily';
$search = $_GET['search'] ?? '';
$date_today = date("Y-m-d");

// ✅ NEW: Month selector
$selectedMonth = $_GET['month'] ?? date("Y-m");

/* =========================
   WHERE BUILDER
========================= */
$where = "1=1";

// DAILY
if ($type == 'daily') {
    $where .= " AND DATE(appointment_date) = '$date_today'";
}

// MONTHLY (FIXED)
else {
    $start = $selectedMonth . "-01";
    $end = date("Y-m-t", strtotime($start));

    $where .= " AND appointment_date BETWEEN '$start' AND '$end'";
}

// SEARCH
if (!empty($search)) {
    $where .= " AND (customer_name LIKE '%$search%' 
                OR appointment_date LIKE '%$search%')";
}

/* =========================
   REVENUE (Approved only)
========================= */
$revenue = $conn->query("
    SELECT SUM(services.price) AS total
    FROM appointments
    JOIN services ON appointments.service_id = services.id
    WHERE $where AND appointments.status='Approved'
")->fetch_assoc()['total'] ?? 0;

/* =========================
   BOOKINGS COUNT
========================= */
$bookings = $conn->query("
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE $where
")->fetch_assoc()['total'] ?? 0;

/* =========================
   DAILY STATUS
========================= */
$dailyApproved = $conn->query("
    SELECT COUNT(*) as total 
    FROM appointments 
    WHERE DATE(appointment_date)='$date_today' AND status='Approved'
")->fetch_assoc()['total'] ?? 0;

$dailyCancelled = $conn->query("
    SELECT COUNT(*) as total 
    FROM appointments 
    WHERE DATE(appointment_date)='$date_today' AND status='Cancelled'
")->fetch_assoc()['total'] ?? 0;

$dailyPending = $conn->query("
    SELECT COUNT(*) as total 
    FROM appointments 
    WHERE DATE(appointment_date)='$date_today' AND status='Pending'
")->fetch_assoc()['total'] ?? 0;

/* =========================
   DATA
========================= */
$data = $conn->query("
    SELECT appointments.*, services.service_name, services.price
    FROM appointments
    JOIN services ON appointments.service_id = services.id
    WHERE $where
    ORDER BY appointment_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Reports</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    margin:0;
    padding:0;
    font-family:Arial;
    background:url('../assets/img/bg.jpg') no-repeat center center fixed;
    background-size:cover;
}

.overlay {
    background:rgba(0,0,0,0.65);
    min-height:100vh;
    padding:30px;
}

.container-box {
    background:rgba(255,255,255,0.95);
    padding:20px;
    border-radius:12px;
}

.report-card {
    padding:15px;
    border-radius:10px;
    color:white;
}
</style>

</head>

<body>

<div class="overlay">
<div class="container container-box">

<h3>📊 Reports Dashboard</h3>

<a href="dashboard.php" class="btn btn-dark mb-3">
    ⬅ Back to Dashboard
</a>

<!-- FILTER -->
<form method="GET" class="row mb-3">

    <div class="col-md-3">
        <select name="type" class="form-control">
            <option value="daily" <?= $type=='daily'?'selected':'' ?>>Daily</option>
            <option value="monthly" <?= $type=='monthly'?'selected':'' ?>>Monthly</option>
        </select>
    </div>

    <!-- ✅ NEW MONTH PICKER -->
    <div class="col-md-3">
        <input type="month" name="month" class="form-control"
               value="<?= $selectedMonth ?>">
    </div>

    <div class="col-md-3">
        <input type="text" name="search" class="form-control"
               placeholder="Search name or date..."
               value="<?= htmlspecialchars($search) ?>">
    </div>

    <div class="col-md-3">
        <button class="btn btn-primary">Filter</button>
        <a href="reports.php" class="btn btn-secondary">Reset</a>
    </div>

</form>

<!-- DAILY REPORT -->
<?php if ($type == 'daily'): ?>
<div class="card p-3 mb-4">

<h4>📅 Daily Report (<?= $date_today ?>)</h4>

<div class="row text-center">

    <div class="col-md-4">
        <div class="report-card bg-success">
            <h6>Approved</h6>
            <h3><?= $dailyApproved ?></h3>
        </div>
    </div>

    <div class="col-md-4">
        <div class="report-card bg-danger">
            <h6>Cancelled</h6>
            <h3><?= $dailyCancelled ?></h3>
        </div>
    </div>

    <div class="col-md-4">
        <div class="report-card bg-warning text-dark">
            <h6>Pending</h6>
            <h3><?= $dailyPending ?></h3>
        </div>
    </div>

</div>

</div>
<?php endif; ?>

<!-- SUMMARY -->
<div class="row mb-3">

    <div class="col-md-6">
        <div class="card p-3 text-center">
            <h5><?= ucfirst($type) ?> Bookings</h5>
            <h3><?= $bookings ?></h3>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-3 text-center">
            <h5><?= ucfirst($type) ?> Revenue</h5>
            <h3 class="text-success">₱<?= number_format($revenue,2) ?></h3>
        </div>
    </div>

</div>

<!-- TABLE -->
<div class="card p-3">

<h5>📋 Appointment Records</h5>

<table class="table table-bordered mt-3">
<thead>
<tr>
    <th>Name</th>
    <th>Service</th>
    <th>Price</th>
    <th>Date</th>
    <th>Status</th>
</tr>
</thead>

<tbody>

<?php if ($data->num_rows > 0): ?>
    <?php while($row = $data->fetch_assoc()): ?>
    <tr>
        <td><?= $row['customer_name'] ?></td>
        <td><?= $row['service_name'] ?></td>
        <td>₱<?= number_format($row['price'],2) ?></td>
        <td><?= $row['appointment_date'] ?></td>
        <td>
            <span class="badge bg-<?=
                $row['status']=='Approved'?'success':
                ($row['status']=='Cancelled'?'danger':'warning')
            ?>">
                <?= $row['status'] ?>
            </span>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="5" class="text-center">No data found</td>
</tr>
<?php endif; ?>

</tbody>
</table>

</div>

</div>
</div>

</body>
</html>