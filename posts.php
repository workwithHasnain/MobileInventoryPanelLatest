<?php
require_once 'auth.php';

// Require login for this page
requireLogin();

$user_role = $_SESSION['role'] ?? 'employee';

// Initialize database
require_once 'database_functions.php';
$pdo = getConnection();

// Handle post actions (delete)
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete' && isset($_POST['post_id'])) {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$_POST['post_id']]);
        $success = "Post deleted successfully!";
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR author LIKE ? OR content_body LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($category_filter)) {
    $where_conditions[] = "categories LIKE ?";
    $params[] = "%$category_filter%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get posts with filters and counts
$query = "SELECT p.*, 
    COALESCE(p.view_count, 0) as view_count,
    (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.id) as comment_count
    FROM posts p $where_clause ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter dropdown
$categories_stmt = $pdo->query("SELECT * FROM post_categories ORDER BY name");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Post Management";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-newspaper"></i> Post Management</h2>
                <a href="add_post.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Post
                </a>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search posts...">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="Draft" <?php echo $status_filter === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="Published" <?php echo $status_filter === 'Published' ? 'selected' : ''; ?>>Published</option>
                                <option value="Archived" <?php echo $status_filter === 'Archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['name']); ?>" 
                                            <?php echo $category_filter === $category['name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="posts.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results Count -->
            <div class="mb-3">
                <p class="text-muted">
                    Showing <?php echo count($posts); ?> posts
                    <?php if (!empty($search) || !empty($status_filter) || !empty($category_filter)): ?>
                        - <a href="posts.php" class="text-decoration-none">Clear all filters</a>
                    <?php endif; ?>
                </p>
            </div>

            <!-- Posts Table -->
            <?php if (empty($posts)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No posts found</h4>
                    <?php if (!empty($search) || !empty($status_filter) || !empty($category_filter)): ?>
                        <p class="text-muted">Try adjusting your search filters</p>
                        <a href="posts.php" class="btn btn-outline-primary">Show All Posts</a>
                    <?php else: ?>
                        <p class="text-muted">Start by creating your first post</p>
                        <a href="add_post.php" class="btn btn-primary">Create New Post</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Featured Image</th>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Status</th>
                                        <th>Categories</th>
                                        <th>Stats</th>
                                        <th>Publish Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($posts as $post): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($post['featured_image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                                         alt="Featured" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light d-flex align-items-center justify-content-center" 
                                                         style="width: 60px; height: 60px;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-grow-1">
                                                        <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                                        <?php if (isset($post['is_featured']) && $post['is_featured']): ?>
                                                            <i class="fas fa-star text-warning ms-2" title="Featured Post"></i>
                                                        <?php endif; ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($post['slug']); ?></small>
                                                        <?php if (!empty($post['short_description'])): ?>
                                                            <br><small class="text-muted"><?php echo substr(htmlspecialchars($post['short_description']), 0, 80) . '...'; ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($post['author']); ?></td>
                                            <td>
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
                                            </td>
                                            <td>
                                                <?php 
                                                $post_categories = json_decode($post['categories'], true) ?: [];
                                                foreach ($post_categories as $cat): ?>
                                                    <span class="badge bg-info me-1"><?php echo htmlspecialchars($cat); ?></span>
                                                <?php endforeach; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <small class="text-muted mb-1">
                                                        <i class="fas fa-eye me-1"></i><?php echo number_format($post['view_count']); ?> views
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="fas fa-comments me-1"></i><?php echo number_format($post['comment_count']); ?> comments
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($post['publish_date'])); ?>
                                                <br><small class="text-muted"><?php echo date('g:i A', strtotime($post['publish_date'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view_post.php?id=<?php echo $post['id']; ?>" 
                                                       class="btn btn-outline-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_post.php?id=<?php echo $post['id']; ?>" 
                                                       class="btn btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($user_role === 'admin'): ?>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="deletePost(<?php echo $post['id']; ?>, '<?php echo htmlspecialchars($post['title']); ?>')" 
                                                                title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the post "<span id="postTitle"></span>"?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="post_id" id="deletePostId">
                    <button type="submit" class="btn btn-danger">Delete Post</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deletePost(postId, postTitle) {
    document.getElementById('deletePostId').value = postId;
    document.getElementById('postTitle').textContent = postTitle;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        var bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php include 'includes/footer.php'; ?>