<?php
$conn = mysqli_connect("localhost", "root", "", "ArtShopDB", 3306);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$id = (int)$_GET['id'];
$sql = "SELECT * FROM Artdata WHERE ArtID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    die("Artwork not found.");
}

// Get previous and next artwork
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($row['ArtName']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- Header Section -->
    <div class="container py-1">
        <?php include 'banner.php'; ?>
        <?php include 'menu.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container mt-5 position-relative">
            <!-- Close Button -->
            <a href="Artwork.php" class="close-btn">&times;</a>

            <!-- Artwork Display -->
            <div class="row align-items-center g-4">
                <!-- Image Section -->
                <div class="col-md-7">
                    <img 
                        src="images/<?php echo htmlspecialchars($row['image_name']); ?>"
                        class="big-image"
                        alt="<?php echo htmlspecialchars($row['ArtName']); ?>">
                </div>

                <!-- Information Section -->
                <div class="col-md-5">
                    <h1><?php echo htmlspecialchars($row['ArtName']); ?></h1>
                    
                    <p class="text-muted">
                        <strong>Artwork ID:</strong><br>
                        <?php echo htmlspecialchars($row['ArtID']); ?>
                    </p>
                    
                    <p>
                        <?php echo htmlspecialchars($row['ArtDes']); ?>
                    </p>

                    <!-- Navigation Buttons -->
                    <div class="d-flex justify-content-between mt-5 gap-2">
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
            </div> <!-- .row -->
        </div> <!-- .container -->
    </div> <!-- .main-content -->
</body>
</html>

<?php
    $conn->close();
?>