<?php
session_start();
include 'db.php';

$error = "";

if (isset($_POST['login'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    // Get user from database
    $stmt = $conn->prepare("SELECT * FROM customers WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();

    // NO HASHING (plain password comparison)
    if ($customer && $customer['password'] == $password) {

        $_SESSION['user_id'] = $customer['id'];
        $_SESSION['name'] = $customer['name'];

        header("Location: customer_dashboard.php");
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
<title>Customer Login - Salon System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>

/* RESET */
*{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

/* BACKGROUND IMAGE */
body{
    height: 100vh;
    background: url('assets/img/bg.jpg') no-repeat center center fixed;
    background-size: cover;

    display: flex;
    justify-content: center;
    align-items: center;

    position: relative;
}

/* DARK OVERLAY */
body::before{
    content: "";
    position: absolute;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    top: 0;
    left: 0;
}

/* LOGIN BOX */
.login-box{
    position: relative;
    z-index: 2;

    width: 360px;
    padding: 30px;

    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(12px);

    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.4);

    color: white;
    text-align: center;
}

/* LOGO */
.logo{
    font-size: 24px;
    font-weight: bold;
    color: #ff4d6d;
    margin-bottom: 10px;
}

/* TITLE */
.login-box h2{
    margin-bottom: 5px;
}

.login-box p{
    font-size: 14px;
    color: #ddd;
    margin-bottom: 20px;
}

/* INPUTS */
input{
    width: 100%;
    padding: 12px;
    margin: 10px 0;

    border: none;
    border-radius: 8px;

    outline: none;
}

/* PASSWORD TOGGLE */
.password-box{
    position: relative;
}

.password-box input{
    padding-right: 45px;
}

.toggle-password{
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #555;
    font-size: 18px;
    user-select: none;
}

/* BUTTON */
button{
    width: 100%;
    padding: 12px;

    background: #ff4d6d;
    color: white;

    border: none;
    border-radius: 8px;

    font-weight: bold;
    cursor: pointer;

    transition: 0.3s;
}

button:hover{
    background: #e60039;
}

/* ERROR */
.error{
    background: rgba(255,0,0,0.2);
    color: #ffb3b3;

    padding: 10px;
    border-radius: 8px;

    margin-bottom: 10px;
}

/* LINK */
.back{
    display: block;
    margin-top: 15px;
    color: #fff;
    text-decoration: none;
    font-size: 14px;
}

.back:hover{
    color: #ff4d6d;
}

/* MOBILE */
@media(max-width: 400px){
    .login-box{
        width: 90%;
    }
}

</style>
</head>

<body>

<div class="login-box">

    <div class="logo">💇 Glow Salon</div>

    <h2>Customer Login</h2>

    <p>Book your beauty appointment anytime</p>

    <?php if (!empty($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">

        <input 
            type="text" 
            name="username" 
            placeholder="Username" 
            required
        >

        <div class="password-box">

            <input 
                type="password" 
                id="password"
                name="password" 
                placeholder="Password" 
                required
            >

            <span class="toggle-password" onclick="togglePassword()">
                👁
            </span>

        </div>

        <button type="submit" name="login">
            Login
        </button>

    </form>

    <a href="index.php" class="back">
        ← Back to Home
    </a>

</div>

<script>

function togglePassword(){

    const password = document.getElementById("password");

    if(password.type === "password"){
        password.type = "text";
    }else{
        password.type = "password";
    }

}

</script>

</body>
</html>