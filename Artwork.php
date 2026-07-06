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
            $conn = mysqli_connect("localhost", "root", "", "ArtShopDB",3306);
            if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
            }
            $sql = "SELECT * FROM gallery";
            $result = mysqli_query($conn, $sql);
            if (!$result) {
                die("Query failed: " . mysqli_error($conn));
            }
        ?>
    <div class="gallery">
        <?php while($row = mysqli_fetch_assoc($result)) { ?>
    <div class="photo">
        <img src="image/<?php echo $row['ImageFile']; ?>" alt="Image">
        <h3><?php echo $row['ArtName']; ?></h3>
        <p><strong>Image ID:</strong> <?php echo $row['ArtID']; ?></p>
        <p><?php echo $row['ArtDescription']; ?></p>
    </div>

    <?php } ?>
</div>
    </body>
</html>