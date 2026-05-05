<?php
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
    $id = $_GET['delete'];
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
    $id = $_GET['edit'];
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
</head>

<body class="bg-light">

<div class="container mt-4">

<h3>💇 Manage Stylists (Assign Job)</h3>
<a href="dashboard.php" class="btn btn-secondary mb-3">Dashboard</a>

<!-- FORM -->
<div class="card p-3 mb-4">

<form method="POST">

    <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

    <!-- NAME -->
    <label>Stylist Name</label>
    <input type="text" name="name" class="form-control"
           value="<?= $editData['name'] ?? '' ?>" required>

    <!-- SERVICE ASSIGN -->
    <label class="mt-2">Assigned Service (Job)</label>
    <select name="service" class="form-control" required>
        <option value="">Select Service</option>
        <?php while($s = $services->fetch_assoc()): ?>
            <option value="<?= $s['id'] ?>"
                <?= (isset($editData['service_id']) && $editData['service_id'] == $s['id']) ? 'selected' : '' ?>>
                <?= $s['service_name'] ?>
            </option>
        <?php endwhile; ?>
    </select>

    <?php if ($editData): ?>
        <button name="update" class="btn btn-primary mt-2">Update</button>
        <a href="stylists.php" class="btn btn-secondary mt-2">Cancel</a>
    <?php else: ?>
        <button name="add" class="btn btn-success mt-2">Add Stylist</button>
    <?php endif; ?>

</form>

</div>

<!-- TABLE -->
<div class="card p-3">

<table class="table table-bordered">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Assigned Job</th>
    <th>Action</th>
</tr>

<?php
$result = $conn->query("
    SELECT stylists.*, services.service_name
    FROM stylists
    LEFT JOIN services ON stylists.service_id = services.id
");

while($row = $result->fetch_assoc()):
?>

<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['name'] ?></td>
    <td><?= $row['service_name'] ?? 'Not Assigned' ?></td>
    <td>
        <a href="?edit=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
        <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
           onclick="return confirm('Delete this stylist?')">Delete</a>
    </td>
</tr>

<?php endwhile; ?>

</table>

</div>

</div>

</body>
</html>