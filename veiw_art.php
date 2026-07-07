<?php
$conn = mysqli_connect("localhost", "root", "", "ArtShopDB", 3306);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
$id = (int)$_GET['id'];
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
            src="images/<?php echo $row['image_name']; ?>"
            class="big-image">
        </div>


        <!-- Information -->
        <div class="col-md-5">
            <h1>
                <?php echo $row['ArtName']; ?>
            </h1>
            <p>
                <strong>Artwork ID:</strong>
                <?php echo $row['ArtID']; ?>
            </p>
            <p>
                <?php echo $row['ArtDes']; ?>
            </p>

            <?php 
                $currentID = $row['ArtID'];
                $prevResult = mysqli_query($conn,
                    "SELECT ArtID
                    FROM Artdata
                    WHERE ArtID < '$currentID'
                    ORDER BY ArtID DESC
                    LIMIT 1");
                $prev = mysqli_fetch_assoc($prevResult);

                $nextResult = mysqli_query($conn,
                    "SELECT ArtID
                    FROM Artdata
                    WHERE ArtID > '$currentID'
                    ORDER BY ArtID ASC
                    LIMIT 1");
                $next = mysqli_fetch_assoc($nextResult);
            ?>
            <div class="d-flex justify-content-between mt-4">
            <?php if ($prev) { ?>
                <a href="veiw_art.php?id=<?php echo $prev['ArtID']; ?>" class="btn btn-dark">
                    ← Previous
                </a>
            <?php } else { ?>
                <div></div>
            <?php } ?>
            <?php if ($next) { ?>
                <a href="veiw_art.php?id=<?php echo $next['ArtID']; ?>" class="btn btn-dark">
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