<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

/* =========================
   TOTAL CUSTOMERS TODAY
========================= */

$result1 = $conn->query("
    SELECT COUNT(*) AS total_customers 
    FROM appointments 
    WHERE LOWER(TRIM(status)) != 'cancelled'
");

$customers = $result1->fetch_assoc()['total_customers'] ?? 0;


/* =========================
   REVENUE TODAY
========================= */

$result2 = $conn->query("
    SELECT COALESCE(
        SUM(CAST(services.price AS DECIMAL(10,2))),
        0
    ) AS total_revenue

    FROM appointments

    LEFT JOIN services
    ON appointments.service_id = services.id

    WHERE LOWER(TRIM(appointments.status))='completed'
");

if (!$result2) {
    die("Revenue Query Error: " . $conn->error);
}

$row2 = $result2->fetch_assoc();

$revenue = $row2['total_revenue'] ?? 0;


/* =========================
   TOTAL APPROVED
========================= */

$result3 = $conn->query("
    SELECT COUNT(*) as total
    FROM appointments
    WHERE LOWER(TRIM(status))='approved'
");

$totalApproved = $result3->fetch_assoc()['total'] ?? 0;


/* =========================
   TOTAL REVENUE
========================= */

$result4 = $conn->query("
    SELECT COALESCE(
        SUM(CAST(services.price AS DECIMAL(10,2))),
        0
    ) as total

    FROM appointments

    LEFT JOIN services
    ON appointments.service_id = services.id

    WHERE LOWER(TRIM(appointments.status))='completed'
");

if (!$result4) {
    die("Total Revenue Query Error: " . $conn->error);
}

$row4 = $result4->fetch_assoc();

$totalRevenue = $row4['total'] ?? 0;


/* =========================
   UPCOMING BOOKINGS
========================= */

$result5 = $conn->query("
    SELECT COUNT(*) as total
    FROM appointments
    WHERE LOWER(TRIM(status))='approved'
");

$upcoming = $result5->fetch_assoc()['total'] ?? 0;

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Salon Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>

/* ── Base ── */
*, *::before, *::after { box-sizing: border-box; }

body {
    margin: 0;
    font-family: 'Inter', 'Segoe UI', sans-serif;
    background: url('../assets/img/admin.jpg') no-repeat center center fixed;
    background-size: cover;
}

body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg, rgba(10,10,20,0.72) 0%, rgba(30,10,40,0.65) 100%);
    z-index: 0;
}

.sidebar, .main { position: relative; z-index: 2; }

/* ── Sidebar ── */
.sidebar {
    width: 265px;
    height: 100vh;
    position: fixed;
    background: rgba(15,10,25,0.78);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    border-right: 1px solid rgba(255,255,255,0.07);
    color: white;
    padding: 28px 18px 24px;
    display: flex;
    flex-direction: column;
}

.sidebar-brand {
    text-align: center;
    padding-bottom: 28px;
    margin-bottom: 8px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.sidebar-brand .brand-icon {
    font-size: 36px;
    display: block;
    margin-bottom: 6px;
}

.sidebar-brand h4 {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    letter-spacing: 0.5px;
    color: rgba(255,255,255,0.9);
}

.sidebar-brand p {
    margin: 2px 0 0;
    font-size: 11px;
    color: rgba(255,255,255,0.4);
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

.nav-section {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.3);
    padding: 18px 10px 8px;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 14px;
    margin-bottom: 3px;
    color: rgba(255,255,255,0.65);
    text-decoration: none;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 500;
    transition: background 0.2s, color 0.2s;
    position: relative;
}

.sidebar a i {
    font-size: 17px;
    flex-shrink: 0;
    width: 20px;
    text-align: center;
}

.sidebar a:hover {
    background: rgba(255,255,255,0.09);
    color: #fff;
}

.sidebar a.active {
    background: linear-gradient(90deg, rgba(180,100,255,0.25), rgba(100,160,255,0.15));
    color: #fff;
    border-left: 3px solid #c084fc;
    padding-left: 11px;
}

.sidebar-logout {
    margin-top: auto;
    padding-top: 16px;
    border-top: 1px solid rgba(255,255,255,0.08);
}

.sidebar-logout a {
    background: rgba(255,70,100,0.15) !important;
    color: #ff7096 !important;
    border: 1px solid rgba(255,70,100,0.25);
    justify-content: center;
    font-weight: 600;
}

.sidebar-logout a:hover {
    background: rgba(255,70,100,0.28) !important;
    color: #ffaabb !important;
}

/* ── Main ── */
.main {
    margin-left: 265px;
    padding: 32px 30px;
    min-height: 100vh;
}

/* ── Page header ── */
.page-header {
    margin-bottom: 28px;
}

.page-header .greeting {
    font-size: 13px;
    color: rgba(255,255,255,0.45);
    margin-bottom: 4px;
    letter-spacing: 0.3px;
}

.page-header h2 {
    font-size: 24px;
    font-weight: 700;
    color: #fff;
    margin: 0;
    letter-spacing: -0.3px;
}

/* ── Section label ── */
.section-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 1.8px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.35);
    margin-bottom: 14px;
    margin-top: 32px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(255,255,255,0.08);
}

/* ── Stat cards ── */
.card-box {
    background: rgba(255,255,255,0.07);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,0.11);
    border-radius: 18px;
    padding: 26px 24px 22px;
    color: white;
    position: relative;
    overflow: hidden;
    transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.25s ease;
}

.card-box::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 18px;
    opacity: 0;
    transition: opacity 0.25s ease;
}

