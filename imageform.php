<?php
    session_start();
    $conn = new mysqli("localhost","root","",
                "ArtShopDB",3306);
    if($conn->connect_error){
        die("can't connect to ArtShopDB database");
    }
    if($_SERVER['REQUEST_METHOD'] == "POST"){
        if(isset($_POST['Insert'])){
            $ArtID = $_POST['ArtID'];
            $ArtName = $_POST['ArtName'];
            $image_name = "";
            $ArtDes = $_POST['ArtDescription'];

            if(move_uploaded_file($_FILES['filUpload']['tmp_name'],
            "image/". $_FILES['filUpload']['name'])){
                $image = $_FILES['filUpload']['name'];
            }

            // step 2: insert data in table
            $sql = "INSERT INTO Artdata(ArtID, ArtName, image_name, ArtDescription) VALUES('$ArtID','$ArtName','$image','$ArtDes')";
            if(mysqli_query($conn, $sql)){
                echo "Insert success!";
            } else {
                echo "Insert fail!";
            }

        } else if(isset($_POST['Update'])){
            $ArtID = $_POST['ArtID'];
            $ArtName = $_POST['ArtName'];
            $image_name = "";
            $ArtDes = $_POST['ArtDescription'];


            if(move_uploaded_file($_FILES['filUpload']['tmp_name'],
            "image/". $_FILES['filUpload']['name'])){
                $image = $_FILES['filUpload']['name'];
            }


            $sql = "UPDATE Artdata set ArtName='$ArtName',
                    image_name = '$image', ArtDescription = '$ArtDes' where ArtID = '$ArtID'";
            if(mysqli_query($conn, $sql)){
                echo "Update success";
            } else {
                echo "Fail";
            }
        }
        else if(isset($_POST['Delete'])){
            $ArtID = $_POST['ArtID'];
            $sql = "DELETE FROM Artdata where ArtID = '$ArtID'";
            if(mysqli_query($conn, $sql)){
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
        <?php include 'header.php'; ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link href="style.css" rel="stylesheet">
    </head>
<body>
    <div class="container">
        <?php include 'Banner.php'; ?>

        <div class="form-container">
            <div class="card">
            <h3>Feature Form</h3>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            
                <label>Art ID</label>
                <input type="text" name="ArtID" value="<?php echo isset($_POST['ArtID']) ? $_POST['ArtID'] : ''; ?>" required class="form-control"><br>

                <label>Art Name</label>
                <input type="text" name="ArtName" value="<?php echo isset($_POST['ArtName']) ? $_POST['ArtName'] : ''; ?>" class="form-control"><br>

                <label>Art Description</label>
                <input type="text" name="ArtDescription" value="<?php echo isset($_POST['ArtDescription']) ? $_POST['ArtDescription'] : ''; ?>" class="form-control"><br>

                <input type="file" name="filUpload" id="image" accept="image/*"><br><br>

                <button class="btn btn-primary" name="Insert" value="Insert">Insert</button>
                <button class="btn btn-warning" name="Update" value="Update">Update</button>
                <button class="btn btn-danger" name="Delete" value="Delete">Delete</button>
            </form>
            </div>
            <br>

            <h1>Gallery preview</h1>
    
            <div class="row g-3">
                <div class="card">
                <?php
                    $sql = "SELECT * FROM Artdata";
                    $result = mysqli_query($conn, $sql);
                    while($row = $result->fetch_assoc()){
                ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card h-100">
                                <img src="image/<?php echo $row['image_name']; ?>" 
                                    class="card-img-top img-fluid" 
                                    style="height:200px; object-fit:cover;">
                                <div class="card-body text-center">
                                    <p>Art ID: <?php echo $row['ArtID']; ?></p>
                                    <h6>Art Name: <?php echo $row['ArtName']; ?></h6>
                                    <a href="imageform.php?ArtID=<?php echo $row['ArtID']; ?>"
                                    class="btn btn-sm btn-primary">
                                    Select
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
            </div>
            </div>
            </div>
        </div>
    </div>

</body>
</html>