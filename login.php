<?php
    session_start();
    $conn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $stmt = $conn->prepare("SELECT name, password FROM users WHERE name = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['username'] = $username;
            header("Location: profile.php");
            exit();
        } else {
            $loginError = "Invalid username or password.";
        }
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login page</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<div class="container py-1">
<?php include 'banner.php'; ?>
<?php include 'menu.php'; ?>
</div>
<div class="main-content">
    <div class="auth-page">
        <div class="auth-card">
            <h3>Login</h3>
            <form class="auth-form" method="post">
                <label>Username</label><br>
                <input name="username" class="form-control" required><br>
                <label>Password</label><br>
                <input type="password" name="password" class="form-control" required><br>
                <button class="btn btn-primary">Login</button>
            </form>
            <p class="auth-link">No account? <a href="signin.php">Sign up now</a></p>
        </div>
    </div>
</div>
<script>
    <?php if (!empty($loginError)) { ?>
        if (confirm("<?php echo addslashes($loginError); ?>\n\nClick OK to try again.")) {
            window.location.href = "login.php";
        }
    <?php } ?>
</script>
</body>
</html>