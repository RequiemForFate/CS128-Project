<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Artwork</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link href="style.css" rel="stylesheet">

    </head>
    <body>
        <?php
            session_start();
            $currentUser = $_SESSION['username'] ?? '';
            if ($currentUser === '') {
                header('Location: login.php');
                exit();
            }

            $conn = mysqli_connect("localhost", "root", "", "ArtShopDB",3306);
            if (!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            }

            $stmt = $conn->prepare("SELECT * FROM Artdata WHERE owner = ? ORDER BY ArtID DESC");
            $stmt->bind_param("s", $currentUser);
            $stmt->execute();
            $result = $stmt->get_result();
        ?>
    <div class=" py-1">
            <?php include 'menu.php'; ?>
            <?php include 'banner.php'; ?>
    <div class="gallery-container row-cols-5">
        <?php while($row = mysqli_fetch_assoc($result)) { ?>
    <div class="photo">
        <img src="images/<?php echo htmlspecialchars($row['image_name']); ?>" alt="Image">
        <h3><?php echo htmlspecialchars($row['ArtName']); ?></h3>
        <p><strong>Image ID:</strong> <?php echo htmlspecialchars($row['ArtID']); ?></p>
        <p><?php echo htmlspecialchars($row['ArtDes']); ?></p>
    </div>

    <?php } ?>
</div>
<?php
    $stmt->close();
    $conn->close();
?>
    </body>
</html>