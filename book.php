<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Glow Beauty Salon</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
/* ===== RESET ===== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

/* ===== HERO BACKGROUND ===== */
.hero {
    background: url('assets/img/bg.jpg') no-repeat center center/cover;
    height: 100vh;
    position: relative;
    color: white;
}

/* DARK OVERLAY */
.hero::before {
    content: "";
    position: absolute;
    top:0; left:0;
    width:100%; height:100%;
    background: rgba(0,0,0,0.6);
}

/* CONTENT */
.hero-content {
    position: relative;
    z-index: 2;
    height: 100%;
    display: flex;
    flex-direction: column;
}

/* NAVBAR */
nav {
    display: flex;
    justify-content: space-between;
    padding: 20px 60px;
    align-items: center;
}

.logo {
    font-size: 24px;
    font-weight: bold;
    color: #ffb6c1;
}

nav a {
    color: white;
    text-decoration: none;
    margin-left: 20px;
    font-weight: 500;
    transition: 0.3s;
}

nav a:hover {
    color: #ffb6c1;
}

/* HERO TEXT */
.center {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
}

.center h1 {
    font-size: 60px;
    color: #fff;
}

.center p {
    font-size: 18px;
    margin-top: 10px;
    color: #eee;
}

/* BUTTONS */
.btn {
    margin-top: 25px;
    padding: 12px 25px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: bold;
    display: inline-block;
}

.btn-pink {
    background: #ff4d6d;
    color: white;
}

.btn-pink:hover {
    background: #e60039;
}

/* SERVICES SECTION */
.services {
    padding: 60px 20px;
    text-align: center;
    background: #f8f8f8;
}

.services h2 {
    font-size: 32px;
    margin-bottom: 30px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    max-width: 1000px;
    margin: auto;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: 0.3s;
}

.card:hover {
    transform: translateY(-5px);
}

.card h3 {
    margin-bottom: 10px;
    color: #ff4d6d;
}

/* FOOTER */
footer {
    text-align: center;
    padding: 20px;
    background: #111;
    color: #aaa;
}

/* MOBILE */
@media(max-width:768px){
    .center h1 {
        font-size: 36px;
    }

    nav {
        flex-direction: column;
    }
}
</style>
</head>

<body>

<!-- HERO -->
<div class="hero">

<div class="hero-content">

<!-- NAV -->
<nav>
    <div class="logo">💇 Glow Salon</div>
    <div>
        <a href="index.php">Home</a>
        <a href="book.php">Book</a>
        <a href="customer_login.php">Login</a>
        <a href="register.php">Register</a>
        <a href="login.php">Admin</a>
    </div>
</nav>

<!-- CENTER TEXT -->
<div class="center">
    <h1>Beauty Starts Here</h1>
    <p>Professional salon services for hair, nails, and spa treatments</p>

    <?php if (isset($_SESSION['customer_id'])): ?>
        <a href="customer_dashboard.php" class="btn btn-pink">Go to Dashboard</a>
    <?php else: ?>
        <a href="customer_login.php" class="btn btn-pink">Book Appointment</a>
    <?php endif; ?>
</div>

</div>
</div>

<!-- SERVICES -->
<div class="services">
    <h2>Our Services</h2>

    <div class="cards">

        <div class="card">
            <h3>💇 Hair Styling</h3>
            <p>Trendy cuts, coloring, and treatments for all hair types.</p>
        </div>

        <div class="card">
            <h3>💅 Nail Care</h3>
            <p>Manicure, pedicure, and nail art designs.</p>
        </div>

        <div class="card">
            <h3>🧖 Spa & Relax</h3>
            <p>Relaxing massage and beauty treatments.</p>
        </div>

    </div>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Glow Beauty Salon System
</footer>

</body>
</html>