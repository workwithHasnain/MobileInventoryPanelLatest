<?php
require_once 'auth.php';

// Require login for this page
requireLogin();

$user_role = $_SESSION['role'] ?? 'employee';

// Initialize database
require_once 'database_functions.php';
$pdo = getConnection();

$message = '';
$error = '';

// Handle comment actions
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    $comment_id = $_POST['comment_id'] ?? '';
    $comment_type = $_POST['comment_type'] ?? '';

    if ($action === 'approve') {
        if ($comment_type === 'post') {
            $stmt = $pdo->prepare("UPDATE post_comments SET status = 'approved' WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE device_comments SET status = 'approved' WHERE id = ?");
        }
        if ($stmt->execute([$comment_id])) {
            $message = 'Comment approved successfully!';
        } else {
            $error = 'Failed to approve comment.';
        }
    } elseif ($action === 'reject') {
        if ($comment_type === 'post') {
            $stmt = $pdo->prepare("UPDATE post_comments SET status = 'rejected' WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE device_comments SET status = 'rejected' WHERE id = ?");
        }
        if ($stmt->execute([$comment_id])) {
            $message = 'Comment rejected successfully!';
        } else {
            $error = 'Failed to reject comment.';
        }
    } elseif ($action === 'delete') {
        if ($comment_type === 'post') {
            $stmt = $pdo->prepare("DELETE FROM post_comments WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("DELETE FROM device_comments WHERE id = ?");
        }
        if ($stmt->execute([$comment_id])) {
            $message = 'Comment deleted successfully!';
        } else {
            $error = 'Failed to delete comment.';
        }
    }
}

