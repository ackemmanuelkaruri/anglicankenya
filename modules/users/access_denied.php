<?php
require_once 'includes/init.php';

 $page_title = "Access Denied";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">Access Denied</h4>
                    </div>
                    <div class="card-body">
                        <p>You do not have permission to access this resource.</p>
                        <p>If you believe this is an error, please contact the Super Admin:</p>
                        <ul>
                            <li>Email: superadmin@anglicankenya.org</li>
                            <li>Phone: +254 700 000000</li>
                        </ul>
                        <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>