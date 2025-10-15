<?php
// Breadcrumb navigation component
if (!isset($breadcrumb)) {
    // Default breadcrumb if not set
    $breadcrumb = [
        ['name' => 'Home', 'url' => isset($_SESSION['username']) ? 'dashboard.php' : 'index.php']
    ];
}
?>

<nav aria-label="breadcrumb" style="margin: 20px 0;">
    <ol class="breadcrumb">
        <?php foreach ($breadcrumb as $index => $item): ?>
            <?php if ($index < count($breadcrumb) - 1): ?>
                <!-- Not the last item - make it a link -->
                <li class="breadcrumb-item">
                    <a href="<?php echo htmlspecialchars($item['url']); ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                </li>
            <?php else: ?>
                <!-- Last item - current page -->
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($item['name']); ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>