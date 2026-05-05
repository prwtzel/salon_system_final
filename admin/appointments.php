<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

include '../db.php';
?>

<?php
// ACTION HANDLER
if (isset($_GET['action']) && isset($_GET['id'])) {

    $id = $_GET['id'];
    $action = $_GET['action'];

    if ($action == 'approve') {
        $status = 'Approved';
    } elseif ($action == 'cancel') {
        $status = 'Cancelled';
    } else {
        $status = 'Pending';
    }

    $conn->query("UPDATE appointments SET status='$status' WHERE id='$id'");

    header("Location: appointments.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointments</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand">
            <i class="bi bi-calendar-check"></i> Appointments
        </span>
        <div>
            <a href="dashboard.php" class="btn btn-secondary btn-sm">Dashboard</a>
            <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <div class="card shadow p-4">
        <h4 class="mb-3">📋 Appointment List</h4>

        <table class="table table-bordered table-hover text-center">
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
$result = $conn->query("
    SELECT a.*, s.service_name, st.name AS stylist_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN stylists st ON a.stylist_id = st.id
    ORDER BY a.id DESC
");

if ($result && $result->num_rows > 0):
    while($row = $result->fetch_assoc()):
?>
                <tr>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td><?= htmlspecialchars($row['service_name']) ?></td>
                    <td><?= htmlspecialchars($row['stylist_name']) ?></td>
                    <td><?= $row['appointment_date'] ?></td>
                    <td><?= $row['appointment_time'] ?></td>

                    <!-- STATUS BADGE -->
                    <td>
                        <span class="badge bg-<?= 
                            $row['status'] == 'Pending' ? 'warning' : 
                            ($row['status'] == 'Approved' ? 'success' : 'danger')
                        ?>">
                            <?= $row['status'] ?>
                        </span>
                    </td>

                    <!-- ACTION BUTTONS -->
                    <td>
                        <?php if ($row['status'] == 'Pending'): ?>
                            <a href="?action=approve&id=<?= $row['id'] ?>" class="btn btn-success btn-sm">
                                Approve
                            </a>

                            <a href="?action=cancel&id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">
                                Cancel
                            </a>
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

</body>
</html>