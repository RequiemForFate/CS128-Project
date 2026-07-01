<div class="menu">
    <a href="Artwork.php">Insert Products</a>
    <a href="imageform.php">Insert Image</a>
    <div class="menu-right">
    <?php
       if(isset($_SESSION['username'])){
        echo "Hello ". $_SESSION['username']. " | ";
        echo "<a href='logout.php'>Logout</a>";
       } else {
        echo "<a href='login.php'>Login</a> ";
       }
    ?>
    </div>
</div>