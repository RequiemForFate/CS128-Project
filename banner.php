<?php
// banner.php fragment — include into pages' body where you want the banner shown.
// Prefer images/banner.jpg if it exists; otherwise fall back to banner.jpg in the project root.
$bannerImage = 'banner.jpg';
if (file_exists(__DIR__ . '/images/banner.jpg')) {
    $bannerImage = 'images/banner.jpg';
} elseif (file_exists(__DIR__ . '/banner.jpg')) {
    $bannerImage = 'banner.jpg';
}
?>

<style>
    .banner {
        width: 100%;
        margin: 0;
        padding: 0;
        text-align: center;
    }

    .banner-image {
        display: block;
        width: 100%;
        height: auto;
        min-height: 180px;
        max-height: 280px;
        object-fit: cover;
        padding: 0;
    }

    .logo-big {
        width: 60px;
        height: auto;
    }

    /* optional spacing for the heading over the banner */
    .banner h2 {
        margin-top: -60px;
        color: #000;
        text-shadow: 0 1px 2px rgba(255,255,255,0.6);
    }
</style>

<div class="banner">
    <img src="<?php echo htmlspecialchars($bannerImage); ?>" class="banner-image" alt="Banner Image">
    <h2 class="text-black">Concept of Fumble's Gallery</h2>
</div>
