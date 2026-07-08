<?php
    session_start();
    $uploadDir = "images";
    if(!is_dir($uploadDir)){
        mkdir($uploadDir, 0777, true);
    }

    $dbError = '';
    $conn = null;

    if (!extension_loaded('mysqli')) {
        $dbError = 'Database support is unavailable in this PHP environment. Enable the mysqli extension to use the gallery and upload features.';
    } else {
        $conn = new mysqli("localhost","root","", "ArtShopDB",3306);
        if($conn->connect_error){
            $dbError = 'Unable to connect to the database right now.';
            $conn = null;
        }
    }

    if($_SERVER['REQUEST_METHOD'] == "POST" && $conn !== null){
        if(isset($_POST['Insert'])){
            $ArtID = $conn->real_escape_string($_POST['ArtID']);
            $ArtName = $conn->real_escape_string($_POST['ArtName']);
            $image = "";
            $ArtDes = $conn->real_escape_string($_POST['ArtDes']);

            if(isset($_FILES['filUpload']) && $_FILES['filUpload']['error'] === UPLOAD_ERR_OK){
                $image = basename($_FILES['filUpload']['name']);
                if(!move_uploaded_file($_FILES['filUpload']['tmp_name'], $uploadDir . "/" . $image)){
                    echo "Image upload failed!";
                }
            }

            $stmt = $conn->prepare("INSERT INTO Artdata(ArtID, ArtName, image_name, ArtDes) VALUES(?, ?, ?, ?)");
            $stmt->bind_param("ssss", $ArtID, $ArtName, $image, $ArtDes);
            if($stmt->execute()){
                echo "Insert success!";
            } else {
                echo "Insert fail!";
            }

        } else if(isset($_POST['Update'])){
            $ArtID = $conn->real_escape_string($_POST['ArtID']);
            $ArtName = $conn->real_escape_string($_POST['ArtName']);
            $image = "";
            $ArtDes = $conn->real_escape_string($_POST['ArtDes']);

            $stmt = $conn->prepare("SELECT image_name FROM Artdata WHERE ArtID = ?");
            $stmt->bind_param("s", $ArtID);
            $stmt->execute();
            $checkResult = $stmt->get_result();
            if($checkResult && $checkResult->num_rows > 0){
                $existingImage = $checkResult->fetch_assoc();
                $image = $existingImage['image_name'];
            }

            if(isset($_FILES['filUpload']) && $_FILES['filUpload']['error'] === UPLOAD_ERR_OK){
                $image = basename($_FILES['filUpload']['name']);
                if(!move_uploaded_file($_FILES['filUpload']['tmp_name'], $uploadDir . "/" . $image)){
                    echo "Image upload failed!";
                }
            }

            $updateStmt = $conn->prepare("UPDATE Artdata SET ArtName=?, image_name=?, ArtDes=? WHERE ArtID=?");
            $updateStmt->bind_param("ssss", $ArtName, $image, $ArtDes, $ArtID);
            if($updateStmt->execute()){
                echo "Update success";
            } else {
                echo "Fail";
            }
        }
        else if(isset($_POST['Delete'])){
            $ArtID = $conn->real_escape_string($_POST['ArtID']);
            $deleteStmt = $conn->prepare("DELETE FROM Artdata WHERE ArtID=?");
            $deleteStmt->bind_param("s", $ArtID);
            if($deleteStmt->execute()){
                echo "Delete success";
            } else {
                echo "Fail";
            }
        }
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
                                $sql = "SELECT * FROM Artdata";
                                $result = $conn->query($sql);
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
                                            <a href="imageform.php?ArtID=<?php echo htmlspecialchars($row['ArtID']); ?>"
                                            class="btn btn-sm btn-primary">
                                            Select
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php }
                                }
                            } ?>
                    </div> <!-- .row -->
                </div> <!-- .gallery-wrapper -->
            </div> <!-- .form-container -->
        </div> <!-- .container -->
    </div> <!-- .main-content -->
</body>
</html>