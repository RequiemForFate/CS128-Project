<?php
session_start();
require_once 'config.php';

$uploadDir = UPLOAD_DIR;
if(!is_dir($uploadDir)){
    mkdir($uploadDir, 0777, true);
}

$dbError = '';
$conn = null;
$currentUser = $_SESSION['username'] ?? '';

if (empty($currentUser)) {
    header('Location: login.php');
    exit();
}

$alertMessages = [];
if (isset($_SESSION['flash_messages'])) {
    $alertMessages = $_SESSION['flash_messages'];
    unset($_SESSION['flash_messages']);
}

if (!extension_loaded('mysqli')) {
    $dbError = 'Database support is unavailable in this PHP environment. Enable the mysqli extension to use the gallery and upload features.';
} else {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if($conn->connect_error){
        $dbError = 'Unable to connect to the database right now.';
        $conn = null;
    }
}

// Create tables if they don't exist (composite key: owner, ArtID)
if ($conn !== null) {
    $createArtdata = "
        CREATE TABLE IF NOT EXISTS Artdata (
            ArtID INT NOT NULL,
            ArtName VARCHAR(100),
            image_name VARCHAR(255),
            ArtDes TEXT,
            owner VARCHAR(255) NOT NULL DEFAULT '',
            isPublic TINYINT(1) DEFAULT 0,
            isAnonymous TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (owner, ArtID)
        )
    ";
    $conn->query($createArtdata);

    $createUsers = "
        CREATE TABLE IF NOT EXISTS users (
            name VARCHAR(50) PRIMARY KEY,
            password VARCHAR(255) NOT NULL,
            profile_picture VARCHAR(255),
            gallery_name VARCHAR(100),
            banner_image VARCHAR(255),
            display_name VARCHAR(100) DEFAULT '',
            bio_description TEXT,
            show_gallery_on_bio TINYINT(1) DEFAULT 1,
            bg_color VARCHAR(7) DEFAULT '#f5f5f5',
            social_links TEXT
        )
    ";
    $conn->query($createUsers);
}

$shouldRedirect = false;

