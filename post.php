<?php
// Individual post page with SEO optimization
require_once 'database_functions.php';
$pdo = getConnection();

// Get post by slug or ID
$slug = $_GET['slug'] ?? $_GET['id'] ?? null;

if (!$slug) {
    header('Location: index.php');
    exit;
}

// Try to get post by slug first, then by ID if it's numeric
if (is_numeric($slug)) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE (slug = ? OR id = ?) AND status ILIKE 'published'");
    $stmt->execute([$slug, intval($slug)]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE slug = ? AND status ILIKE 'published'");
    $stmt->execute([$slug]);
}
$post = $stmt->fetch();

if (!$post) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Track view for this post (one per IP per day)
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    $view_stmt = $pdo->prepare("INSERT INTO content_views (content_type, content_id, ip_address, user_agent) VALUES ('post', ?, ?, ?) ON CONFLICT (content_type, content_id, ip_address, DATE(viewed_at)) DO NOTHING");
    $view_stmt->execute([$post['id'], $user_ip, $user_agent]);

    // Update view count in posts table
    $update_view_stmt = $pdo->prepare("UPDATE posts SET view_count = (SELECT COUNT(*) FROM content_views WHERE content_type = 'post' AND content_id = ?) WHERE id = ?");
    $update_view_stmt->execute([$post['id'], $post['id']]);
} catch (Exception $e) {
    // Silently ignore view tracking errors
}

