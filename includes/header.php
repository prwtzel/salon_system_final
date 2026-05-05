<!DOCTYPE html>
<html>
<head>
    <title>Salon System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/salon_system/assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="d-flex">

    <!-- ✅ SIDEBAR -->
    <?php include 'includes/header.php'; ?>
<link href="/salon_system/assets/css/style.css" rel="stylesheet">

<style>
/* ===== BACKGROUND ===== */
body {
    background: linear-gradient(120deg, #f8f9fa, #e9ecef);
}

/* ===== HERO GLASS STYLE ===== */
.hero {
    background: url('assets/img/salon-bg.jpg') center/cover no-repeat;
    padding: 90px 20px;
    border-radius: 20px;
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: "";
    position: absolute;
    top:0; left:0; right:0; bottom:0;
    background: rgba(0,0,0,0.55);
}

.hero-content {
    position: relative;
    color: white;
    text-align: center;
}

/* ===== GLASS BUTTONS ===== */
.btn-glass {
    backdrop-filter: blur(10px);
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 10px 18px;
    border-radius: 50px;
    margin: 5px;
    transition: 0.3s;
}

.btn-glass:hover {
    background: white;
    color: black;
}

/* ===== SERVICE CARD ===== */
.service-card {
    background: white;
    border-radius: 18px;
    padding: 20px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    transition: 0.3s;
}

.service-card:hover {
    transform: scale(1.05);
}

/* ===== SECTION TITLE ===== */
.title {
    font-weight: bold;
    margin: 40px 0 20px;
    text-align: center;
}
</style>

<!-- HERO -->
<div class="container mt-4">

    <div class="hero">

        <div class="hero-content">

            <h1>💇 Luxury Salon Experience</h1>

            <p>Your beauty deserves professional care and elegance.</p>

            <div class="mt-4">

                <a href="book.php" class="btn-glass">💇 Book Appointment</a>

                <a href="login.php" class="btn-glass">🔐 Admin</a>

                <a href="customer_login.php" class="btn-glass">👤 Login</a>

                <a href="register.php" class="btn-glass">📝 Register</a>

            </div>

        </div>

    </div>

</div>

<!-- SERVICES -->
<div class="container">

    <h2 class="title">✨ Our Premium Services</h2>

    <div class="row">

        <?php
        include 'db.php';
        $services = $conn->query("SELECT * FROM services");
        while($s = $services->fetch_assoc()):
        ?>

        <div class="col-md-4 mb-4">

            <div class="service-card text-center">

                <h4><?= $s['service_name'] ?></h4>

                <p class="text-muted">Professional salon service</p>

                <h5 class="text-danger">₱<?= $s['price'] ?></h5>

                <a href="book.php" class="btn btn-outline-dark btn-sm mt-3">
                    Book Now
                </a>

            </div>

        </div>

        <?php endwhile; ?>

    </div>

</div>

<!-- FOOTER TEXT -->
<div class="container text-center mt-5 mb-5">
    <h4>🌸 Beauty • Confidence • Elegance</h4>
    <p class="text-muted">We make your style shine brighter.</p>
</div>

<?php include 'includes/footer.php'; ?>