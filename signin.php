<?php 
  // connect php to mysql
  $conn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
  if ($conn->connect_error){
    die ("CANT CONNECT TO DATABASE");
  }
  // check if user had made request to backend
  //user click sign in
  if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmpassword'];
    if ($password !== $confirmPassword){
        echo "TRY AGAIN";
        echo "<a href= 'signIn.php'>Sign In</a>";
    }
    $sql = "INSERT into users(name, password)
    VALUE('$username', '$password')";
    if (mysqli_query($conn, $sql)){
        header ("location: login.php");
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
  <?php include 'header.php'; ?>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="container">
    <?php include 'banner.php'; ?>
    <?php include 'menu.php'; ?>
    <div class="card">
      <h3>Sign In</h3>
        <form method="post">
            <label>Username</label><br>
            <input name="username" required><br><br>
            <label>Password</label><br>
            <input type="password" name="password" required><br><br>
            <label>Confirm Password</label><br>
            <input type="password" name="confirmpassword" required><br><br>
            <button class="btn-primary" name="signIn">Sign In</button>
        </form>
    </div>
  </div>
</body>
</html>