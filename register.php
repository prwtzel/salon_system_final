<?php
session_start();
include 'db.php';

$success = "";
$error = "";

if (isset($_POST['register'])) {

    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!empty($name) && !empty($username) && !empty($password)) {

        // 🔐 Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if username exists
        $check = $conn->query("SELECT * FROM customers WHERE username='$username'");

        if ($check->num_rows > 0) {
            $error = "Username already exists!";
        } else {

            // Insert user
            $conn->query("INSERT INTO customers (name, username, password) 
                          VALUES ('$name','$username','$hashed_password')");

            $success = "✅ Registration successful! You can now login.";
        }

    } else {
        $error = "All fields are required!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
            height: 100vh;
        }

        .card {
            border-radius: 20px;
            background: rgba(255,255,255,0.95);
        }

        .btn-salon {
            background: #ff6f91;
            border: none;
            color: white;
        }

        .btn-salon:hover {
            background: #ff3e6c;
        }
    </style>
</head>

<body class="d-flex justify-content-center align-items-center">

<div class="card shadow-lg p-4" style="width:380px;">

    <h3 class="text-center mb-3">💇 Register</h3>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success text-center"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">

        <input type="text" name="name" class="form-control mb-3" placeholder="Full Name" required>

        <input type="text" name="username" class="form-control mb-3" placeholder="Username" required>

        <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>

        <button name="register" class="btn btn-salon w-100">
            Register
        </button>

    </form>

    <p class="text-center mt-3">
        Already have an account? 
        <a href="customer_login.php">Login</a>
    </p>

</div>

</body>
</html>