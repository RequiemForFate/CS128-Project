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
    header('Location: index.php');
    exit();
}

// alert messages from session
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
            banner_image VARCHAR(255)
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

        // --- DUPLICATE CHECK (per user) ---
        $checkStmt = $conn->prepare("SELECT ArtID FROM Artdata WHERE owner = ? AND ArtID = ?");
        $checkStmt->bind_param("ss", $currentUser, $ArtID);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            $alertMessages[] = "You already have an artwork with this ID. Please choose a different Art ID.";
        } else {
            // Proceed with insert
            if(isset($_FILES['filUpload']) && $_FILES['filUpload']['error'] === UPLOAD_ERR_OK){
                $image = basename($_FILES['filUpload']['name']);
                if(!move_uploaded_file($_FILES['filUpload']['tmp_name'], $uploadDir . $image)){
                    $alertMessages[] = "Image upload failed!";
                }
            }

            $stmt = $conn->prepare("INSERT INTO Artdata(ArtID, ArtName, image_name, ArtDes, owner) VALUES(?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $ArtID, $ArtName, $image, $ArtDes, $currentUser);
            if($stmt->execute()){
                $alertMessages[] = "Insert success!";
            } else {
                $alertMessages[] = "Insert fail: " . $stmt->error;
            }
        }
        $checkStmt->close();

    } else if(isset($_POST['Update'])){
        $ArtID = $conn->real_escape_string($_POST['ArtID']);
        $newArtName = trim($_POST['ArtName']);
        $newArtDes = trim($_POST['ArtDes']);
        $image = "";

        // --- Fetch existing data ---
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

        if(empty($alertMessages)) {
            // Use existing values 
            $ArtName = (empty($newArtName)) ? $existingArtName : $newArtName;
            $ArtDes = (empty($newArtDes)) ? $existingArtDes : $newArtDes;

            // Image: keep existing unless a new file is uploaded
            if(isset($_FILES['filUpload']) && $_FILES['filUpload']['error'] === UPLOAD_ERR_OK && $_FILES['filUpload']['size'] > 0){
                $image = basename($_FILES['filUpload']['name']);
                if(!move_uploaded_file($_FILES['filUpload']['tmp_name'], $uploadDir . $image)){
                    $alertMessages[] = "Image upload failed!";
                } else {
                    // New image uploaded successfully
                }
            } else {
                $image = $existingImage; // keep existing
            }

            if(empty($alertMessages)) {
                $updateStmt = $conn->prepare("UPDATE Artdata SET ArtName=?, image_name=?, ArtDes=? WHERE ArtID=? AND owner=?");
                $updateStmt->bind_param("sssss", $ArtName, $image, $ArtDes, $ArtID, $currentUser);
                if($updateStmt->execute()){
                    $alertMessages[] = "Update success";
                } else {
                    $alertMessages[] = "Update failed.";
                }
            }
        }

    } else if(isset($_POST['Delete'])){
        $ArtID = $conn->real_escape_string($_POST['ArtID']);
        $deleteStmt = $conn->prepare("DELETE FROM Artdata WHERE ArtID=? AND owner=?");
        $deleteStmt->bind_param("ss", $ArtID, $currentUser);
        if($deleteStmt->execute()){
            $alertMessages[] = "Delete success";
        } else {
            $alertMessages[] = "Delete failed.";
        }
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

                        <input type="file" name="filUpload" id="image" accept="image/*" class="mt-2"><br><br>

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
                        ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <img src="<?php echo UPLOAD_DIR . htmlspecialchars($row['image_name']); ?>"
                                            class="card-img-top"
                                            alt="<?php echo htmlspecialchars($row['ArtName']); ?>">
                                        <div class="card-body text-center">
                                            <p class="card-text small">Art ID: <?php echo htmlspecialchars($row['ArtID']); ?></p>
                                            <h5 class="card-title"><?php echo htmlspecialchars($row['ArtName']); ?></h5>
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