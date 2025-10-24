<!-- Impersonation Warning Banner -->
<div class="impersonation-banner">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>⚠️ IMPERSONATION MODE ACTIVE</strong>
            <span class="ms-3">
                Viewing as: <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                (<?php echo htmlspecialchars($user['email']); ?>)
            </span>
        </div>
        <a href="modules/users/stop_impersonate.php" class="btn btn-stop-impersonate">
            <i class="fas fa-times-circle me-2"></i>EXIT IMPERSONATION
        </a>
    </div>
</div>