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
            $sql = "SELECT * FROM Artdata";
            $result = mysqli_query($conn, $sql);
            if (!$result) {
                die("Query failed: " . mysqli_error($conn));
            }
        ?>
    <div class=" py-1">
            <?php include 'banner.php'; ?>
            <?php include 'menu.php';?>
    <div class="gallery-container row-cols-5">
        <?php while($row = mysqli_fetch_assoc($result)) { ?>
    <div class="photo">
        <img src="images/<?php echo $row['image_name']; ?>" alt="Image">
        <h3><?php echo $row['ArtName']; ?></h3>
    </div>

    <?php } ?>
</div>
    </body>
</html>