<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the logged‑in username (or empty string if not set)
$username = $_SESSION['username'] ?? '';

// Default avatar (fallback)
$profileImageSrc = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"%3E%3Crect width="64" height="64" rx="32" fill="%234563ea"/%3E%3Ccircle cx="32" cy="24" r="14" fill="%23ffffff"/%3E%3Cellipse cx="32" cy="48" rx="20" ry="16" fill="%23ffffff"/%3E%3C/svg%3E';

// Initialize $userData with a safe default (avoids undefined variable errors)
$userData = ['display_name' => $username];

// If we have a username, try to load the custom profile picture and display name
if (!empty($username) && extension_loaded('mysqli')) {
    $menuConn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$menuConn->connect_error) {
        // Fetch both profile_picture and display_name
        $stmt = $menuConn->prepare("SELECT profile_picture, display_name FROM users WHERE name = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!empty($row['profile_picture'])) {
                $profileImageSrc = UPLOAD_DIR . htmlspecialchars($row['profile_picture']);
            }
            // Now $userData contains the display_name from the DB
            $userData = $row;
        }
        $menuConn->close();
    }
}
?>
<!-- The Side Menu -->
<button id="sidebarToggle" class="sidebar-toggle" aria-expanded="true" aria-controls="sidebar">☰ Menu</button>
<div class="sidebar" id="sidebar">
    <?php if (!empty($username)): ?>
        <!-- Logged-in user -->
        <a href="Bios.php?user=<?php echo urlencode($username); ?>" class="profile-link">
            <img src="<?php echo htmlspecialchars($profileImageSrc); ?>" alt="Profile" class="profile-avatar">
            <span class="username-display">
                <?php 
                    // Use display_name if available, otherwise fallback to username
                    $displayName = !empty($userData['display_name']) ? $userData['display_name'] : $username;
                    echo htmlspecialchars($displayName);
                ?>
            </span>
        </a>
        <br>
        <a href="Artwork.php">Gallery</a><br>
        <a href="Archive.php">Archive</a><br>
        <a href="imageform.php">Insert Image</a><br>
        <a href="logout.php">Logout</a><br>
    <?php else: ?>
        <!-- Guest (not logged in) -->
        <br>
        <a href="Artwork.php">Gallery</a><br>
        <a href="index.php">Login</a><br>
        <a href="signin.php">Sign Up</a><br>
    <?php endif; ?>
</div>
<script>
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');

    sidebar.classList.add('closed');
    document.body.classList.add('sidebar-closed');
    sidebarToggle.setAttribute('aria-expanded', 'false');
    sidebarToggle.textContent = '☰ Menu';

    sidebarToggle.addEventListener('click', () => {
        const isClosed = sidebar.classList.toggle('closed');
        document.body.classList.toggle('sidebar-closed', isClosed);
        sidebarToggle.setAttribute('aria-expanded', String(!isClosed));
        sidebarToggle.textContent = isClosed ? '☰ Menu' : '✕ Close';
    });
</script>
