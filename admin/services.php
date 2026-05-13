<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

/* =========================
   ADD SERVICE
========================= */
if (isset($_POST['add'])) {

    $name = $_POST['name'];
    $price = $_POST['price'];

    // OPTIONAL stylist
    $stylist = !empty($_POST['stylist']) ? $_POST['stylist'] : NULL;

    $conn->query("
        INSERT INTO services (service_name, price, stylist_id) 
        VALUES ('$name','$price',".($stylist ? "'$stylist'" : "NULL").")
    ");
}

/* =========================
   DELETE SERVICE
========================= */
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $conn->query("DELETE FROM services WHERE id='$id'");
}

/* =========================
   LOAD EDIT
========================= */
$editData = null;

if (isset($_GET['edit'])) {

    $id = $_GET['edit'];

    $result = $conn->query("
        SELECT * FROM services 
        WHERE id='$id'
    ");

    $editData = $result->fetch_assoc();
}

/* =========================
   UPDATE SERVICE
========================= */
if (isset($_POST['update'])) {

    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];

    $stylist = !empty($_POST['stylist']) ? $_POST['stylist'] : NULL;

    $conn->query("
        UPDATE services 
        SET service_name='$name',
            price='$price',
            stylist_id=".($stylist ? "'$stylist'" : "NULL")."
        WHERE id='$id'
    ");

    header("Location: services.php");
    exit();
}

/* =========================
   LOAD STYLISTS
========================= */
$stylists = $conn->query("SELECT * FROM stylists");
?>

<!DOCTYPE html>
<html>
<head>
<title>Services</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    margin:0;
    padding:0;
    font-family:Arial,sans-serif;
    background:url('../assets/img/bg.jpg') no-repeat center center fixed;
    background-size:cover;
}

.overlay{
    background:rgba(0,0,0,0.6);
    min-height:100vh;
    padding:30px;
}

.container-box{
    background:rgba(255,255,255,0.9);
    padding:20px;
    border-radius:12px;
    box-shadow:0 10px 30px rgba(0,0,0,0.3);
}
</style>

</head>

<body>

<div class="overlay">

<div class="container container-box mt-4">

<h3>💇 Services</h3>

<a href="dashboard.php" class="btn btn-secondary mb-3">
    Dashboard
</a>

<!-- FORM -->
<form method="POST" class="mb-3 d-flex gap-2 flex-wrap">

    <?php if ($editData): ?>
        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
    <?php endif; ?>

    <input type="text"
           name="name"
           placeholder="Service Name"
           class="form-control"
           value="<?= $editData['service_name'] ?? '' ?>"
           required>

    <input type="number"
           name="price"
           placeholder="Price"
           class="form-control"
           value="<?= $editData['price'] ?? '' ?>"
           required>

    <!-- OPTIONAL STYLIST -->
    <select name="stylist" class="form-control">

        <option value="">No Stylist Assigned</option>

        <?php while($st = $stylists->fetch_assoc()): ?>

            <option value="<?= $st['id'] ?>"
                <?= (isset($editData['stylist_id']) && $editData['stylist_id'] == $st['id']) ? 'selected' : '' ?>>

                <?= $st['name'] ?>

            </option>

        <?php endwhile; ?>

    </select>

    <?php if ($editData): ?>

        <button name="update" class="btn btn-primary">
            Update
        </button>

        <a href="services.php" class="btn btn-secondary">
            Cancel
        </a>

    <?php else: ?>

        <button name="add" class="btn btn-success">
            Add
        </button>

    <?php endif; ?>

</form>

<!-- TABLE -->
<table class="table table-bordered bg-white">

<tr>
    <th>Service</th>
    <th>Price</th>
    <th>Stylist</th>
    <th>Action</th>
</tr>

<?php
$result = $conn->query("
    SELECT services.*, stylists.name AS stylist_name
    FROM services
    LEFT JOIN stylists 
    ON services.stylist_id = stylists.id
");

while($row = $result->fetch_assoc()):
?>

<tr>

    <td><?= $row['service_name'] ?></td>

    <td>₱<?= number_format($row['price'],2) ?></td>

    <td>
        <?= $row['stylist_name'] ?? 'Not Assigned' ?>
    </td>

    <td>

        <a href="?edit=<?= $row['id'] ?>"
           class="btn btn-warning btn-sm">
           Edit
        </a>

        <a href="?delete=<?= $row['id'] ?>"
           class="btn btn-danger btn-sm"
           onclick="return confirm('Delete this service?')">
           Delete
        </a>

    </td>

</tr>

<?php endwhile; ?>

</table>

</div>
</div>

</body>
</html>