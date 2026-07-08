<?php
session_start();

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$uploadDir = "images";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
if (!is_writable($uploadDir)) {
    $dbError = 'The images/ folder is not writable. Please set permissions to 755 or 777.';
}

$dbError = '';
$conn = null;
$currentUser = $_SESSION['username'] ?? '';

if (empty($currentUser)) {
    header('Location: login.php');
    exit();
}

if (!extension_loaded('mysqli')) {
    $dbError = 'Database support is unavailable in this PHP environment. Enable the mysqli extension to use the gallery and upload features.';
} else {
    $conn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
    if ($conn->connect_error) {
        $dbError = 'Unable to connect to the database right now.';
        $conn = null;
    }
}

// Ensure the owner column exists (already in the new table, but keep for safety)
if ($conn !== null) {
    $check = $conn->query("SHOW COLUMNS FROM Artdata LIKE 'owner'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE Artdata ADD COLUMN owner VARCHAR(255) NOT NULL DEFAULT ''");
    }
}

// Handle POST actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] == "POST" && $conn !== null) {
    $ArtID   = $conn->real_escape_string($_POST['ArtID']);
    $ArtName = $conn->real_escape_string($_POST['ArtName']);
    $ArtDes  = $conn->real_escape_string($_POST['ArtDes']);
    $image   = '';

    // Process file upload
    $uploadOk = false;
    if (isset($_FILES['filUpload']) && $_FILES['filUpload']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['filUpload']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            // Create a unique filename
            $image = uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['filUpload']['tmp_name'], $uploadDir . '/' . $image)) {
                $uploadOk = true;
            } else {
                $message = 'Image upload failed (move error).';
            }
        } else {
            $message = 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.';
        }
    }

    // Insert
    if (isset($_POST['Insert'])) {
        if ($uploadOk && $image !== '') {
            $stmt = $conn->prepare("INSERT INTO Artdata(ArtID, ArtName, image_name, ArtDes, owner) VALUES(?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $ArtID, $ArtName, $image, $ArtDes, $currentUser);
            if ($stmt->execute()) {
                $message = 'Insert success!';
            } else {
                $message = 'Insert failed: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = $message ?: 'Please select an image to insert.';
        }
    }

    // Update
    elseif (isset($_POST['Update'])) {
        // Fetch current image name
        $stmt = $conn->prepare("SELECT image_name FROM Artdata WHERE ArtID = ? AND owner = ?");
        $stmt->bind_param("ss", $ArtID, $currentUser);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $oldImage = $row['image_name'];
        } else {
            $message = 'Record not found or you do not own it.';
            $stmt->close();
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode($message));
            exit();
        }
        $stmt->close();

        // If a new image was uploaded, use it; otherwise keep old
        $newImage = ($uploadOk && $image !== '') ? $image : $oldImage;

        $updateStmt = $conn->prepare("UPDATE Artdata SET ArtName=?, image_name=?, ArtDes=? WHERE ArtID=? AND owner=?");
        $updateStmt->bind_param("sssss", $ArtName, $newImage, $ArtDes, $ArtID, $currentUser);
        if ($updateStmt->execute()) {
            $message = 'Update success';
        } else {
            $message = 'Update failed: ' . $updateStmt->error;
        }
        $updateStmt->close();
    }

    // Delete
    elseif (isset($_POST['Delete'])) {
        // First, get the image name to delete the file
        $stmt = $conn->prepare("SELECT image_name FROM Artdata WHERE ArtID=? AND owner=?");
        $stmt->bind_param("ss", $ArtID, $currentUser);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $imageFile = $row['image_name'];
            // Delete the file if it exists
            if (!empty($imageFile) && file_exists($uploadDir . '/' . $imageFile)) {
                unlink($uploadDir . '/' . $imageFile);
            }
        }
        $stmt->close();

        // Now delete the record
        $deleteStmt = $conn->prepare("DELETE FROM Artdata WHERE ArtID=? AND owner=?");
        $deleteStmt->bind_param("ss", $ArtID, $currentUser);
        if ($deleteStmt->execute()) {
            $message = 'Delete success';
        } else {
            $message = 'Delete failed: ' . $deleteStmt->error;
        }
        $deleteStmt->close();
    }

    $conn->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode($message));
    exit();
}

