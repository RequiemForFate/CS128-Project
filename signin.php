<?php
session_start();
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$signupError = '';
$signupSuccess = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirmpassword'] ?? '');

    if (empty($username) || empty($password) || empty($confirmPassword)) {
        $signupError = "All fields are required.";
    } elseif (strlen($password) < 6) {
        $signupError = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirmPassword) {
        $signupError = "Passwords do not match.";
    } elseif (strpos($username, ' ') !== false) {
        $signupError = "Username cannot contain spaces.";
    } else {
        $checkStmt = $conn->prepare("SELECT name FROM users WHERE name = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $signupError = "Username is already taken.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users(name, password) VALUES(?, ?)");
            $stmt->bind_param("ss", $username, $password);
            if ($stmt->execute()) {
                $signupSuccess = true;
                $_SESSION['username'] = $username;
                header("Location: profile.php");
                exit();
            } else {
                $signupError = "Sign up failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="main-content-auth">
        <div class="auth-page">
            <div class="auth-card">
                <h3>Sign Up</h3>
                <?php if (!empty($signupError)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($signupError); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <form class="auth-form" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username (no spaces)</label>
                        <input type="text" 
                               id="username"
                               name="username" 
                               class="form-control" 
                               required
                               autofocus
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" 
                               id="password"
                               name="password" 
                               class="form-control" 
                               required>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="mb-4">
                        <label for="confirmpassword" class="form-label">Confirm Password</label>
                        <input type="password" 
                               id="confirmpassword"
                               name="confirmpassword" 
                               class="form-control" 
                               required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                </form>
                <p class="auth-link">
                    Already have an account? <a href="login.php">Login here</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>