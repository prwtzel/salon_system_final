<?php
session_start();

// If user confirms logout
if (isset($_POST['confirm_logout'])) {

    // Unset all session variables
    $_SESSION = [];

    // Destroy session
    session_destroy();

    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Prevent caching
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Redirect to login
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Logout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex justify-content-center align-items-center" style="height:100vh; background:#f4f6f9;">

<div class="card p-4 text-center shadow" style="width:350px;">
    <h4>Are you sure you want to logout?</h4>

    <form method="POST">
        <button name="confirm_logout" class="btn btn-danger w-100 mt-3">
            Yes, Logout
        </button>

        <button name="cancel" class="btn btn-secondary w-100 mt-2">
            Cancel
        </button>
    </form>
</div>

</body>
</html>