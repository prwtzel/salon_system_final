<?php
include '../db.php';

$id = $_GET['id'];
$data = $conn->query("SELECT * FROM appointments WHERE id=$id")->fetch_assoc();

if(isset($_POST['update'])){
$name=$_POST['name'];
$conn->query("UPDATE appointments SET customer_name='$name' WHERE id=$id");
header("Location: appointments.php");
}
?>

<form method="POST">
<input name="name" value="<?= $data['customer_name'] ?>">
<button name="update">Update</button>
</form>