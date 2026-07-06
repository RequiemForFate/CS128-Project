<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Artwork</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <div class="gallery">
        <?php while($row = mysqli_fetch_assoc($result)) { ?>
    <div class="photo">
        <img src="uploads/<?php echo $row['image']; ?>" alt="Image">
        <h3><?php echo $row['image_name']; ?></h3>
        <p><strong>Image ID:</strong> <?php echo $row['image_id']; ?></p>
        <p><?php echo $row['description']; ?></p>
        <p><strong>Uploaded by:</strong> <?php echo $row['user_name']; ?></p>
    </div>

    <?php } ?>
    </div>

</div>
    </body>
</html>