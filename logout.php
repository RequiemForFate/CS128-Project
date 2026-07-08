<?php
  session_start();

  // Remove uploaded artwork data and image files when the user logs out.
  if (extension_loaded('mysqli')) {
      $conn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
      if (!$conn->connect_error) {
          $conn->query("DELETE FROM Artdata");
          $conn->close();
      }
  }

  $uploadDir = __DIR__ . '/images';
  if (is_dir($uploadDir)) {
      foreach (glob($uploadDir . '/*') as $file) {
          if (is_file($file)) {
              @unlink($file);
          }
      }
  }

  // clear or delete all session
  session_destroy();
  header("Location: login.php");
?>