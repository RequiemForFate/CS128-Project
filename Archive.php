<?php
session_start();
require_once 'config.php';

$currentUser = $_SESSION['username'] ?? '';
if ($currentUser === '') {
    header('Location: index.php');
    exit();
}

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// --- Search logic for OWNER only ---
$searchTerm = '';
$whereClause = "WHERE owner = ?";
$params = [$currentUser];
$types = "s";

if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = trim($_GET['search']);
    if (is_numeric($searchTerm)) {
        $whereClause .= " AND ArtID = ?";
        $params[] = intval($searchTerm);
        $types .= "i";
    } else {
        $searchTermEscaped = $conn->real_escape_string($searchTerm);
        $whereClause .= " AND ArtName LIKE ?";
        $params[] = "%$searchTermEscaped%";
        $types .= "s";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Archive</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link href="style.css?v=3" rel="stylesheet">
    </head>
    <body>
        <div class="py-1">
            <?php include 'banner.php'; ?>
            <?php include 'menu.php'; ?>
            <?php
                // Build the final SQL query
                $sql = "SELECT * FROM Artdata $whereClause ORDER BY ArtID DESC";
                $artstmt = $conn->prepare($sql);
                $artstmt->bind_param($types, ...$params);
                $artstmt->execute();
                $result = $artstmt->get_result();
            ?>
            <div class="main-content">
                <div class="container py-4">

                    <!-- Search Form -->
                    <div class="row mb-4">
                        <div class="col-md-6 mx-auto">
                            <form method="get" class="d-flex" role="search">
                                <input
                                    type="text"
                                    name="search"
                                    class="form-control me-2"
                                    placeholder="Archive Search ..."
                                    value="<?php echo htmlspecialchars($searchTerm); ?>"
                                    aria-label="Search">
                                <button class="btn btn-primary" type="submit">⌕</button>
                                <?php if ($searchTerm !== ''): ?>
                                    <a href="Archive.php" class="btn btn-outline-secondary ms-2">⟳</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <!-- Gallery -->
                    <?php if ($result->num_rows === 0): ?>
                        <div class="alert alert-info text-center">
                            <?php if ($searchTerm !== ''): ?>
                                No artwork found matching “<?php echo htmlspecialchars($searchTerm); ?>”.
                            <?php else: ?>
                                You haven't uploaded any artwork yet. 
                                <a href="imageform.php">Upload your first piece!</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="gallery row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                            <?php while($row = mysqli_fetch_assoc($result)) { ?>
                            <div class="col">
                                <div class="card h-100 gallery-item">
                                    <a href="view_art.php?id=<?php echo $row['ArtID']; ?>">
                                        <img
                                            src="<?php echo UPLOAD_DIR . htmlspecialchars($row['image_name']); ?>"
                                            class="card-img-top gallery-img"
                                            alt="<?php echo htmlspecialchars($row['ArtName'] ?: 'Artwork'); ?>">
                                    </a>
                                    <div class="card-body text-center d-flex flex-column">
                                        <h5 class="card-title"><?php echo htmlspecialchars($row['ArtName']); ?></h5>
                                        <p class="card-text small">Art ID: <?php echo htmlspecialchars($row['ArtID']); ?></p>
                                        <!-- BADGE – consistent for all cards -->
                                        <span class="badge <?php echo (isset($row['isPublic']) && $row['isPublic']) ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo (isset($row['isPublic']) && $row['isPublic']) ? '🌍 Public' : '🔒 Private'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php
        $artstmt->close();
        $conn->close();
    ?>
    </body>
</html>
