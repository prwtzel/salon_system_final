<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php';

$error = "";

if (isset($_POST['login'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);

    if (!$result) {
        die("SQL Error: " . $conn->error);
    }

    if ($result->num_rows > 0) {

        $_SESSION['admin'] = $username;

        header("Location: admin/dashboard.php");
        exit();

    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin Login - Salon System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
/* RESET */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

/* BACKGROUND */
body {
    height: 100vh;

    background: url('assets/img/bg.jpg') no-repeat center center fixed;
    background-size: cover;

    display: flex;
    justify-content: center;
    align-items: center;

    position: relative;
}

/* DARK OVERLAY */
body::before {
    content: "";
    position: absolute;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.65);
    top: 0;
    left: 0;
}

/* LOGIN CARD */
.card {
    position: relative;
    z-index: 2;

    width: 380px;
    padding: 30px;

    border-radius: 15px;

    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(12px);

    box-shadow: 0 10px 30px rgba(0,0,0,0.4);

    color: white;
    animation: fadeIn 0.8s ease;
}

/* TITLE */
h3 {
    text-align: center;
    margin-bottom: 20px;
    font-size: 26px;
    color: #ff4d6d;
}

/* INPUTS */
input {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;

    border: none;
    border-radius: 8px;

    outline: none;
}

/* LOGIN BUTTON */
.btn-login {
    width: 100%;
    padding: 12px;

    background: #ff4d6d;
    border: none;

    color: white;
    font-weight: bold;

    border-radius: 8px;
    cursor: pointer;

    transition: 0.3s;
}

.btn-login:hover {
    background: #e60039;
    transform: scale(1.03);
}

/* BACK BUTTON */
.btn-back {
    margin-top: 10px;
    width: 100%;
    padding: 12px;

    background: rgba(255,255,255,0.15);
    border: none;

    color: white;
    border-radius: 8px;

    text-decoration: none;
    display: block;
    text-align: center;
}

.btn-back:hover {
    background: rgba(255,255,255,0.25);
}

/* ERROR */
.alert {
    background: rgba(255,0,0,0.2);
    padding: 10px;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 10px;
    color: #ffb3b3;
}

/* LOADER */
.loader {
    display: none;
    text-align: center;
    margin-top: 10px;
}

.dot {
    height: 8px;
    width: 8px;
    margin: 0 2px;
    background-color: white;
    border-radius: 50%;
    display: inline-block;
    animation: bounce 1.2s infinite ease-in-out;
}

.dot:nth-child(2) { animation-delay: 0.2s; }
.dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes bounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

@keyframes fadeIn {
    from {opacity: 0; transform: translateY(20px);}
    to {opacity: 1; transform: translateY(0);}
}

/* LOGO */
.logo {
    text-align: center;
    font-size: 20px;
    margin-bottom: 10px;
    color: white;
}

.logo span {
    color: #ff4d6d;
    font-weight: bold;
}
</style>
</head>

<body>

<div class="card">

    <div class="logo">💇 <span>Glow Salon</span> Admin</div>

    <h3>Admin Login</h3>

    <?php if (!empty($error)): ?>
        <div class="alert"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" onsubmit="showLoader()">

        <input type="text" name="username" placeholder="Username" required>

        <input type="password" name="password" placeholder="Password" required>

        <button type="submit" name="login" class="btn-login">Login</button>

        <div class="loader" id="loader">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>

    </form>

    <a href="index.php" class="btn-back">⬅ Back to Home</a>

</div>

<script>
function showLoader() {
    document.getElementById("loader").style.display = "block";
}
</script>

</body>
</html>