// Get pending comments grouped by post
$pending_by_post_stmt = $pdo->prepare("
    SELECT p.id as post_id, p.title, p.slug, 
           COUNT(pc.id) as comment_count,
           JSON_AGG(
               JSON_BUILD_OBJECT(
                   'id', pc.id,
                   'name', pc.name,
                   'email', pc.email,
                   'comment', pc.comment,
                   'created_at', pc.created_at
               ) ORDER BY pc.created_at DESC
           ) as comments
    FROM posts p
    LEFT JOIN post_comments pc ON p.id = pc.post_id AND pc.status = 'pending'
    WHERE p.status ILIKE 'published'
    GROUP BY p.id, p.title, p.slug
    HAVING COUNT(pc.id) > 0
    ORDER BY COUNT(pc.id) DESC, p.created_at DESC
");
$pending_by_post_stmt->execute();
$pending_by_post = $pending_by_post_stmt->fetchAll();

// Get pending device comments
$device_comments_stmt = $pdo->prepare("SELECT * FROM device_comments WHERE status = 'pending' ORDER BY created_at DESC");
$device_comments_stmt->execute();
$pending_device_comments = $device_comments_stmt->fetchAll();

// Get total pending counts for tabs
$total_pending_posts_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM post_comments WHERE status = 'pending'");
$total_pending_posts_stmt->execute();
$total_pending_posts = $total_pending_posts_stmt->fetch()['count'];

$total_pending_devices_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM device_comments WHERE status = 'pending'");
$total_pending_devices_stmt->execute();
$total_pending_devices = $total_pending_devices_stmt->fetch()['count'];

// Get recent approved comments - separate queries to avoid UNION type mismatch
$post_approved_stmt = $pdo->prepare("
    SELECT 'post' as type, pc.id, pc.post_id::text as reference_id, pc.name, pc.email, pc.comment, pc.status, pc.created_at, pc.updated_at, p.title as reference_title
    FROM post_comments pc 
    LEFT JOIN posts p ON pc.post_id = p.id 
    WHERE pc.status = 'approved' 
    ORDER BY pc.created_at DESC LIMIT 10
");
$post_approved_stmt->execute();
$recent_post_approved = $post_approved_stmt->fetchAll();

$device_approved_stmt = $pdo->prepare("
    SELECT 'device' as type, dc.id, dc.device_id as reference_id, dc.name, dc.email, dc.comment, dc.status, dc.created_at, dc.updated_at, dc.device_id as reference_title
    FROM device_comments dc 
    WHERE dc.status = 'approved' 
    ORDER BY dc.created_at DESC LIMIT 10
");
$device_approved_stmt->execute();
$recent_device_approved = $device_approved_stmt->fetchAll();

// Merge and sort the results
$recent_approved = array_merge($recent_post_approved, $recent_device_approved);
usort($recent_approved, function ($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recent_approved = array_slice($recent_approved, 0, 20);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Comments - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-comments text-primary me-3"></i>Manage Comments</h1>
            <a href="index.php" class="btn btn-outline-primary" target="_blank">
                <i class="fas fa-external-link-alt me-2"></i>View Public Site
            </a>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h3 class="fw-bold"><?php echo $total_pending_posts + $total_pending_devices; ?></h3>
                        <p class="mb-0">Pending Comments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-newspaper fa-2x mb-2"></i>
                        <h3 class="fw-bold"><?php echo $total_pending_posts; ?></h3>
                        <p class="mb-0">Post Comments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-mobile-alt fa-2x mb-2"></i>
                        <h3 class="fw-bold"><?php echo count($pending_device_comments); ?></h3>
                        <p class="mb-0">Device Comments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h3 class="fw-bold"><?php echo count($recent_approved); ?></h3>
                        <p class="mb-0">Recent Approved</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs for different comment types -->
        <ul class="nav nav-tabs" id="commentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                    <i class="fas fa-clock me-2"></i>Pending Comments
                    <span class="badge bg-warning text-dark ms-2"><?php echo $total_pending_posts + $total_pending_devices; ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab">
                    <i class="fas fa-check-circle me-2"></i>Recent Approved
                </button>
            </li>
        </ul>

        <div class="tab-content" id="commentTabsContent">
            <!-- Pending Comments Tab -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($pending_by_post) && empty($pending_device_comments)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Pending Comments</h4>
                                <p class="text-muted">All comments have been reviewed!</p>
                            </div>
                        <?php else: ?>
                            <!-- Post Comments Grouped by Post -->
                            <?php if (!empty($pending_by_post)): ?>
                                <h4 class="border-bottom pb-2 mb-4">
                                    <i class="fas fa-newspaper text-primary me-2"></i>Post Comments (<?php echo $total_pending_posts; ?>)
                                </h4>
                                <?php foreach ($pending_by_post as $post_group): ?>
                                    <div class="post-comment-group mb-4 p-3 border rounded bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h5 class="mb-1">
                                                    <i class="fas fa-file-alt text-primary me-2"></i>
                                                    <?php echo htmlspecialchars($post_group['title']); ?>
                                                </h5>
                                                <small class="text-muted">
                                                    <i class="fas fa-comments me-1"></i>
                                                    <?php echo $post_group['comment_count']; ?> pending comment(s)
                                                </small>
                                            </div>
                                            <a href="post.php?slug=<?php echo htmlspecialchars($post_group['slug']); ?>"
                                                class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="fas fa-external-link-alt me-1"></i>View Post
                                            </a>
                                        </div>

                                        <?php
                                        $comments = json_decode($post_group['comments'], true);
                                        if ($comments && is_array($comments)):
                                            foreach ($comments as $comment):
                                                if ($comment): // Skip null entries 
                                        ?>
                                                    <div class="comment-item border rounded p-3 mb-3 bg-white">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($comment['name']); ?></strong>
                                                                <small class="text-muted ms-2"><?php echo htmlspecialchars($comment['email']); ?></small>
                                                                <br>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-clock me-1"></i>
                                                                    <?php echo date('M j, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                                                                </small>
                                                            </div>
                                                            <div class="btn-group">
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="action" value="approve">
                                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                                    <input type="hidden" name="comment_type" value="post">
                                                                    <button type="submit" class="btn btn-sm btn-success">
                                                                        <i class="fas fa-check"></i> Approve
                                                                    </button>
                                                                </form>
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="action" value="reject">
                                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                                    <input type="hidden" name="comment_type" value="post">
                                                                    <button type="submit" class="btn btn-sm btn-warning">
                                                                        <i class="fas fa-times"></i> Reject
                                                                    </button>
                                                                </form>
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                                    <input type="hidden" name="comment_type" value="post">
                                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                                        onclick="return confirm('Are you sure you want to delete this comment?')">
                                                                        <i class="fas fa-trash"></i> Delete
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- Device Comments -->
                            <?php if (!empty($pending_device_comments)): ?>
                                <h5 class="border-bottom pb-2 mb-3 mt-4">
                                    <i class="fas fa-mobile-alt text-success me-2"></i>Device Comments
                                </h5>
                                <?php foreach ($pending_device_comments as $comment): ?>
                                    <div class="comment-item border rounded p-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <strong><?php echo htmlspecialchars($comment['name']); ?></strong>
                                                <small class="text-muted ms-2"><?php echo htmlspecialchars($comment['email']); ?></small>
                                                <br>
                                                <small class="text-muted">
                                                    On device: <strong><?php echo htmlspecialchars($comment['device_id']); ?></strong>
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo date('M j, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                                                </small>
                                            </div>
                                            <div class="btn-group">
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <input type="hidden" name="comment_type" value="device">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <input type="hidden" name="comment_type" value="device">
                                                    <button type="submit" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <input type="hidden" name="comment_type" value="device">
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Are you sure you want to delete this comment?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Approved Comments Tab -->
            <div class="tab-pane fade" id="approved" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($recent_approved)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Approved Comments</h4>
                                <p class="text-muted">Start approving comments to see them here!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_approved as $comment): ?>
                                <div class="comment-item border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($comment['name']); ?></strong>
                                            <span class="badge bg-<?php echo $comment['type'] === 'post' ? 'primary' : 'success'; ?> ms-2">
                                                <?php echo ucfirst($comment['type']); ?>
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                <?php if ($comment['type'] === 'post'): ?>
                                                    On post: <strong><?php echo htmlspecialchars($comment['reference_title'] ?? 'Unknown Post'); ?></strong>
                                                <?php else: ?>
                                                    On device: <strong><?php echo htmlspecialchars($comment['reference_title'] ?? 'Unknown Device'); ?></strong>
                                                <?php endif; ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-success me-2">Approved</span>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                <input type="hidden" name="comment_type" value="<?php echo $comment['type']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to delete this approved comment?')"
                                                    title="Delete Comment">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>

    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>

</html>