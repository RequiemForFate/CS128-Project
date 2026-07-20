<?php
session_start();
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $loginError = "Username and password are required.";
    } else {
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
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link href="style.css" rel="stylesheet">
    </head>
<body>
    <div class="main-content-auth">
        <div class="auth-page">
            <div class="auth-card">
                <h3>Login</h3>
                <?php if (!empty($loginError)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($loginError); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <form class="auth-form" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                <p class="auth-link">
                    No account? <a href="signin.php">Sign up now</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>