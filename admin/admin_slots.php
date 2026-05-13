<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

$notif = "";

/* ADD SLOT */
if (isset($_POST['add'])) {
    $stylist = $_POST['stylist'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    $check = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM stylist_slots
        WHERE stylist_id = ?
        AND slot_date = ?
        AND slot_time = ?
    ");

    $check->bind_param("iss", $stylist, $date, $time);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();

    if ($row['total'] > 0) {
        $notif = "⚠️ Slot already exists!";
    } else {

        $insert = $conn->prepare("
            INSERT INTO stylist_slots (stylist_id, slot_date, slot_time)
            VALUES (?, ?, ?)
        ");

        $insert->bind_param("iss", $stylist, $date, $time);

        if ($insert->execute()) {
            $notif = "✅ Slot created successfully!";
        } else {
            $notif = "❌ Error: " . $conn->error;
        }
    }
}
?>

<h2>Admin - Create Time Slots</h2>

<p style="color:green;"><?php echo $notif; ?></p>

<form method="POST">

    <select name="stylist" required>
        <option value="">Select Stylist</option>
        <?php
        $stylist = $conn->query("SELECT * FROM stylists");
        while ($s = $stylist->fetch_assoc()):
        ?>
            <option value="<?= $s['id'] ?>"><?= $s['name'] ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <input type="date" name="date" required><br><br>

    <input type="time" name="time" required><br><br>

    <button type="submit" name="add">Create Slot</button>

</form>