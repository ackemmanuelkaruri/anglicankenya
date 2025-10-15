<?php
// Get current page filename
$currentPage = basename($_SERVER['PHP_SELF']);

// Function to check if a link is active
function is_active($page, $currentPage) {
    return ($page === $currentPage) ? 'active' : '';
}
?>
<nav class="navbar navbar-expand-lg" style="background-color: #007bff;">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo isset($_SESSION['username']) ? 'dashboard.php' : 'index.php'; ?>" style="color: white;">ACK EMMANUEL KARURI</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <?php 
                    $homeLink = isset($_SESSION['username']) ? 'dashboard.php' : 'index.php';
                    $homeClass = is_active($homeLink, $currentPage);
                    ?>
                    <a class="nav-link <?php echo $homeClass; ?>" href="<?php echo $homeLink; ?>" style="color: white;">Home</a>
                </li>
                <?php
                if (isset($_SESSION['username'])) {
                    // Dashboard link for logged-in users
                    $dashClass = is_active('dashboard.php', $currentPage);
                    echo '<li class="nav-item">
                            <a class="nav-link ' . $dashClass . '" href="dashboard.php" style="color: white;">Dashboard</a>
                          </li>';
                    
                    // Welcome message
                    echo '<li class="nav-item">
                            <span class="nav-link" style="color: white;">Welcome, ' . htmlspecialchars($_SESSION['username']) . '!</span>
                          </li>';
                    
                    // Logout link
                    echo '<li class="nav-item">
                            <a class="nav-link" href="logout.php" style="color: white;">Logout</a>
                          </li>';
                } else {
                    // Guest links
                    echo '<li class="nav-item">
                            <a class="nav-link ' . is_active('login.php', $currentPage) . '" href="login.php" style="color: white;">Login</a>
                          </li>';
                    echo '<li class="nav-item">
                            <a class="nav-link ' . is_active('register.php', $currentPage) . '" href="register.php" style="color: white;">Register</a>
                          </li>';
                    echo '<li class="nav-item">
                            <a class="nav-link ' . is_active('reset_password.php', $currentPage) . '" href="reset_password.php" style="color: white;">Reset Password</a>
                          </li>';
                }
                ?>
            </ul>
        </div>
    </div>
</nav>