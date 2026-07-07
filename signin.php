<?php 
  // connect php to mysql
  $conn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
  if ($conn->connect_error){
    die ("CANT CONNECT TO DATABASE");
  }
  // check if user had made request to backend
  //user click sign in
  if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirmpassword'] ?? '');
    if ($password !== $confirmPassword){
        echo "TRY AGAIN";
        echo "<a href='signin.php'>Sign In</a>";
        exit();
    }
    $stmt = $conn->prepare("INSERT INTO users(name, password) VALUES(?, ?)");
    $stmt->bind_param("ss", $username, $password);
    if ($stmt->execute()){
        header ("Location: login.php");
        exit();
    } else{
        echo "insert fail";
    }
  }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Sign In page</title>
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
        <h3>Sign In</h3>
        <form class="auth-form" method="post">
          <label>Username</label><br>
          <input name="username" class="form-control" required><br>
          <label>Password</label><br>
          <input type="password" name="password" class="form-control" required><br>
          <label>Confirm Password</label><br>
          <input type="password" name="confirmpassword" class="form-control" required><br>
          <button class="btn btn-primary" name="signIn">Sign In</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>