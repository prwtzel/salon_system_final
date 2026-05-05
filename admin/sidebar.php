<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Salon Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<style>

/* =========================
   LOCK SCROLL WHEN MODAL OPEN
========================= */
body.modal-open {
    overflow: hidden;
}

/* =========================
   OVERLAY (REAL MODAL BACKDROP)
========================= */
#overlay {
    position: fixed;
    inset: 0;

    background: rgba(0,0,0,0.6);

    opacity: 0;
    visibility: hidden;

    transition: 0.3s ease;

    z-index: 999;
}

/* ACTIVE OVERLAY */
#overlay.active {
    opacity: 1;
    visibility: visible;
}

/* =========================
   SIDEBAR (MODAL STYLE)
========================= */
#sidebar {
    position: fixed;
    top: 0;
    left: 0;

    width: 280px;
    height: 100vh;

    background: rgba(20,20,20,0.98);
    backdrop-filter: blur(12px);

    transform: translateX(-100%);
    transition: 0.3s ease;

    z-index: 1000;

    padding: 20px;
    color: white;
}

/* OPEN STATE */
#sidebar.active {
    transform: translateX(0);
}

/* TITLE */
#sidebar h4 {
    text-align: center;
    margin-bottom: 25px;
}

/* LINKS */
#sidebar a {
    display: block;
    padding: 10px 12px;
    margin-bottom: 8px;

    color: white;
    text-decoration: none;

    border-radius: 8px;
    transition: 0.2s;
}

#sidebar a:hover {
    background: rgba(255,255,255,0.15);
}

/* LOGOUT */
.logout {
    background: #ff4d6d;
    margin-top: 20px;
    text-align: center;
}

/* BUTTON */
.menu-btn {
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1100;
}

</style>
</head>

<body>

<!-- 🔘 OPEN BUTTON -->
<button class="btn btn-dark menu-btn" onclick="openSidebar()">
    <i class="bi bi-list"></i>
</button>

<!-- 🌑 OVERLAY -->
<div id="overlay" onclick="closeSidebar()"></div>

<!-- 📌 SIDEBAR -->
<div id="sidebar">
    <h4>💇 Salon Admin</h4>

    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="appointments.php"><i class="bi bi-calendar-check"></i> Appointments</a>
    <a href="services.php"><i class="bi bi-scissors"></i> Services</a>
    <a href="customer.php"><i class="bi bi-people"></i> Customers</a>
    <a href="stylists.php"><i class="bi bi-person-badge"></i> Stylists</a>
    <a href="reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
    <a href="walk_in_booking.php">🚶 Walk-in Booking</a>

    <a href="../logout.php" class="logout">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</div>

<script>
function openSidebar() {
    document.getElementById("sidebar").classList.add("active");
    document.getElementById("overlay").classList.add("active");
    document.body.classList.add("modal-open");
}

function closeSidebar() {
    document.getElementById("sidebar").classList.remove("active");
    document.getElementById("overlay").classList.remove("active");
    document.body.classList.remove("modal-open");
}
</script>

</body>
</html>