<?php
session_start();
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Glow Beauty Salon</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

/* ===== HERO ===== */
.hero {
    background: url('assets/img/bg.jpg') no-repeat center center/cover;
    height: 80vh;
    position: relative;
    color: white;
}

.hero::before {
    content: "";
    position: absolute;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
}

.hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    padding-top: 120px;
}

.hero h1 {
    font-size: 55px;
}

.hero p {
    margin-top: 10px;
    color: #eee;
}

/* NAV */
nav {
    display: flex;
    justify-content: space-between;
    padding: 20px 50px;
    position: relative;
    z-index: 2;
}

nav a {
    color: white;
    margin-left: 15px;
    text-decoration: none;
    font-weight: 500;
}

nav a:hover {
    color: #ff4d6d;
}

/* BUTTON */
.btn {
    margin-top: 20px;
    padding: 12px 25px;
    background: #ff4d6d;
    color: white;
    border-radius: 25px;
    display: inline-block;
    text-decoration: none;
}

/* SERVICES */
.services {
    padding: 60px 20px;
    background: #f7f7f7;
    text-align: center;
}

.services h2 {
    font-size: 32px;
    margin-bottom: 30px;
}

.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    max-width: 1000px;
    margin: auto;
}

/* CARD */
.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: 0.3s;
}

.card:hover {
    transform: translateY(-5px);
}

/* IMAGE */
.card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
}

/* CONTENT */
.card-content {
    padding: 15px;
}

.card h3 {
    margin-bottom: 8px;
}

.price {
    color: #ff4d6d;
    font-weight: bold;
    margin-top: 5px;
}

/* FOOTER */
footer {
    text-align: center;
    padding: 20px;
    background: #111;
    color: #aaa;
}
</style>
</head>

<body>

<!-- HERO SECTION -->
<div class="hero">

<nav>
    <div><strong>💇 Glow Salon</strong></div>
    <div>
        <a href="index.php">Home</a>
        <a href="book.php">Book</a>
        <a href="customer_login.php">Login</a>
        <a href="register.php">Register</a>
        <a href="login.php">Admin</a>
    </div>
</nav>

<div class="hero-content">
    <h1>Beauty Starts Here</h1>
    <p>Professional salon services for haircut, coloring, and rebonding</p>

    <?php if (isset($_SESSION['customer_id'])): ?>
        <a href="customer_dashboard.php" class="btn">Go to Dashboard</a>
    <?php else: ?>
        <a href="customer_login.php" class="btn">Book Appointment</a>
    <?php endif; ?>
</div>

</div>

<!-- SERVICES SECTION -->
<div class="services">
    <h2>Our Services</h2>

    <div class="grid">

    <?php
    $result = $conn->query("SELECT * FROM services");

    if ($result && $result->num_rows > 0):
        while ($row = $result->fetch_assoc()):

            // SAFE DATA
            $serviceName = $row['service'] ?? $row['name'] ?? $row['service_name'] ?? 'Service';
            $price = $row['price'] ?? 0;
            $duration = $row['duration'] ?? null;
            $image = $row['image'] ?? 'default.jpg';
    ?>

        <div class="card">

            <img src="assets/img/services/<?php echo htmlspecialchars($image); ?>"
                 onerror="this.src='assets/img/services/rebond.jpg'">

            <div class="card-content">
                <h3><?php echo htmlspecialchars($serviceName); ?></h3>

                <div class="price">
                    ₱<?php echo number_format($price, 2); ?>
                </div>

                <p>
                    <?php echo $duration ? $duration . " hour(s)" : "Standard Service"; ?>
                </p>
            </div>

        </div>

    <?php
        endwhile;
    else:
    ?>
        <p>No services available.</p>
    <?php endif; ?>

    </div>
</div>

<!-- FOOTER -->
<footer>
    &copy; <?php echo date("Y"); ?> Glow Beauty Salon System
</footer>

</body>
</html>