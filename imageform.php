<?php

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ART Product</title>
</head>

<body>
    <div class="container">
        <?php include 'banner.php'; ?>
        <?php include 'menu.php'; ?>

        <div class="card">
            <h3>Feature Form</h3>
           <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            
                <label>Art ID</label>
                <input type="text" name="ProductID" value="<?php  ?>" required class="form-control"><br>

                <label>Feature Name</label>
                <input type="text" name="FeatureName" class="form-control"><br>
                
                <input type="file" name="filUpload" id="image" accept="image/*"><br><br>

                <button class="btn btn-primary" name="Insert" value="Insert">Insert</button>
                <button class="btn btn-warning" name="Update" value="Update">Update</button>

            </form>
            <br>

            <h1>Gallery</h1>
    
            <div class="row g-3">
                
            </div>
</body>
</html>