<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$username = isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'Login';
?>

<!-- The Side Menu -->
<button id="sidebarToggle" class="sidebar-toggle" aria-expanded="true" aria-controls="sidebar">☰ Menu</button>
<div class="sidebar" id="sidebar">
    <a href="Artwork.php">Gallery</a><br>
    <a href="imageform.php">Insert Image</a><br>
    <a href="profile.php">
        <?php echo htmlspecialchars($username); ?>
    </a><br>
    <a href="logout.php">Logout</a><br>
</div>
<script>
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    sidebarToggle.addEventListener('click', () => {
        const isClosed = sidebar.classList.toggle('closed');
        document.body.classList.toggle('sidebar-closed', isClosed);
        sidebarToggle.setAttribute('aria-expanded', String(!isClosed));
        sidebarToggle.textContent = isClosed ? '☰ Menu' : '✕ Close';
    });
</script>
