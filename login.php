<?php 
    session_start();
    $conn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    if($_SERVER['REQUEST_METHOD'] == "POST"){
        $sql = "SELECT * FROM users";
        $result = mysqli_query($conn, $sql);
        $status = true;
        while($row = $result->fetch_assoc()){
            if($_POST['username'] == $row['name'] && $_POST['password'] == $row['password']){
                $_SESSION['username'] = $_POST['username'];
                $_SESSION['password'] = $_POST['password'];
                header("Location: ProductForm.php");
                exit();
                $status = false;
        }
        if(!$status){
            echo "Invalid username or password.";
            echo "<a href='login.php'>Go back</a>";
        }
    }
    }
?>
<!DOCTYPE html>
<html>
<head>
    <?php include 'header.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<div class="container py-1">
<?php include 'banner.php'; ?>
<?php include 'menu.php'; ?>
<div class="card">
<h3>Login</h3>
<form method="post">
    <label>Username</label><br>
    <input name="username" required><br><br>
    <label>Password</label><br>
    <input type="password" name="password" required><br><br>
    <button class="btn-primary">Login</button>
</form>
<p class="center">No account? <a href="signIn.php">Sign up now</a></p>
</div>
</div>
</body>
</html>