// Get comments for this post (parent comments and their replies)
$comments_stmt = $pdo->prepare("
    WITH RECURSIVE comment_tree AS (
        -- Get parent comments (no parent_id)
        SELECT id, post_id, name, email, comment, status, created_at, parent_id, 0 as depth
        FROM post_comments 
        WHERE post_id = ? AND status = 'approved' AND parent_id IS NULL
        
        UNION ALL
        
        -- Get replies recursively
        SELECT pc.id, pc.post_id, pc.name, pc.email, pc.comment, pc.status, pc.created_at, pc.parent_id, ct.depth + 1
        FROM post_comments pc
        INNER JOIN comment_tree ct ON pc.parent_id = ct.id
        WHERE pc.status = 'approved'
    )
    SELECT * FROM comment_tree ORDER BY created_at ASC
");
$comments_stmt->execute([$post['id']]);
$all_comments = $comments_stmt->fetchAll();

// Organize comments into a nested structure
$comments = [];
$comment_lookup = [];

foreach ($all_comments as $comment) {
    $comment_lookup[$comment['id']] = $comment;
    if ($comment['parent_id'] === null) {
        $comment['replies'] = [];
        $comments[] = $comment;
    }
}

// Add replies to their parent comments
foreach ($all_comments as $comment) {
    if ($comment['parent_id'] !== null) {
        for ($i = 0; $i < count($comments); $i++) {
            if ($comments[$i]['id'] == $comment['parent_id']) {
                if (!isset($comments[$i]['replies'])) {
                    $comments[$i]['replies'] = [];
                }
                $comments[$i]['replies'][] = $comment;
                break;
            }
            // Check nested replies
            if (isset($comments[$i]['replies'])) {
                for ($j = 0; $j < count($comments[$i]['replies']); $j++) {
                    if ($comments[$i]['replies'][$j]['id'] == $comment['parent_id']) {
                        if (!isset($comments[$i]['replies'][$j]['replies'])) {
                            $comments[$i]['replies'][$j]['replies'] = [];
                        }
                        $comments[$i]['replies'][$j]['replies'][] = $comment;
                        break;
                    }
                }
            }
        }
    }
}

// Handle new comment submission
if ($_POST && isset($_POST['submit_comment'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    if ($name && $email && $comment) {
        $insert_stmt = $pdo->prepare("INSERT INTO post_comments (post_id, name, email, comment, parent_id, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        if ($insert_stmt->execute([$post['id'], $name, $email, $comment, $parent_id])) {
            $success_message = $parent_id ? "Thank you! Your reply has been submitted and is pending approval." : "Thank you! Your comment has been submitted and is pending approval.";
        } else {
            $error_message = "Sorry, there was an error submitting your comment. Please try again.";
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

// Parse gallery images
$gallery_images = [];
if ($post['media_gallery']) {
    $gallery_data = json_decode($post['media_gallery'], true);
    if ($gallery_data && is_array($gallery_data)) {
        $gallery_images = $gallery_data;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['meta_title'] ?: $post['title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($post['meta_description'] ?: $post['short_description']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($post['tags'] ?? ''); ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?php echo htmlspecialchars($post['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($post['short_description'] ?? ''); ?>">
    <?php if ($post['featured_image']): ?>
        <meta property="og:image" content="<?php echo $_SERVER['HTTP_HOST'] . '/' . htmlspecialchars($post['featured_image']); ?>">
    <?php endif; ?>

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($post['title']); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($post['short_description'] ?? ''); ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">

    <style>
        .post-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0 2rem;
        }

        .post-meta {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .post-content {
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .post-content h1,
        .post-content h2,
        .post-content h3 {
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        .comment-form {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
        }

        .comment-item {
            background: white;
            border-left: 4px solid #667eea;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mobile-alt me-2"></i>TechSpecs
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Home</a>
                <a class="nav-link" href="login.php">Admin</a>
            </div>
        </div>
    </nav>

    <!-- Post Header -->
    <div class="post-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <div class="post-meta">
                        <span class="badge bg-light text-primary fs-6 me-3">
                            <i class="fas fa-folder me-1"></i><?php echo htmlspecialchars($post['categories'] ?? 'General'); ?>
                        </span>
                        <span class="text-light">
                            <i class="fas fa-calendar-alt me-1"></i>
                            <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                        </span>
                        <span class="text-light ms-3">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($post['author'] ?? 'Admin'); ?>
                        </span>
                    </div>
                    <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($post['title']); ?></h1>
                    <?php if ($post['short_description']): ?>
                        <p class="lead"><?php echo htmlspecialchars($post['short_description']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Post Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Featured Image -->
                <?php if ($post['featured_image']): ?>
                    <div class="text-center mb-4">
                        <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                            class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($post['title']); ?>">
                    </div>
                <?php endif; ?>

                <!-- Content Body -->
                <div class="post-content">
                    <?php echo $post['content_body']; ?>
                </div>

                <!-- Gallery -->
                <?php if (!empty($gallery_images)): ?>
                    <div class="my-5">
                        <h4 class="mb-4"><i class="fas fa-images me-2"></i>Gallery</h4>
                        <div class="row">
                            <?php foreach ($gallery_images as $image): ?>
                                <div class="col-md-4 mb-4">
                                    <img src="<?php echo htmlspecialchars($image); ?>"
                                        class="img-fluid rounded shadow-sm" alt="Gallery Image"
                                        style="height: 200px; width: 100%; object-fit: cover;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tags -->
                <?php if ($post['tags']): ?>
                    <div class="my-4">
                        <h6>Tags:</h6>
                        <?php
                        $tags = explode(',', $post['tags']);
                        foreach ($tags as $tag): ?>
                            <span class="badge bg-secondary me-2"><?php echo htmlspecialchars(trim($tag)); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <hr class="my-5">

                <!-- Back to Home -->
                <div class="text-center mb-4">
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>
                </div>

                <!-- Comments Section -->
                <div class="comments-section">
                    <h4 class="mb-4">
                        <i class="fas fa-comments me-2"></i>Comments (<?php echo count($all_comments); ?>)
                    </h4>

                    <!-- Success/Error Messages -->
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Comment Form -->
                    <div class="comment-form mb-5" id="main-comment-form">
                        <h5 class="mb-3">Leave a Comment</h5>
                        <form method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="comment" class="form-label">Comment *</label>
                                <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                            </div>
                            <input type="hidden" name="parent_id" value="">
                            <button type="submit" name="submit_comment" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Submit Comment
                            </button>
                            <button type="button" class="btn btn-secondary ms-2 cancel-reply" style="display: none;">
                                <i class="fas fa-times me-2"></i>Cancel Reply
                            </button>
                        </form>
                    </div>

                    <!-- Existing Comments -->
                    <?php if (empty($comments)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-comment-slash fa-3x mb-3"></i>
                            <h5>No comments yet</h5>
                            <p>Be the first to leave a comment!</p>
                        </div>
                    <?php else: ?>
                        <?php
                        function displayComment($comment, $depth = 0)
                        {
                            $margin_left = $depth * 40; // 40px per depth level
                        ?>
                            <div class="comment-item mb-4" style="margin-left: <?php echo $margin_left; ?>px;">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <strong class="text-primary"><?php echo htmlspecialchars($comment['name']); ?></strong>
                                                <small class="text-muted ms-2">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo date('M j, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                                                </small>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary reply-btn"
                                                data-comment-id="<?php echo $comment['id']; ?>"
                                                data-comment-author="<?php echo htmlspecialchars($comment['name']); ?>">
                                                <i class="fas fa-reply me-1"></i>Reply
                                            </button>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                    </div>
                                </div>

                                <!-- Placeholder for reply form -->
                                <div class="reply-form-placeholder mt-3" data-comment-id="<?php echo $comment['id']; ?>"></div>

                                <!-- Display replies -->
                                <?php if (isset($comment['replies']) && !empty($comment['replies'])): ?>
                                    <?php foreach ($comment['replies'] as $reply): ?>
                                        <?php displayComment($reply, $depth + 1); ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php
                        }

                        foreach ($comments as $comment):
                            displayComment($comment);
                        endforeach;
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2025 TechSpecs. Professional Mobile Device Management System.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const commentForm = document.getElementById('main-comment-form');
            const originalFormParent = commentForm.parentNode;
            const parentIdInput = commentForm.querySelector('input[name="parent_id"]');
            const formTitle = commentForm.querySelector('h5');
            const submitButton = commentForm.querySelector('button[type="submit"]');
            const cancelButton = commentForm.querySelector('.cancel-reply');

            // Handle reply button clicks
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('reply-btn') || e.target.closest('.reply-btn')) {
                    e.preventDefault();
                    const button = e.target.closest('.reply-btn');
                    const commentId = button.getAttribute('data-comment-id');
                    const commentAuthor = button.getAttribute('data-comment-author');
                    const placeholder = document.querySelector(`.reply-form-placeholder[data-comment-id="${commentId}"]`);

                    // Move form to reply position
                    placeholder.appendChild(commentForm);

                    // Update form for reply
                    parentIdInput.value = commentId;
                    formTitle.textContent = `Reply to ${commentAuthor}`;
                    submitButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Reply';
                    cancelButton.style.display = 'inline-block';

                    // Clear form fields
                    commentForm.querySelector('#name').value = '';
                    commentForm.querySelector('#email').value = '';
                    commentForm.querySelector('#comment').value = '';

                    // Focus on name field
                    commentForm.querySelector('#name').focus();
                }
            });

            // Handle cancel reply
            cancelButton.addEventListener('click', function(e) {
                e.preventDefault();

                // Move form back to original position
                originalFormParent.appendChild(commentForm);

                // Reset form
                parentIdInput.value = '';
                formTitle.textContent = 'Leave a Comment';
                submitButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Comment';
                cancelButton.style.display = 'none';

                // Clear form fields
                commentForm.querySelector('#name').value = '';
                commentForm.querySelector('#email').value = '';
                commentForm.querySelector('#comment').value = '';
            });
        });
    </script>
</body>

</html>