<?php
session_start();

$conn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all artwork
$sql = "SELECT * FROM Artdata";
$result = $conn->query($sql);
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="style.css" rel="stylesheet">
</head>

<body>
    <!-- Header Section -->
    <div class="py-1">
        <?php include 'banner.php'; ?>
        <?php include 'menu.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="gallery-wrapper">
                <h1>Gallery</h1>

                <!-- Gallery Grid -->
                <div class="row row-cols-1 row-cols-lg-3 row-cols-md-2 g-4 gallery-row">
                    <?php while($row = $result->fetch_assoc()) { ?>
                        <div class="col">
                            <div class="card h-100">
                                <a href="veiw_art.php?id=<?php echo urlencode($row['ArtID']); ?>" class="gallery-link">
                                    <img 
                                        src="images/<?php echo htmlspecialchars($row['image_name']); ?>" 
                                        class="card-img-top"
                                        alt="<?php echo htmlspecialchars($row['ArtName']); ?>">
                                </a>
                                <div class="card-body text-center">
                                    <h5 class="card-title"><?php echo htmlspecialchars($row['ArtName']); ?></h5>
                                    <a href="veiw_art.php?id=<?php echo urlencode($row['ArtID']); ?>" class="btn btn-sm btn-primary">
                                        View
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div> <!-- .gallery-row -->
            </div> <!-- .gallery-wrapper -->
        </div> <!-- .container -->
    </div> <!-- .main-content -->

    <?php
        // Clean up
        if (isset($result) && $result instanceof mysqli_result) {
            $result->free();
        }
        $conn->close();
    ?>
</body>
</html>