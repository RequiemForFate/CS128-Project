<?php
    session_start();

    $conn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $signupError = '';
    $signupSuccess = false;

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirmpassword'] ?? '');

        // Validation
        if (empty($username) || empty($password) || empty($confirmPassword)) {
            $signupError = "All fields are required.";
        } elseif (strlen($password) < 6) {
            $signupError = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirmPassword) {
            $signupError = "Passwords do not match.";
        } else {
            // Check if username already exists
            $checkStmt = $conn->prepare("SELECT name FROM users WHERE name = ?");
            $checkStmt->bind_param("s", $username);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                $signupError = "Username is already taken.";
            } else {
                // Insert new user
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
    <!-- Header Section -->
    <div class="container py-1"> <!-- container start -->
        <?php include 'banner.php'; ?>
        <?php include 'menu.php'; ?>
    </div> <!-- end container -->

    <!-- Main Content -->
    <div class="main-content"> <!-- main-content start -->
        <div class="auth-page"> <!-- auth-page start -->
            <div class="auth-card"> <!-- auth-card start -->
                <h3>Sign Up</h3>

                <!-- Error Message -->
                <?php if (!empty($signupError)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($signupError); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Sign Up Form -->
                <form class="auth-form" method="post"> <!-- auth-form start -->
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
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
                </form> <!-- end auth-form -->

                <!-- Login Link -->
                <p class="auth-link">
                    Already have an account? <a href="login.php">Login here</a>
                </p>
            </div> <!-- end auth-card -->
        </div> <!-- end auth-page -->
    </div> <!-- end main-content -->
</body>
</html>

<?php
    $conn->close();
?>