.card-box:hover {
    transform: translateY(-4px);
    background: rgba(255,255,255,0.11);
    box-shadow: 0 20px 40px rgba(0,0,0,0.35);
}

.card-box:hover::before { opacity: 1; }

/* Accent top stripe */
.card-box .card-accent {
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 18px 18px 0 0;
}

.accent-purple .card-accent { background: linear-gradient(90deg, #c084fc, #818cf8); }
.accent-emerald .card-accent { background: linear-gradient(90deg, #34d399, #06b6d4); }
.accent-amber .card-accent  { background: linear-gradient(90deg, #fbbf24, #f97316); }
.accent-pink .card-accent   { background: linear-gradient(90deg, #f472b6, #c084fc); }
.accent-blue .card-accent   { background: linear-gradient(90deg, #60a5fa, #818cf8); }

.card-box .card-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.45);
    margin-bottom: 14px;
    margin-top: 4px;
}

.card-box .card-value {
    font-size: 38px;
    font-weight: 700;
    letter-spacing: -1px;
    line-height: 1;
    color: #fff;
}

.card-box .card-sub {
    font-size: 12px;
    color: rgba(255,255,255,0.3);
    margin-top: 8px;
}

.card-box .card-icon-wrap {
    position: absolute;
    right: 20px;
    bottom: 18px;
    font-size: 42px;
    opacity: 0.12;
    line-height: 1;
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        flex-direction: column;
    }
    .main { margin-left: 0; padding: 20px 16px; }
    .sidebar-logout { padding-top: 12px; margin-top: 12px; }
}

</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="sidebar-brand">
        <span class="brand-icon">💇</span>
        <h4>Salon Admin</h4>
        <p>Management Portal</p>
    </div>

    <div class="nav-section">Menu</div>

    <a href="dashboard.php" class="active">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <a href="appointments.php">
        <i class="bi bi-calendar-check"></i> Appointments
    </a>

    <a href="services.php">
        <i class="bi bi-scissors"></i> Services
    </a>

    <a href="customers.php">
        <i class="bi bi-people"></i> Customers
    </a>

    <a href="stylists.php">
        <i class="bi bi-person-badge"></i> Stylists
    </a>

    <a href="reports.php">
        <i class="bi bi-bar-chart"></i> Reports
    </a>

    <a href="walk_in_booking.php">
        <i class="bi bi-person-walking"></i> Walk-in Booking
    </a>

    <div class="sidebar-logout">
        <a href="../logout.php">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

</div>

<!-- MAIN -->
<div class="main">

    <div class="page-header">
        <p class="greeting">Welcome back, Admin</p>
        <h2>📊 Dashboard Overview</h2>
    </div>

    <!-- OVERVIEW STATS -->
    <div class="section-label">All-time stats</div>

    <div class="row g-3 mb-2">

        <div class="col-md-4">
            <div class="card-box accent-purple">
                <div class="card-accent"></div>
                <div class="card-label">Total Approved</div>
                <div class="card-value"><?= $totalApproved ?></div>
                <div class="card-sub">All approved appointments</div>
                <div class="card-icon-wrap"><i class="bi bi-calendar-check"></i></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-box accent-emerald">
                <div class="card-accent"></div>
                <div class="card-label">Total Revenue</div>
                <div class="card-value">₱<?= number_format($totalRevenue, 2) ?></div>
                <div class="card-sub">From completed services</div>
                <div class="card-icon-wrap"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-box accent-amber">
                <div class="card-accent"></div>
                <div class="card-label">Upcoming</div>
                <div class="card-value"><?= $upcoming ?></div>
                <div class="card-sub">Pending confirmed bookings</div>
                <div class="card-icon-wrap"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>

    </div>

    <!-- TODAY STATS -->
    <div class="section-label">Today's snapshot</div>

    <div class="row g-3">

        <div class="col-md-6">
            <div class="card-box accent-blue">
                <div class="card-accent"></div>
                <div class="card-label">Customers Today</div>
                <div class="card-value"><?= $customers ?></div>
                <div class="card-sub">Non-cancelled appointments</div>
                <div class="card-icon-wrap"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card-box accent-pink">
                <div class="card-accent"></div>
                <div class="card-label">Revenue Today</div>
                <div class="card-value">₱<?= number_format($revenue, 2) ?></div>
                <div class="card-sub">From today's completed services</div>
                <div class="card-icon-wrap"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
        </div>

    </div>

</div>

</body>
</html>