if($_SERVER['REQUEST_METHOD'] == "POST" && $conn !== null){
    if(isset($_POST['Insert'])){
        $ArtID = $conn->real_escape_string($_POST['ArtID']);
        $ArtName = $conn->real_escape_string($_POST['ArtName']);
        $image = "";
        $ArtDes = $conn->real_escape_string($_POST['ArtDes']);
        $isPublic = isset($_POST['isPublic']) ? 1 : 0;
        $isAnonymous = isset($_POST['isAnonymous']) ? 1 : 0;
        if ($isAnonymous && !$isPublic) {
            $alertMessages[] = "Anonymous posting requires the artwork to be public. We've made it public.";
            $isPublic = 1;
        }

        // DUPLICATE CHECK
        $checkStmt = $conn->prepare("SELECT ArtID FROM Artdata WHERE owner = ? AND ArtID = ?");
        $checkStmt->bind_param("ss", $currentUser, $ArtID);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            $alertMessages[] = "You already have an artwork with this ID. Please choose a different Art ID.";
        } else {
            // --- File upload (images + videos) ---
            if(isset($_FILES['filUpload']) && $_FILES['filUpload']['error'] === UPLOAD_ERR_OK){
                $fileTmpPath = $_FILES['filUpload']['tmp_name'];
                $fileName = basename($_FILES['filUpload']['name']);
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                // Allowed file types: images + videos
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];
                if (in_array($fileExtension, $allowedExtensions)) {
                    // Generate a unique filename to avoid overwriting
                    $newFileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
                    $destPath = $uploadDir . $newFileName;
                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        $image = $newFileName;
                    } else {
                        $alertMessages[] = "File upload failed!";
                    }
                } else {
                    $alertMessages[] = "Invalid file type. Please upload an image or video.";
                }
            }

            if ($image !== '' && empty($alertMessages)) {
                $stmt = $conn->prepare("INSERT INTO Artdata(ArtID, ArtName, image_name, ArtDes, owner, isPublic, isAnonymous) VALUES(?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssii", $ArtID, $ArtName, $image, $ArtDes, $currentUser, $isPublic, $isAnonymous);
                if($stmt->execute()){
                    $alertMessages[] = "Insert success!";
                } else {
                    $alertMessages[] = "Insert fail: " . $stmt->error;
                }
                $stmt->close();
            } else {
                // If image empty, means no file or upload failed; we already have alerts
            }
        }
        $checkStmt->close();

    } else if(isset($_POST['Update'])){
        $ArtID = $conn->real_escape_string($_POST['ArtID']);
        $newArtName = trim($_POST['ArtName']);
        $newArtDes = trim($_POST['ArtDes']);
        $image = "";
        $isPublic = isset($_POST['isPublic']) ? 1 : 0;
        $isAnonymous = isset($_POST['isAnonymous']) ? 1 : 0;
        if ($isAnonymous && !$isPublic) {
            $alertMessages[] = "Anonymous posting requires the artwork to be public. We've made it public.";
            $isPublic = 1;
        }

        // Fetch existing
        $stmt = $conn->prepare("SELECT ArtName, ArtDes, image_name FROM Artdata WHERE ArtID = ? AND owner = ?");
        $stmt->bind_param("ss", $ArtID, $currentUser);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result && $result->num_rows > 0){
            $row = $result->fetch_assoc();
            $existingArtName = $row['ArtName'];
            $existingArtDes = $row['ArtDes'];
            $existingImage = $row['image_name'];
        } else {
            $alertMessages[] = "Update failed: Artwork not found or you don't own it.";
            $shouldRedirect = true;
        }
        $stmt->close();

        if(empty($alertMessages)) {
            $ArtName = (empty($newArtName)) ? $existingArtName : $newArtName;
            $ArtDes = (empty($newArtDes)) ? $existingArtDes : $newArtDes;

            // Handle file upload (if any)
            if(isset($_FILES['filUpload']) && $_FILES['filUpload']['error'] === UPLOAD_ERR_OK && $_FILES['filUpload']['size'] > 0){
                $fileTmpPath = $_FILES['filUpload']['tmp_name'];
                $fileName = basename($_FILES['filUpload']['name']);
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];
                if (in_array($fileExtension, $allowedExtensions)) {
                    $newFileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
                    $destPath = $uploadDir . $newFileName;
                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        // Delete old file if it exists
                        if (!empty($existingImage) && file_exists($uploadDir . $existingImage)) {
                            unlink($uploadDir . $existingImage);
                        }
                        $image = $newFileName;
                    } else {
                        $alertMessages[] = "File upload failed!";
                    }
                } else {
                    $alertMessages[] = "Invalid file type. Please upload an image or video.";
                }
            } else {
                $image = $existingImage; // keep existing
            }

            if(empty($alertMessages)) {
                $updateStmt = $conn->prepare("UPDATE Artdata SET ArtName=?, image_name=?, ArtDes=?, isPublic=?, isAnonymous=? WHERE ArtID=? AND owner=?");
                $updateStmt->bind_param("sssiiis", $ArtName, $image, $ArtDes, $isPublic, $isAnonymous, $ArtID, $currentUser);
                if($updateStmt->execute()){
                    $alertMessages[] = "Update success";
                } else {
                    $alertMessages[] = "Update failed.";
                }
                $updateStmt->close();
            }
        }

    } else if(isset($_POST['Delete'])){
        $ArtID = $conn->real_escape_string($_POST['ArtID']);
        // Delete the file first
        $selectStmt = $conn->prepare("SELECT image_name FROM Artdata WHERE ArtID=? AND owner=?");
        $selectStmt->bind_param("ss", $ArtID, $currentUser);
        $selectStmt->execute();
        $selectResult = $selectStmt->get_result();
        if ($row = $selectResult->fetch_assoc()) {
            if (!empty($row['image_name']) && file_exists($uploadDir . $row['image_name'])) {
                unlink($uploadDir . $row['image_name']);
            }
        }
        $selectStmt->close();

        $deleteStmt = $conn->prepare("DELETE FROM Artdata WHERE ArtID=? AND owner=?");
        $deleteStmt->bind_param("ss", $ArtID, $currentUser);
        if($deleteStmt->execute()){
            $alertMessages[] = "Delete success";
        } else {
            $alertMessages[] = "Delete failed.";
        }
        $deleteStmt->close();
    }

    $_SESSION['flash_messages'] = $alertMessages;
    $shouldRedirect = true;
}

