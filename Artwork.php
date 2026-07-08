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
            // Use a local connection variable to avoid interfering with other includes
            $artConn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
            if ($artConn->connect_error) {
                die("Connection failed: " . $artConn->connect_error);
            }

            $sql = "SELECT * FROM Artdata";
            $result = $artConn->query($sql);
            if (!$result) {
                die("Query failed: " . $artConn->error);
            }
        ?>
    <div class="py-1">
            <?php include 'banner.php'; ?>
            <?php include 'menu.php';?>
    </div>
    <div class="main-content">
    <div class="container mt-4">
    <div class="row g-4">
        <?php while($row = $result->fetch_assoc()) { ?>
        <div class="col-md-4">
            <div class="gallery h-100">
                <a href="veiw_art.php?id=<?php echo urlencode($row['ArtID']); ?>">
                    <img 
                    src="images/<?php echo htmlspecialchars($row['image_name']); ?>" 
                    class="card-img-top gallery-img img-fluid"
                    alt="<?php echo htmlspecialchars($row['ArtName']); ?>">
                </a>
                <div class="mt-2 text-center small text-muted"><?php echo htmlspecialchars($row['ArtName']); ?></div>
            </div>
        </div>

        <?php } ?>

    </div>

</div>
    </div>
    <?php
        // free result and close local connection
        if (isset($result) && $result instanceof mysqli_result) {
            $result->free();
        }
        $artConn->close();
    ?>
    </body>
</html>
