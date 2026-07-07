<?php 
  session_start();
  // clear or delete all session
  session_destroy();
  header("Location: login.php");
?>