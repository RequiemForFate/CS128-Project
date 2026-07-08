<?php
session_start();
$uploadDir = "images";
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

if (!extension_loaded('mysqli')) {
    $dbError = 'Database support is unavailable in this PHP environment. Enable the mysqli extension to use the gallery and upload features.';
} else {
    $conn = new mysqli("localhost","root","", "ArtShopDB",3306);
    if($conn->connect_error){
        $dbError = 'Unable to connect to the database right now.';
        $conn = null;
    }
}

if ($conn !== null) {
    $conn->query("ALTER TABLE Artdata ADD COLUMN IF NOT EXISTS owner VARCHAR(255) NOT NULL DEFAULT ''");
}

$alertMessages = [];

if($_SERVER['REQUEST_METHOD'] == "POST" && $conn !== null){
    if(isset($_POST['Insert'])){
        $ArtID = $conn->real_escape_string($_POST['ArtID']);
        $ArtName = $conn->real_escape_string($_POST['ArtName']);
        $image = "";
        $ArtDes = $conn->real_escape_string($_POST['ArtDes']);

        if(isset($_FILES['filUpload']) && $_FILES['filUpload']['error'] === UPLOAD_ERR_OK){
            $image = basename($_FILES['filUpload']['name']);
            if(!move_uploaded_file($_FILES['filUpload']['tmp_name'], $uploadDir . "/" . $image)){
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

    } else if(isset($_POST['Update'])){
        $ArtID = $conn->real_escape_string($_POST['ArtID']);
        $ArtName = $conn->real_escape_string($_POST['ArtName']);
        $image = "";
        $ArtDes = $conn->real_escape_string($_POST['ArtDes']);

        $stmt = $conn->prepare("SELECT image_name FROM Artdata WHERE ArtID = ? AND owner = ?");
        $stmt->bind_param("ss", $ArtID, $currentUser);
        $stmt->execute();
        $checkResult = $stmt->get_result();
        if($checkResult && $checkResult->num_rows > 0){
            $existingImage = $checkResult->fetch_assoc();
            $image = $existingImage['image_name'];
        }

        if(isset($_FILES['filUpload']) && $_FILES['filUpload']['error'] === UPLOAD_ERR_OK){
            $image = basename($_FILES['filUpload']['name']);
            if(!move_uploaded_file($_FILES['filUpload']['tmp_name'], $uploadDir . "/" . $image)){
                $alertMessages[] = "Image upload failed!";
            }
        }

        $updateStmt = $conn->prepare("UPDATE Artdata SET ArtName=?, image_name=?, ArtDes=? WHERE ArtID=? AND owner=?");
        $updateStmt->bind_param("sssss", $ArtName, $image, $ArtDes, $ArtID, $currentUser);
        if($updateStmt->execute()){
            $alertMessages[] = "Update success";
        } else {
            $alertMessages[] = "Update failed.";
        }
    }
    else if(isset($_POST['Delete'])){
        $ArtID = $conn->real_escape_string($_POST['ArtID']);
        $deleteStmt = $conn->prepare("DELETE FROM Artdata WHERE ArtID=? AND owner=?");
        $deleteStmt->bind_param("ss", $ArtID, $currentUser);
        if($deleteStmt->execute()){
            $alertMessages[] = "Delete success";
        } else {
            $alertMessages[] = "Delete failed.";
        }
    }

    // --- Clear input fields after any submit ---
    $_POST['ArtID'] = '';
    $_POST['ArtName'] = '';
    $_POST['ArtDes'] = '';
}

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
    <div class="main-content">
        <div class="container">
            <!-- Alert messages -->
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
                        <input type="text" name="ArtID" value="<?php echo isset($_POST['ArtID']) ? htmlspecialchars($_POST['ArtID']) : ''; ?>" required class="form-control"><br>

                        <label>Art Name</label>
                        <input type="text" name="ArtName" value="<?php echo isset($_POST['ArtName']) ? htmlspecialchars($_POST['ArtName']) : ''; ?>" class="form-control"><br>

                        <label>Art Description</label>
                        <input type="text" name="ArtDes" value="<?php echo isset($_POST['ArtDes']) ? htmlspecialchars($_POST['ArtDes']) : ''; ?>" class="form-control"><br>

                        <input type="file" name="filUpload" id="image" accept="image/*"><br><br>
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
                                        <img src="images/<?php echo htmlspecialchars($row['image_name']); ?>"
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