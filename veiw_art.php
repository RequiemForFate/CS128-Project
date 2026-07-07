<?php
$conn = mysqli_connect("localhost", "root", "", "ArtShopDB", 3306);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    die("No artwork ID provided.");
}
$sql = "SELECT * FROM Artdata WHERE ArtID = $id";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
if (!$row) {
    die("Artwork not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Artwork</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
<div class="container mt-4 position-relative">
    <a href="Artwork.php" class="close-btn">&times;</a>
<div class="container mt-5">

    <div class="row align-items-center">

        <!-- Image -->
        <div class="col-md-7">
            <img 
            src="images/<?php echo htmlspecialchars($row['image_name']); ?>"
            class="big-image">
        </div>


        <!-- Information -->
        <div class="col-md-5">
            <h1>
                <?php echo htmlspecialchars($row['ArtName']); ?>
            </h1>
            <p>
                <strong>Artwork ID:</strong>
                <?php echo htmlspecialchars($row['ArtID']); ?>
            </p>
            <p>
                <?php echo htmlspecialchars($row['ArtDes']); ?>
            </p>

            <?php 
                $currentID = $row['ArtID'];
                $prevStmt = $conn->prepare("SELECT ArtID FROM Artdata WHERE ArtID < ? ORDER BY ArtID DESC LIMIT 1");
                $prevStmt->bind_param("i", $currentID);
                $prevStmt->execute();
                $prevResult = $prevStmt->get_result();
                $prev = $prevResult->fetch_assoc();

                $nextStmt = $conn->prepare("SELECT ArtID FROM Artdata WHERE ArtID > ? ORDER BY ArtID ASC LIMIT 1");
                $nextStmt->bind_param("i", $currentID);
                $nextStmt->execute();
                $nextResult = $nextStmt->get_result();
                $next = $nextResult->fetch_assoc();
            ?>
            <div class="d-flex justify-content-between mt-4">
            <?php if ($prev) { ?>
                <a href="veiw_art.php?id=<?php echo htmlspecialchars($prev['ArtID']); ?>" class="btn btn-dark">
                    ← Previous
                </a>
            <?php } else { ?>
                <div></div>
            <?php } ?>
            <?php if ($next) { ?>
                <a href="veiw_art.php?id=<?php echo htmlspecialchars($next['ArtID']); ?>" class="btn btn-dark">
                    Next →
                </a>
            <?php } ?>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>