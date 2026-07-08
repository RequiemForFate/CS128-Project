<?php
  session_start();

  $currentUser = $_SESSION['username'] ?? '';

  if ($currentUser !== '' && extension_loaded('mysqli')) {
      $conn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
      if (!$conn->connect_error) {
          $stmt = $conn->prepare("SELECT image_name FROM Artdata WHERE owner = ?");
          $stmt->bind_param("s", $currentUser);
          $stmt->execute();
          $result = $stmt->get_result();
          while ($row = $result->fetch_assoc()) {
              $file = __DIR__ . '/images/' . basename($row['image_name']);
              if (is_file($file)) {
                  @unlink($file);
              }
          }
          $stmt->close();

          $deleteStmt = $conn->prepare("DELETE FROM Artdata WHERE owner = ?");
          $deleteStmt->bind_param("s", $currentUser);
          $deleteStmt->execute();
          $deleteStmt->close();

          $conn->close();
      }
  }

  // clear or delete all session
  session_destroy();
  header("Location: login.php");
?>