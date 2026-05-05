<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

/* ✅ CHECK DB CONNECTION */
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

/* =========================
   ACTION HANDLER
========================= */
if (isset($_GET['action']) && isset($_GET['id'])) {

    $id = intval($_GET['id']); // 🔒 safe
    $action = $_GET['action'];

    if ($action == 'approve') {
        $status = 'Approved';
    } elseif ($action == 'cancel') {
        $status = 'Cancelled';
    } else {
        $status = 'Pending';
    }

    if (!$conn->query("UPDATE appointments SET status='$status' WHERE id='$id'")) {
        die("Update Error: " . $conn->error);
    }

    header("Location: appointments.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointments</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    body {
        background: url('../assets/img/bg.jpg') no-repeat center center fixed;
        background-size: cover;
    }

    .overlay {
        background: rgba(0,0,0,0.6);
        min-height: 100vh;
        padding: 20px;
    }

    .card {
        border-radius: 15px;
        background: rgba(255,255,255,0.95);
    }
    </style>
</head>

<body>

<div class="overlay">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <span class="navbar-brand">💇 Appointments</span>
        <div>
            <a href="dashboard.php" class="btn btn-secondary btn-sm">Dashboard</a>
            <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container">

<div class="card shadow p-4">

<h4 class="mb-3">📋 Appointment List</h4>

<table class="table table-bordered text-center">
<thead class="table-dark">
<tr>
    <th>Name</th>
    <th>Service</th>
    <th>Stylist</th>
    <th>Date</th>
    <th>Time</th>
    <th>Status</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>

<?php
$query = "
SELECT a.*, s.service_name, st.name AS stylist_name
FROM appointments a
LEFT JOIN services s ON a.service_id = s.id
LEFT JOIN stylists st ON a.stylist_id = st.id
ORDER BY a.id DESC
";

$result = $conn->query($query);

if (!$result) {
    die("Query Error: " . $conn->error);
}

if ($result->num_rows > 0):
    while($row = $result->fetch_assoc()):
?>

<tr>
    <td><?= htmlspecialchars($row['customer_name']) ?></td>
    <td><?= htmlspecialchars($row['service_name'] ?? 'N/A') ?></td>
    <td><?= htmlspecialchars($row['stylist_name'] ?? 'N/A') ?></td>
    <td><?= $row['appointment_date'] ?></td>
    <td><?= $row['appointment_time'] ?></td>

    <td>
        <span class="badge bg-<?=
            $row['status'] == 'Pending' ? 'warning' :
            ($row['status'] == 'Approved' ? 'success' : 'danger')
        ?>">
            <?= $row['status'] ?>
        </span>
    </td>

    <td>
        <?php if ($row['status'] == 'Pending'): ?>
            <a href="?action=approve&id=<?= $row['id'] ?>" class="btn btn-success btn-sm">Approve</a>
            <a href="?action=cancel&id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Cancel</a>
        <?php else: ?>
            —
        <?php endif; ?>
    </td>
</tr>

<?php
    endwhile;
else:
?>
<tr>
    <td colspan="7">No appointments found.</td>
</tr>
<?php endif; ?>

</tbody>
</table>

</div>

</div>

</div>

</body>
</html>