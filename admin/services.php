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
    $stylist = $_POST['stylist'];

    $conn->query("INSERT INTO services (service_name, price, stylist_id) 
                  VALUES ('$name','$price','$stylist')");
}

/* =========================
   DELETE SERVICE
========================= */
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM services WHERE id='$id'");
}

/* =========================
   LOAD SERVICE FOR EDIT
========================= */
$editData = null;

if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM services WHERE id='$id'");
    $editData = $result->fetch_assoc();
}

/* =========================
   UPDATE SERVICE
========================= */
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stylist = $_POST['stylist'];

    $conn->query("UPDATE services 
                  SET service_name='$name', price='$price', stylist_id='$stylist' 
                  WHERE id='$id'");

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
</head>

<body class="bg-light">

<div class="container mt-4">
<h3>💇 Services</h3>
<a href="dashboard.php" class="btn btn-secondary">Dashboard</a>

<!-- =========================
     ADD / EDIT FORM
========================= -->

<form method="POST" class="mb-3 d-flex gap-2 flex-wrap">

    <?php if ($editData): ?>
        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
    <?php endif; ?>

    <input type="text" name="name"
        placeholder="Service Name"
        class="form-control"
        value="<?= $editData['service_name'] ?? '' ?>"
        required>

    <input type="number" name="price"
        placeholder="Price"
        class="form-control"
        value="<?= $editData['price'] ?? '' ?>"
        required>

    <!-- NEW: STYLIST SELECT -->
    <select name="stylist" class="form-control" required>
        <option value="">Select Stylist</option>
        <?php while($st = $stylists->fetch_assoc()): ?>
            <option value="<?= $st['id'] ?>"
                <?= (isset($editData['stylist_id']) && $editData['stylist_id'] == $st['id']) ? 'selected' : '' ?>>
                <?= $st['name'] ?>
            </option>
        <?php endwhile; ?>
    </select>

    <?php if ($editData): ?>
        <button name="update" class="btn btn-primary">Update</button>
        <a href="services.php" class="btn btn-secondary">Cancel</a>
    <?php else: ?>
        <button name="add" class="btn btn-success">Add</button>
    <?php endif; ?>

</form>

<!-- =========================
     TABLE
========================= -->

<table class="table table-bordered">
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
    LEFT JOIN stylists ON services.stylist_id = stylists.id
");

while($row = $result->fetch_assoc()):
?>

<tr>
    <td><?= $row['service_name'] ?></td>
    <td>₱<?= $row['price'] ?></td>
    <td><?= $row['stylist_name'] ?? 'Not Assigned' ?></td>
    <td>
        <a href="?edit=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
        <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
           onclick="return confirm('Delete this service?')">
           Delete
        </a>
    </td>
</tr>

<?php endwhile; ?>

</table>

</div>

</body>
</html>