if ($shouldRedirect) {
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link href="style.css?v=2" rel="stylesheet">
    </head>
<body>
    <?php include 'banner.php'; ?>
    <?php include 'menu.php'; ?>
    <div class="main-content">
        <div class="container px-0 px-sm-3">
            <?php if (!empty($alertMessages)): ?>
                <?php foreach ($alertMessages as $msg): ?>
                    <div class="alert alert-info text-center"><?php echo htmlspecialchars($msg); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="form-container">
                <div class="card">
                    <h3>Portfolio Application</h3>
                    <?php if ($dbError !== ''): ?>
                        <div class="alert alert-warning"><?php echo htmlspecialchars($dbError); ?></div>
                    <?php endif; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                        <label>Art ID</label>
                        <input type="text" name="ArtID" value="" required class="form-control mb-2" placeholder="Enter Art ID...">

                        <label>Art Name</label>
                        <input type="text" name="ArtName" value="" class="form-control mb-2" placeholder="Enter Artwork Name...">

                        <label>Art Description</label>
                        <textarea name="ArtDes" class="form-control" rows="4" placeholder="Enter Description..."></textarea>

                        <!-- File input accepts both images and videos -->
                        <input type="file" name="filUpload" id="image" accept="image/*,video/*" class="mt-2"><br><br>

                        <!-- Toggles -->
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="isPublic" id="isPublic" value="1" checked>
                            <label class="form-check-label" for="isPublic">Make this artwork public</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="isAnonymous" id="isAnonymous" value="1">
                            <label class="form-check-label" for="isAnonymous">Post anonymously (only if public)</label>
                        </div>

                        <div class="btn-group d-flex justify-content-center">
                            <button class="btn btn-primary" name="Insert" value="Insert">Insert</button>
                            <button class="btn btn-warning" name="Update" value="Update">Update</button>
                            <button class="btn btn-danger" name="Delete" value="Delete">Delete</button>
                        </div>
                    </form>
                </div>
                <br>
                <div class="gallery-wrapper">
                    <h1>Gallery preview</h1>
                    <div class="row row-cols-1 row-cols-lg-3 row-cols-md-2 g-4 gallery-row">
                        <?php
                            if ($conn !== null) {
                                $sql = "SELECT * FROM Artdata WHERE owner = ? ORDER BY ArtID DESC";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("s", $currentUser);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if($result && $result->num_rows > 0){
                                    while($row = $result->fetch_assoc()){
                                        $filePath = UPLOAD_DIR . htmlspecialchars($row['image_name']);
                                        $fileExt = strtolower(pathinfo($row['image_name'], PATHINFO_EXTENSION));
                                        $isVideo = in_array($fileExt, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv']);
                        ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <?php if ($isVideo): ?>
                                            <video controls class="card-img-top" style="height: 200px; object-fit: cover; background:#000;">
                                                <source src="<?php echo $filePath; ?>" type="video/<?php echo $fileExt; ?>">
                                                Your browser does not support the video tag.
                                            </video>
                                        <?php else: ?>
                                            <img src="<?php echo $filePath; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['ArtName']); ?>">
                                        <?php endif; ?>
                                        <div class="card-body text-center">
                                            <p class="card-text small">Art ID: <?php echo htmlspecialchars($row['ArtID']); ?></p>
                                            <h5 class="card-title"><?php echo htmlspecialchars($row['ArtName']); ?></h5>
                                            <span class="badge <?php echo $row['isPublic'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $row['isPublic'] ? 'Public' : 'Private'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php }
                                }
                            } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php if ($conn) $conn->close(); ?>