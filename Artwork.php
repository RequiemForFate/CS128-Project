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

        ?>
    <div class="py-1">
        <?php include 'banner.php'; ?>
        <?php include 'menu.php'; ?>
        <?php
            $artstmt = $conn->prepare("SELECT * FROM Artdata ORDER BY ArtID DESC");
            $artstmt->execute();
            $result = $artstmt->get_result();
        ?>
        <div class="main-content container py-4">

            <div class="gallery row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
                <?php while($row = mysqli_fetch_assoc($result)) { ?>
                <div class="gallery-card">
                    <a href="view_art.php?id=<?php echo $row['ArtID']; ?>">
                        <img
                            src="images/<?php echo htmlspecialchars($row['image_name']); ?>"
                            class="gallery-img"
                            alt="<?php echo htmlspecialchars($row['ArtName'] ?: 'Artwork'); ?>">
                    </a>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
<?php
    $artstmt->close();
    $conn->close();
?>
    </body>
</html>