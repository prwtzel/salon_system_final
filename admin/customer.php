<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

$notif = "";

// ✅ APPROVE
if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    $conn->query("UPDATE appointments SET status='Approved' WHERE id='$id'");
    $notif = "✅ Appointment Approved!";
}

// ❌ REJECT
if (isset($_GET['reject'])) {
    $id = $_GET['reject'];
    $conn->query("UPDATE appointments SET status='Rejected' WHERE id='$id'");
    $notif = "❌ Appointment Rejected!";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

<h3>👥 Customers</h3>
<a href="dashboard.php" class="btn btn-secondary mb-3">Dashboard</a>

<!-- 🔔 Notification -->
<?php if (!empty($notif)): ?>
    <div class="alert alert-info text-center"><?= $notif ?></div>
<?php endif; ?>

<!-- 👥 CUSTOMERS TABLE -->
<table class="table table-striped">
<tr>
    <th>Name</th>
    <th>Username</th>
</tr>

<?php
$result = $conn->query("SELECT * FROM customers");
while($row = $result->fetch_assoc()):
?>

<tr>
    <td><?= $row['name'] ?></td>
    <td><?= $row['username'] ?></td>
</tr>

<?php endwhile; ?>
</table>


<!-- 🔥 NEW SECTION: APPOINTMENTS -->
<h3 class="mt-5">📋 Appointment Requests</h3>

<table class="table table-bordered">
<tr>
    <th>Customer</th>
    <th>Service</th>
    <th>Stylist</th>
    <th>Date</th>
    <th>Time</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php
$appointments = $conn->query("
    SELECT a.*, s.service_name, st.name AS stylist_name 
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN stylists st ON a.stylist_id = st.id
    ORDER BY a.appointment_date DESC
");

while($row = $appointments->fetch_assoc()):
?>

<tr>
    <td><?= $row['customer_name'] ?></td>
    <td><?= $row['service_name'] ?></td>
    <td><?= $row['stylist_name'] ?></td>
    <td><?= $row['appointment_date'] ?></td>
    <td><?= $row['appointment_time'] ?></td>

    <td>
        <span class="badge bg-<?=
            $row['status'] == 'Pending' ? 'warning' :
            ($row['status'] == 'Approved' ? 'success' :
            ($row['status'] == 'Rejected' ? 'danger' : 'secondary'))
        ?>">
            <?= $row['status'] ?>
        </span>
    </td>

    <td>
        <?php if ($row['status'] == 'Pending'): ?>
            <a href="?approve=<?= $row['id'] ?>" class="btn btn-success btn-sm">Approve</a>
            <a href="?reject=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Reject</a>
        <?php else: ?>
            —
        <?php endif; ?>
    </td>
</tr>

<?php endwhile; ?>

</table>

</div>

</body>
</html>