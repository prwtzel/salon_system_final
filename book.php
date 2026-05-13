<?php
session_start();
include 'db.php';

if (isset($_POST['book'])) {

    $customer = $_POST['customer'];
    $service  = $_POST['service'];
    $stylist  = $_POST['stylist'];
    $date     = $_POST['date'];
    $time     = $_POST['time'];

    // =========================
    // CHECK IF SLOT EXISTS
    // =========================
    $check = $conn->prepare("
        SELECT * FROM appointments
        WHERE stylist = ?
        AND date = ?
        AND time = ?
        AND status != 'Cancelled'
    ");

    $check->bind_param("sss", $stylist, $date, $time);
    $check->execute();

    $result = $check->get_result();

    if ($result->num_rows > 0) {

        echo "<script>
            alert('Time slot already booked!');
            window.location.href='book.php';
        </script>";

        exit();
    }

    // =========================
    // INSERT BOOKING
    // =========================
    $stmt = $conn->prepare("
        INSERT INTO appointments
        (customer, service, stylist, date, time, status)
        VALUES (?, ?, ?, ?, ?, 'Pending')
    ");

    $stmt->bind_param("sssss",
        $customer,
        $service,
        $stylist,
        $date,
        $time
    );

    if ($stmt->execute()) {

        echo "<script>
            alert('Appointment Booked!');
            window.location.href='book.php';
        </script>";

    } else {

        echo "<script>
            alert('Booking Failed!');
        </script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Appointment</title>
</head>
<body>

<h2>Book Appointment</h2>

<form method="POST">

    <!-- CUSTOMER -->
    <input type="text"
           name="customer"
           placeholder="Customer Name"
           required>
    <br><br>

    <!-- SERVICE -->
    <select name="service" required>
        <option value="">Select Service</option>
        <option value="Haircut">Haircut</option>
        <option value="Hair Color">Hair Color</option>
        <option value="Rebond">Rebond</option>
        <option value="Hairwash">Hairwash</option>
    </select>
    <br><br>

    <!-- STYLIST -->
    <select name="stylist" id="stylist" required>

        <option value="">Select Stylist</option>

        <?php
        $stylists = $conn->query("SELECT * FROM stylists");

        while($row = $stylists->fetch_assoc()) {
        ?>

            <option value="<?= $row['name']; ?>">
                <?= $row['name']; ?>
            </option>

        <?php } ?>

    </select>
    <br><br>

    <!-- DATE -->
    <input type="date"
           name="date"
           id="date"
           required>
    <br><br>

    <!-- TIME SLOT -->
    <select name="time" id="time" required>

        <option value="">Select Time</option>

    </select>
    <br><br>

    <button type="submit" name="book">
        Book Now
    </button>

</form>

<!-- =========================
     AJAX TIME SLOT
========================= -->
<script>

document.getElementById('stylist').addEventListener('change', loadTimes);

document.getElementById('date').addEventListener('change', loadTimes);

function loadTimes() {

    let stylist = document.getElementById('stylist').value;
    let date = document.getElementById('date').value;

    if(stylist == '' || date == '') {
        return;
    }

    let xhr = new XMLHttpRequest();

    xhr.open("POST", "get_times.php", true);

    xhr.setRequestHeader(
        "Content-type",
        "application/x-www-form-urlencoded"
    );

    xhr.onload = function () {

        if(this.status == 200) {

            document.getElementById('time').innerHTML =
                this.responseText;
        }
    };

    xhr.send(
        "stylist=" + encodeURIComponent(stylist)
        + "&date=" + encodeURIComponent(date)
    );
}

</script>

</body>
</html>