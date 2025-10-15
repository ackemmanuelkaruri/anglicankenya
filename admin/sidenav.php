<!-- sidenav.php -->
<style>
    .sidebar {
        background: linear-gradient(180deg, #007bff 0%, #0056b3 100%);
        min-height: 100vh;
        width: 250px;
        position: fixed;
        box-shadow: 3px 0 15px rgba(0,0,0,0.1);
        transition: all 0.3s;
        z-index: 1; /* Ensure sidebar is above other content */
    }

    .nav-link {
        color: rgba(255,255,255,0.8) !important;
        padding: 15px 25px;
        margin: 8px 15px;
        border-radius: 8px;
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
    }

    .nav-link:hover {
        background: rgba(255,255,255,0.1);
        color: white !important;
        transform: translateX(10px);
    }

    .nav-link.active {
        background: rgba(255,255,255,0.15);
        color: white !important;
        font-weight: 500;
    }

    .nav-link::before {
        content: '';
        position: absolute;
        left: -5px;
        top: 0;
        height: 100%;
        width: 3px;
        background: white;
        transition: all 0.3s;
        opacity: 0;
    }

    .nav-link:hover::before {
        opacity: 1;
        left: 0;
    }
</style>

<div class="sidebar">
    <div class="sidebar-header p-4">
        <h3 class="text-white mb-0">ACK EMMANUEL KARURI</h3>
        <small class="text-white-50">Management Portal</small>
    </div>
    
    <nav class="nav flex-column mt-3">
        <a href="dashboard.php" class="nav-link active">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-person-gear me-2"></i> Administrator
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-person-badge me-2"></i> Clergy
        </a>
        <a href="#" class="nav-link">
            <i class="bi bi-people me-2"></i> Parishioner
        </a>
    </nav>
</div>