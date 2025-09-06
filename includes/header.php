<?php
// If session is not started, start it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Phone Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <!-- Navigation bar -->
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <a class="navbar-brand" href="dashboard.php">
                    <i class="fas fa-mobile-alt me-2"></i>
                    Mobile Phone Admin
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="posts.php">Posts</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_comments.php">Comments</a>
                        </li>

                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-cogs"></i> Manage Data
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="manage_data.php">
                                            <i class="fas fa-file-alt"></i> JSON Files
                                        </a></li>
                                    <li><a class="dropdown-item" href="manage_data_db.php">
                                            <i class="fas fa-database"></i> PostgreSQL Database
                                        </a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <div class="d-flex align-items-center">
                        <span class="text-white me-3">
                            <i class="fas fa-user me-1"></i>
                            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                            (<?php echo isset($_SESSION['role']) ? ucfirst(htmlspecialchars($_SESSION['role'])) : 'User'; ?>)
                        </span>
                        <a href="logout.php" class="btn btn-outline-light">
                            <i class="fas fa-sign-out-alt me-1"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <!-- Main content container -->
    <main>