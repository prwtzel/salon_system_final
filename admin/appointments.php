<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

/* =========================
   ACTION HANDLER
========================= */
if (isset($_GET['action']) && isset($_GET['id'])) {

    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == 'approve') {
        $status = 'Approved';

    } elseif ($action == 'cancel') {
        $status = 'Cancelled';

    } elseif ($action == 'complete') {   // ✅ NEW
        $status = 'Completed';

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

/* MODAL */
.modal-custom {
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.6);
    z-index:999;
}

.modal-box {
    background:white;
    width:350px;
    margin:15% auto;
    padding:20px;
    border-radius:12px;
    text-align:center;
}

.modal-box button {
    margin:10px 5px;
    padding:8px 15px;
    border:none;
    border-radius:8px;
}

.btn-yes { background:#28a745; color:white; }
.btn-no { background:#dc3545; color:white; }
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
            ($row['status'] == 'Approved' ? 'primary' :
            ($row['status'] == 'Completed' ? 'success' : 'danger'))
        ?>">
            <?= $row['status'] ?>
        </span>
    </td>

    <td>

        <?php if ($row['status'] == 'Pending'): ?>

            <button class="btn btn-success btn-sm"
                onclick="openModal('approve', <?= $row['id'] ?>)">
                Approve
            </button>

            <button class="btn btn-danger btn-sm"
                onclick="openModal('cancel', <?= $row['id'] ?>)">
                Cancel
            </button>

        <?php elseif ($row['status'] == 'Approved'): ?>

            <!-- ✅ COMPLETE BUTTON -->
            <button class="btn btn-primary btn-sm"
                onclick="openModal('complete', <?= $row['id'] ?>)">
                Complete
            </button>

        <?php else: ?>
            —
        <?php endif; ?>

    </td>
</tr>

<?php endwhile; ?>

</tbody>
</table>

</div>

</div>

</div>

<!-- MODAL -->
<div class="modal-custom" id="modal">
    <div class="modal-box">
        <h5 id="modalText">Are you sure?</h5>

        <button class="btn-yes" id="yesBtn">Yes</button>
        <button class="btn-no" onclick="closeModal()">No</button>
    </div>
</div>

<script>
let actionType = "";
let actionId = "";

function openModal(type, id){
    actionType = type;
    actionId = id;

    let text = "";

    if(type === "approve"){
        text = "Approve this appointment?";
    } 
    else if(type === "cancel"){
        text = "Cancel this appointment?";
    }
    else if(type === "complete"){
        text = "Mark this appointment as completed?";
    }

    document.getElementById("modalText").innerText = text;
    document.getElementById("modal").style.display = "block";
}

function closeModal(){
    document.getElementById("modal").style.display = "none";
}

document.getElementById("yesBtn").onclick = function(){
    window.location.href = "?action=" + actionType + "&id=" + actionId;
};
</script>

</body>
</html> 