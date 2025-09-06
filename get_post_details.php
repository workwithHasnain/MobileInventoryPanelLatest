<?php
require_once 'database_functions.php';
$pdo = getConnection();

$post_id = $_GET['id'] ?? '';

if (!$post_id) {
    echo '<div class="alert alert-danger">Invalid post ID.</div>';
    exit;
}

// Get post details (case-insensitive status check)
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND status ILIKE 'published'");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    echo '<div class="alert alert-danger">Post not found.</div>';
    exit;
}

// Get comments for this post
$comments_stmt = $pdo->prepare("SELECT * FROM post_comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at DESC");
$comments_stmt->execute([$post_id]);
$comments = $comments_stmt->fetchAll();
?>

<div class="post-details">
    <!-- Post Header -->
    <div class="mb-4">
        <?php if (isset($post['featured_image']) && !empty($post['featured_image'])): ?>
            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                class="img-fluid rounded mb-3" alt="Post Image" style="max-height: 300px; width: 100%; object-fit: cover;">
        <?php endif; ?>

        <div class="mb-3">
            <span class="badge bg-primary me-2"><?php echo htmlspecialchars($post['categories'] ?? 'General'); ?></span>
            <small class="text-muted">
                <i class="fas fa-calendar-alt me-1"></i>
                <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
            </small>
            <small class="text-muted ms-3">
                <i class="fas fa-user me-1"></i>
                <?php echo htmlspecialchars($post['author'] ?? 'Unknown Author'); ?>
            </small>
        </div>

        <h3><?php echo htmlspecialchars($post['title'] ?? 'Untitled Post'); ?></h3>
        <p class="lead text-muted"><?php echo htmlspecialchars($post['short_description'] ?? 'No description available'); ?></p>
    </div>

    <!-- Post Content -->
    <div class="mb-4">
        <h5><i class="fas fa-file-text me-2"></i>Content</h5>
        <div class="content-body" style="line-height: 1.6;">
            <?php
            // Display HTML content with basic sanitization
            $allowed_tags = '<p><br><strong><b><em><i><u><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><a><img><span><div>';
            echo strip_tags($post['content_body'], $allowed_tags);
            ?>
        </div>
    </div>

    <!-- Gallery Images -->
    <?php if (isset($post['gallery_images']) && !empty($post['gallery_images'])): ?>
        <div class="mb-4">
            <h5><i class="fas fa-images me-2"></i>Gallery</h5>
            <div class="row">
                <?php
                $gallery_images = json_decode($post['gallery_images'], true);
                if ($gallery_images && is_array($gallery_images)):
                    foreach ($gallery_images as $image): ?>
                        <div class="col-md-4 mb-3">
                            <img src="<?php echo htmlspecialchars($image); ?>"
                                class="img-fluid rounded" alt="Gallery Image">
                        </div>
                <?php endforeach;
                endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Comments Section -->
    <div class="comment-section">
        <h5><i class="fas fa-comments me-2"></i>Comments (<?php echo count($comments); ?>)</h5>

        <!-- Add Comment Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Add Your Comment</h6>
            </div>
            <div class="card-body">
                <form method="post" action="index.php#posts">
                    <input type="hidden" name="action" value="comment_post">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="comment" class="form-label">Comment <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="comment" name="comment" rows="4"
                            placeholder="Share your thoughts about this post..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Submit Comment
                    </button>
                </form>
            </div>
        </div>

        <!-- Display Comments -->
        <?php if (empty($comments)): ?>
            <div class="text-center py-4">
                <i class="fas fa-comments fa-2x text-muted mb-3"></i>
                <p class="text-muted">No comments yet. Be the first to comment!</p>
            </div>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <div class="comment-item">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <strong><?php echo htmlspecialchars($comment['name']); ?></strong>
                            <small class="text-muted ms-2">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo date('M j, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>