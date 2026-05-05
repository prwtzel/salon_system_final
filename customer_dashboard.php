<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: customer_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$customer_name = $_SESSION['name'];

$success = "";
$notif = "";

/* =========================
   NOTIFICATION SYSTEM
========================= */
$checkNotif = $conn->query("
    SELECT * FROM appointments 
    WHERE user_id='$user_id'
    AND status='Approved'
    AND notif_seen=0
    ORDER BY id DESC
    LIMIT 1
");

if ($checkNotif && $checkNotif->num_rows > 0) {
    $rowNotif = $checkNotif->fetch_assoc();

    $notif = "🎉 Your appointment on " . $rowNotif['appointment_date'] . " has been APPROVED!";
    $conn->query("UPDATE appointments SET notif_seen=1 WHERE id=".$rowNotif['id']);
}

/* =========================
   BOOK APPOINTMENT
========================= */
if (isset($_POST['submit'])) {

    $name = $_POST['name'];
    $service = $_POST['service'];
    $stylist = $_POST['stylist'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    if ($name && $service && $stylist && $date && $time) {

        $conn->query("
            INSERT INTO appointments 
            (user_id, customer_name, service_id, stylist_id, appointment_date, appointment_time, status)
            VALUES 
            ('$user_id','$name','$service','$stylist','$date','$time','Pending')
        ");

        header("Location: customer_dashboard.php?msg=booked");
        exit();
    } else {
        $notif = "All fields are required!";
    }
}

/* =========================
   CANCEL
========================= */
if (isset($_GET['cancel'])) {
    $id = $_GET['cancel'];

    $conn->query("
        UPDATE appointments 
        SET status='Cancelled' 
        WHERE id='$id' AND user_id='$user_id'
    ");

    header("Location: customer_dashboard.php?msg=cancel");
    exit();
}

/* =========================
   DELETE
========================= */
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $conn->query("
        DELETE FROM appointments 
        WHERE id='$id' AND user_id='$user_id'
    ");

    header("Location: customer_dashboard.php?msg=deleted");
    exit();
}

/* =========================
   MESSAGES
========================= */
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == "booked") $success = "Appointment booked successfully!";
    if ($_GET['msg'] == "cancel") $notif = "Booking cancelled!";
    if ($_GET['msg'] == "deleted") $notif = "Appointment deleted!";
}

/* =========================
   APPOINTMENTS QUERY (FIXED)
========================= */
$appointments = $conn->query("
SELECT a.*, 
       s.service_name AS service_name, 
       st.name AS stylist_name
FROM appointments a
LEFT JOIN services s ON a.service_id = s.id
LEFT JOIN stylists st ON a.stylist_id = st.id
WHERE a.user_id='$user_id'
ORDER BY a.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Salon Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

/* BACKGROUND */
body {
    background: url('assets/img/bg.jpg') no-repeat center center fixed;
    background-size: cover;
}

/* OVERLAY */
body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    z-index: 0;
}

/* TOP BAR */
.topbar {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: space-between;
    padding: 15px 25px;
    color: white;
}

/* TITLE */
.title {
    position: relative;
    z-index: 2;
    text-align: center;
    font-size: 30px;
    color: white;
    margin: 10px 0;
}

/* ALERT */
.alert {
    position: relative;
    z-index: 2;
    width: 80%;
    margin: 10px auto;
    padding: 10px;
    border-radius: 10px;
    text-align: center;
}

.success { background: rgba(0,255,0,0.15); color: #b6ffb6; }
.error { background: rgba(255,0,0,0.15); color: #ffb3b3; }

/* CONTAINER */
.container {
    position: relative;
    z-index: 2;
    width: 92%;
    margin: auto;

    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 20px;
}

/* CARD */
.card {
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(12px);
    padding: 20px;
    border-radius: 15px;
    color: white;
}

/* INPUT */
input, select {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border-radius: 8px;
    border: none;
}

/* BUTTON */
button {
    width: 100%;
    padding: 10px;
    background: #ff4d6d;
    border: none;
    color: white;
    border-radius: 10px;
}

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    color: black;
    border-radius: 10px;
    overflow: hidden;
}

th {
    background: #ff4d6d;
    color: white;
    padding: 10px;
}

td {
    padding: 10px;
    text-align: center;
}

/* STATUS */
.status {
    padding: 5px 8px;
    border-radius: 6px;
    color: white;
}

/* RESPONSIVE */
@media(max-width: 900px){
    .container {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<div class="topbar">
    <div>💇 Glow Salon</div>
    <div><a href="logout.php" style="color:white;">Logout</a></div>
</div>

<div class="title">Welcome, <?php echo $customer_name; ?></div>

<?php if (!empty($notif)): ?>
<div class="alert error"><?php echo $notif; ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="alert success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="container">

<!-- BOOK -->
<div class="card">
<h3>Book Appointment</h3>

<form method="POST">

<input type="text" name="name" value="<?php echo $customer_name; ?>">

<select name="service" required>
<option value="">Service</option>
<?php
$services = $conn->query("SELECT * FROM services");
while($s = $services->fetch_assoc()):
?>
<option value="<?= $s['id'] ?>">
    <?= $s['service_name'] ?>
</option>
<?php endwhile; ?>
</select>

<select name="stylist" required>
<option value="">Stylist</option>
<?php
$stylists = $conn->query("SELECT * FROM stylists");
while($st = $stylists->fetch_assoc()):
?>
<option value="<?= $st['id'] ?>">
    <?= $st['name'] ?>
</option>
<?php endwhile; ?>
</select>

<input type="date" name="date" required>
<input type="time" name="time" required>

<button name="submit">Book Now</button>
</form>

</div>

<!-- APPOINTMENTS -->
<div class="card">
<h3>My Appointments</h3>

<table>
<tr>
<th>Service</th>
<th>Stylist</th>
<th>Date</th>
<th>Status</th>
<th>Action</th>
</tr>

<?php while($row = $appointments->fetch_assoc()): ?>

<tr>
<td><?= $row['service_name'] ?></td>
<td><?= $row['stylist_name'] ?></td>
<td><?= $row['appointment_date'] ?></td>

<td>
<span class="status" style="background:
<?= $row['status']=='Pending'?'orange':($row['status']=='Approved'?'green':'red') ?>">
<?= $row['status'] ?>
</span>
</td>

<td>
<a href="?cancel=<?= $row['id'] ?>">Cancel</a> |
<a href="?delete=<?= $row['id'] ?>">Delete</a>
</td>

</tr>

<?php endwhile; ?>

</table>

</div>

</div>

</body>
</html>