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

            <!-- Results Count and Sorter -->
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <p class="text-muted mb-0">
                    Showing <span id="postsCountShown">0</span> of <span id="postsCountTotal">0</span> posts
                </p>
                <div style="width: 200px;">
                    <label for="postSorter" class="form-label mb-0 me-2 d-inline">Sort by:</label>
                    <select class="form-select form-select-sm d-inline" id="postSorter" style="width: auto;">
                        <option value="default">Default</option>
                        <option value="views-desc">Most Views</option>
                        <option value="views-asc">Least Views</option>
                        <option value="comments-desc">Most Comments</option>
                        <option value="comments-asc">Least Comments</option>
                    </select>
                </div>
            </div>

            <!-- Posts Container (AJAX-populated) -->
            <div id="postsContainer">
                <div class="text-center py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>

            <!-- Load More Button -->
            <div class="text-center mt-4" id="loadMoreContainer" style="display: none;">
                <button class="btn btn-primary" id="loadMoreBtn">
                    <i class="fas fa-chevron-down me-2"></i>Load More Posts
                </button>
                <p class="text-muted mt-2"><small>Page <span id="currentPage">1</span> of <span id="totalPages">1</span></small></p>
            </div>

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
    let currentPage = 1;
    let totalPages = 1;
    let currentSort = 'default';
    let tableElement = null;
    const userRole = '<?php echo htmlspecialchars($user_role); ?>';

    function getFilterParams() {
        return new URLSearchParams({
            search: document.getElementById('search').value,
            status: document.getElementById('status').value,
            author: document.getElementById('category').value // Using category as author filter
        });
    }

    function createPostRow(post) {
        let statusClass = 'bg-secondary';
        switch (post.status) {
            case 'Published':
                statusClass = 'bg-success';
                break;
            case 'Draft':
                statusClass = 'bg-warning text-dark';
                break;
            case 'Archived':
                statusClass = 'bg-secondary';
                break;
        }

        let imageHtml = post.featured_image ?
            `<img src="${escapeHtml(post.featured_image)}" alt="Featured" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">` :
            `<div class="bg-light d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;"><i class="fas fa-image text-muted"></i></div>`;

        const deleteBtn = userRole === 'admin' ?
            `<button type="button" class="btn btn-outline-danger btn-sm"
                onclick="deletePost(${post.id}, '${escapeHtml(post.title)}')"
                title="Delete">
                <i class="fas fa-trash"></i>
            </button>` :
            '';

        return `
            <tr>
                <td>${imageHtml}</td>
                <td>
                    <div class="d-flex align-items-start">
                        <div class="flex-grow-1">
                            <strong>${escapeHtml(post.title)}</strong>
                            <br><small class="text-muted">${escapeHtml(post.slug)}</small>
                            ${post.description ? '<br><small class="text-muted">' + escapeHtml(post.description.substring(0, 80)) + '...</small>' : ''}
                        </div>
                    </div>
                </td>
                <td>${escapeHtml(post.author)}</td>
                <td><span class="badge ${statusClass}">${escapeHtml(post.status)}</span></td>
                <td></td>
                <td>
                    <div class="d-flex flex-column">
                        <small class="text-muted mb-1">
                            <i class="fas fa-eye me-1"></i>${post.view_count} views
                        </small>
                        <small class="text-muted">
                            <i class="fas fa-comments me-1"></i>${post.comment_count} comments
                        </small>
                    </div>
                </td>
                <td>
                    ${new Date(post.created_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}
                    <br><small class="text-muted">${new Date(post.created_at).toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit'})}</small>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="view_post.php?id=${post.id}" class="btn btn-outline-info" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="edit_post.php?id=${post.id}" class="btn btn-outline-primary" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        ${deleteBtn}
                    </div>
                </td>
            </tr>
        `;
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function loadPosts(pageNum = 1, sort = 'default') {
        currentPage = pageNum;
        currentSort = sort;

        const params = getFilterParams();
        params.append('page', pageNum);
        params.append('sort', sort);

        const container = document.getElementById('postsContainer');

        if (pageNum === 1) {
            container.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            tableElement = null;
        }

        fetch(`api_get_posts.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    totalPages = data.total_pages;

                    if (pageNum === 1) {
                        tableElement = document.createElement('div');
                        tableElement.className = 'card';
                        tableElement.innerHTML = `
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="postsTable">
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
                                        <tbody id="postsTableBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        `;
                    }

                    if (data.posts.length === 0 && pageNum === 1) {
                        container.innerHTML = `
                            <div class="text-center py-5">
                                <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No posts found</h4>
                                <p class="text-muted">Try adjusting your search filters</p>
                                <a href="posts.php" class="btn btn-outline-primary">Show All Posts</a>
                            </div>
                        `;
                        document.getElementById('loadMoreContainer').style.display = 'none';
                    } else {
                        const tbody = tableElement.querySelector('#postsTableBody');
                        data.posts.forEach((post) => {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = createPostRow(post);
                            tbody.appendChild(tempDiv.firstElementChild);
                        });

                        if (pageNum === 1) {
                            container.innerHTML = '';
                            container.appendChild(tableElement);
                        }

                        const shownCount = Math.min(pageNum * 50, data.total);
                        document.getElementById('postsCountShown').textContent = shownCount;
                        document.getElementById('postsCountTotal').textContent = data.total;
                        document.getElementById('currentPage').textContent = pageNum;
                        document.getElementById('totalPages').textContent = totalPages;

                        document.getElementById('loadMoreContainer').style.display = pageNum < totalPages ? 'block' : 'none';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (pageNum === 1) {
                    container.innerHTML = '<div class="alert alert-danger">Error loading posts</div>';
                }
            });
    }

    document.getElementById('postSorter').addEventListener('change', function() {
        loadPosts(1, this.value);
    });

    document.getElementById('loadMoreBtn').addEventListener('click', function() {
        loadPosts(currentPage + 1, currentSort);
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    function deletePost(postId, postTitle) {
        document.getElementById('deletePostId').value = postId;
        document.getElementById('postTitle').textContent = postTitle;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    // Load initial posts on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadPosts(1, 'default');
    });
</script>

<?php include 'includes/footer.php'; ?>