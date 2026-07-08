<?php
session_start();

// Redirect if not logged in
$currentUser = $_SESSION['username'] ?? '';
if ($currentUser === '') {
    header('Location: login.php');
    exit();
}

// Database connection
$conn = mysqli_connect("localhost", "root", "", "ArtShopDB", 3306);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch artworks belonging to this user
$sql = "SELECT * FROM Artdata WHERE owner = ? ORDER BY id DESC"; // order by internal id
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $currentUser);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artwork Gallery</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <?php include 'menu.php'; ?>
    <?php include 'banner.php'; ?>
    
    <div class="container mt-4"> <!-- container start -->
        <div class="row g-4"> <!-- row start -->
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="col-sm-6 col-lg-4"> <!-- card column start -->
                        <div class="card gallery-item h-100"> <!-- card start -->
                            <a href="view_art.php?id=<?php echo htmlspecialchars($row['ArtID']); ?>">
                                <img
                                    src="images/<?php echo urlencode($row['image_name']); ?>"
                                    class="card-img-top gallery-img"
                                    alt="<?php echo htmlspecialchars($row['ArtName']); ?>"
                                    onerror="this.src='images/placeholder.png';">
                            </a>
                            <div class="card-body"> <!-- card-body start -->
                                <h5 class="card-title mb-2"><?php echo htmlspecialchars($row['ArtName']); ?></h5>
                                <p class="card-text text-muted">Artwork ID: <?php echo htmlspecialchars($row['ArtID']); ?></p>
                            </div> <!-- end card-body -->
                        </div> <!-- end card -->
                    </div> <!-- end card column -->
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-secondary text-center">
                        No artwork found yet. Upload an image from <a href="imageform.php">Insert Image</a> to populate the gallery.
                    </div>
                </div>
            <?php endif; ?>
        </div> <!-- end row -->
    </div> <!-- end container -->

    <?php
    if ($stmt) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
    ?>
</body>
</html>