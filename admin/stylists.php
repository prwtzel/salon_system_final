<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

/* =========================
   ADD STYLIST
========================= */
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $service = $_POST['service'];

    $conn->query("INSERT INTO stylists (name, service_id) 
                  VALUES ('$name','$service')");
    header("Location: stylists.php");
    exit();
}

/* =========================
   DELETE STYLIST
========================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM stylists WHERE id='$id'");
    header("Location: stylists.php");
    exit();
}

/* =========================
   UPDATE STYLIST
========================= */
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $service = $_POST['service'];

    $conn->query("UPDATE stylists 
                  SET name='$name', service_id='$service' 
                  WHERE id='$id'");
    header("Location: stylists.php");
    exit();
}

/* =========================
   LOAD EDIT DATA
========================= */
$editData = null;

if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM stylists WHERE id='$id'");
    $editData = $result->fetch_assoc();
}

/* =========================
   LOAD SERVICES
========================= */
$services = $conn->query("SELECT * FROM services");
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Stylists</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: url('../assets/img/bg.jpg') no-repeat center center fixed;
    background-size: cover;
}

/* OVERLAY */
.overlay {
    background: rgba(0,0,0,0.65);
    min-height: 100vh;
    padding: 30px;
}

/* CARD STYLE */
.card-custom {
    background: rgba(255,255,255,0.95);
    border-radius: 15px;
}

/* TITLE */
.title {
    color: white;
    font-weight: bold;
}
</style>

</head>

<body>

<div class="overlay">

<div class="container">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="title">💇 Manage Stylists</h3>
    <a href="dashboard.php" class="btn btn-light btn-sm">Dashboard</a>
</div>

<!-- =========================
     FORM
========================= -->
<div class="card card-custom shadow p-4 mb-4">

<form method="POST" class="row g-2">

    <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

    <!-- NAME -->
    <div class="col-md-4">
        <input type="text" name="name"
               placeholder="Stylist Name"
               class="form-control"
               value="<?= $editData['name'] ?? '' ?>"
               required>
    </div>

    <!-- SERVICE -->
    <div class="col-md-4">
        <select name="service" class="form-control" required>
            <option value="">Select Service</option>
            <?php 
            $services2 = $conn->query("SELECT * FROM services"); // reload
            while($s = $services2->fetch_assoc()): 
            ?>
                <option value="<?= $s['id'] ?>"
                    <?= (isset($editData['service_id']) && $editData['service_id'] == $s['id']) ? 'selected' : '' ?>>
                    <?= $s['service_name'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- BUTTON -->
    <div class="col-md-4">
        <?php if ($editData): ?>
            <button name="update" class="btn btn-primary">Update</button>
            <a href="stylists.php" class="btn btn-secondary">Cancel</a>
        <?php else: ?>
            <button name="add" class="btn btn-success">Add Stylist</button>
        <?php endif; ?>
    </div>

</form>

</div>

<!-- =========================
     TABLE
========================= -->
<div class="card card-custom shadow p-4">

<table class="table table-bordered table-hover text-center">

<thead class="table-dark">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Assigned Job</th>
    <th>Action</th>
</tr>
</thead>

<tbody>

<?php
$result = $conn->query("
    SELECT stylists.*, services.service_name
    FROM stylists
    LEFT JOIN services ON stylists.service_id = services.id
");

if ($result && $result->num_rows > 0):
while($row = $result->fetch_assoc()):
?>

<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= $row['service_name'] ?? 'Not Assigned' ?></td>
    <td>
        <a href="?edit=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
        <a href="?delete=<?= $row['id'] ?>" 
           class="btn btn-danger btn-sm"
           onclick="return confirm('Delete this stylist?')">
           Delete
        </a>
    </td>
</tr>

<?php endwhile; else: ?>

<tr>
    <td colspan="4">No stylists found.</td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>

</div>

</div>

</body>
</html>