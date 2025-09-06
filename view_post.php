<?php
require_once 'auth.php';

// Require login for this page
requireLogin();

// Get post ID
$post_id = $_GET['id'] ?? null;
if (!$post_id) {
    header('Location: posts.php');
    exit();
}

// Initialize database
require_once 'database_functions.php';
$pdo = getConnection();

// Get post data
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: posts.php');
    exit();
}

// Decode JSON fields for display
$post_categories = json_decode($post['categories'], true) ?: [];
$post_media_gallery = json_decode($post['media_gallery'], true) ?: [];

$page_title = htmlspecialchars($post['title']);
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-eye"></i> View Post</h2>
                <div>
                    <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="btn btn-primary me-2">
                        <i class="fas fa-edit"></i> Edit Post
                    </a>
                    <a href="posts.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Posts
                    </a>
                </div>
            </div>

            <!-- Post Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h1 class="display-6 mb-3"><?php echo htmlspecialchars($post['title']); ?></h1>
                            <div class="d-flex flex-wrap gap-3 mb-3">
                                <span class="text-muted">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author']); ?>
                                </span>
                                <span class="text-muted">
                                    <i class="fas fa-calendar"></i> <?php echo date('F j, Y', strtotime($post['publish_date'])); ?>
                                </span>
                                <span class="text-muted">
                                    <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($post['publish_date'])); ?>
                                </span>
                                <?php
                                $status_class = match($post['status']) {
                                    'Published' => 'bg-success',
                                    'Draft' => 'bg-warning',
                                    'Archived' => 'bg-secondary',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($post['status']); ?>
                                </span>
                            </div>
                            <p class="text-muted">
                                <strong>URL:</strong> <code><?php echo htmlspecialchars($post['slug']); ?></code>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <?php if (!empty($post['featured_image'])): ?>
                                <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                     alt="Featured image" class="img-fluid rounded">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Post Content -->
            <div class="row">
                <div class="col-md-8">
                    <!-- Short Description -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-quote-right"></i> Short Description</h5>
                        </div>
                        <div class="card-body">
                            <p class="lead"><?php echo htmlspecialchars($post['short_description']); ?></p>
                        </div>
                    </div>

                    <!-- Content Body -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-file-text"></i> Content</h5>
                        </div>
                        <div class="card-body">
                            <div class="content-body" style="line-height: 1.6;">
                                <?php 
                                // Display HTML content (since it's from TinyMCE editor)
                                // Basic sanitization - in production, use HTMLPurifier or similar
                                $allowed_tags = '<p><br><strong><b><em><i><u><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><a><img>';
                                echo strip_tags($post['content_body'], $allowed_tags);
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Media Gallery -->
                    <?php if (!empty($post_media_gallery)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-images"></i> Media Gallery</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($post_media_gallery as $index => $image): ?>
                                        <div class="col-md-4 mb-3">
                                            <img src="<?php echo htmlspecialchars($image); ?>" 
                                                 alt="Gallery image <?php echo $index + 1; ?>" 
                                                 class="img-fluid rounded shadow-sm"
                                                 data-bs-toggle="modal" 
                                                 data-bs-target="#imageModal<?php echo $index; ?>"
                                                 style="cursor: pointer;">
                                        </div>

                                        <!-- Image Modal -->
                                        <div class="modal fade" id="imageModal<?php echo $index; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Gallery Image <?php echo $index + 1; ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <img src="<?php echo htmlspecialchars($image); ?>" 
                                                             alt="Gallery image <?php echo $index + 1; ?>" 
                                                             class="img-fluid">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Categories -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-folder"></i> Categories</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($post_categories)): ?>
                                <?php foreach ($post_categories as $category): ?>
                                    <span class="badge bg-primary me-1 mb-1"><?php echo htmlspecialchars($category); ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No categories assigned</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-tags"></i> Tags</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($post['tags'])): ?>
                                <?php 
                                $tags = array_map('trim', explode(',', $post['tags']));
                                foreach ($tags as $tag): ?>
                                    <span class="badge bg-secondary me-1 mb-1"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No tags assigned</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- SEO Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-search"></i> SEO Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Meta Title:</strong>
                                <p class="text-muted"><?php echo htmlspecialchars($post['meta_title'] ?: $post['title']); ?></p>
                            </div>
                            <div class="mb-3">
                                <strong>Meta Description:</strong>
                                <p class="text-muted"><?php echo htmlspecialchars($post['meta_description'] ?: 'No meta description set'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Post Statistics -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Post Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Created:</strong>
                                <p class="text-muted small"><?php echo date('F j, Y g:i A', strtotime($post['created_at'])); ?></p>
                            </div>
                            <div class="mb-2">
                                <strong>Last Updated:</strong>
                                <p class="text-muted small"><?php echo date('F j, Y g:i A', strtotime($post['updated_at'])); ?></p>
                            </div>
                            <div class="mb-2">
                                <strong>Content Length:</strong>
                                <p class="text-muted small"><?php echo number_format(strlen($post['content_body'])); ?> characters</p>
                            </div>
                            <?php if (!empty($post_media_gallery)): ?>
                                <div class="mb-2">
                                    <strong>Gallery Images:</strong>
                                    <p class="text-muted small"><?php echo count($post_media_gallery); ?> images</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>