// Reconnect for displaying the form and gallery
$conn = new mysqli("localhost", "root", "", "ArtShopDB", 3306);
if ($conn->connect_error) {
    $dbError = 'Unable to connect to the database.';
    $conn = null;
}

$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <?php include 'banner.php'; ?>
    <?php include 'menu.php'; ?>
    <div class="main-content"> <!-- main-content start -->
        <div class="container"> <!-- container start -->
            <div class="form-container"> <!-- form-container start -->
                <div class="card"> <!-- card start -->
                    <h3>Portfolio Application</h3>
                    <?php if ($dbError !== ''): ?>
                        <div class="alert alert-warning"><?php echo htmlspecialchars($dbError); ?></div>
                    <?php endif; ?>
                    <?php if ($msg !== ''): ?>
                        <div class="alert alert-info"><?php echo $msg; ?></div>
                    <?php endif; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                        <label>Art ID</label>
                        <input type="text" name="ArtID" value="<?php echo isset($_POST['ArtID']) ? htmlspecialchars($_POST['ArtID']) : ''; ?>" required class="form-control"><br>

                        <label>Art Name</label>
                        <input type="text" name="ArtName" value="<?php echo isset($_POST['ArtName']) ? htmlspecialchars($_POST['ArtName']) : ''; ?>" class="form-control"><br>

                        <label>Art Description</label>
                        <input type="text" name="ArtDes" value="<?php echo isset($_POST['ArtDes']) ? htmlspecialchars($_POST['ArtDes']) : ''; ?>" class="form-control"><br>

                        <input type="file" name="filUpload" id="image" accept="image/*"><br><br>
                        <div class="btn-group d-flex justify-content-center"> <!-- btn-group start -->
                            <button class="btn btn-primary" name="Insert" value="Insert">Insert</button>
                            <button class="btn btn-warning" name="Update" value="Update">Update</button>
                            <button class="btn btn-danger" name="Delete" value="Delete">Delete</button>
                        </div> <!-- end btn-group -->
                    </form>
                </div> <!-- end card -->
                <br>
                <div class="gallery-wrapper"> <!-- gallery-wrapper start -->
                    <h1>Gallery preview</h1>
                    <div class="row row-cols-1 row-cols-lg-3 row-cols-md-2 g-4 gallery-row"> <!-- row start -->
                        <?php
                        if ($conn !== null) {
                            $sql = "SELECT * FROM Artdata WHERE owner = ? ORDER BY id DESC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("s", $currentUser);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    ?>
                                    <div class="col"> <!-- col start -->
                                        <div class="card h-100"> <!-- card start -->
                                            <img src="images/<?php echo urlencode($row['image_name']); ?>"
                                                 class="card-img-top"
                                                 alt="<?php echo htmlspecialchars($row['ArtName']); ?>"
                                                 onerror="this.src='images/placeholder.png';">
                                            <div class="card-body text-center"> <!-- card-body start -->
                                                <p class="card-text small">Art ID: <?php echo htmlspecialchars($row['ArtID']); ?></p>
                                                <h5 class="card-title"><?php echo htmlspecialchars($row['ArtName']); ?></h5>
                                            </div> <!-- end card-body -->
                                        </div> <!-- end card -->
                                    </div> <!-- end col -->
                                <?php }
                            } else {
                                echo '<div class="col-12"><div class="alert alert-secondary">No images uploaded yet.</div></div>';
                            }
                            $stmt->close();
                            $conn->close();
                        }
                        ?>
                    </div> <!-- end row -->
                </div> <!-- end gallery-wrapper -->
            </div> <!-- end form-container -->
        </div> <!-- end container -->
    </div> <!-- end main-content -->
</body>
</html>