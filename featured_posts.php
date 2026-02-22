<?php
// Featured Posts - Public page for browsing posts by tags
// No authentication required

// Database connection
require_once 'database_functions.php';
$pdo = getConnection();

// Get selected tag from URL
$selected_tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';

// Function to get all unique tags from published posts
function getAllTags($pdo)
{
    try {
        $stmt = $pdo->prepare("
            SELECT tags 
            FROM posts 
            WHERE status = 'Published' 
            AND is_featured = true
            AND tags IS NOT NULL 
            AND tags != ''
        ");
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $all_tags = [];
        foreach ($posts as $post) {
            if (!empty($post['tags'])) {
                $post_tags = array_map('trim', explode(',', $post['tags']));
                foreach ($post_tags as $tag) {
                    if (!empty($tag)) {
                        $all_tags[] = strtolower($tag);
                    }
                }
            }
        }

        // Count tag frequency and sort by popularity
        $tag_counts = array_count_values($all_tags);
        arsort($tag_counts);

        return $tag_counts;
    } catch (PDOException $e) {
        return [];
    }
}

// Function to get posts by tag or all featured posts
function getPostsByTag($pdo, $tag = '')
{
    try {
        if (!empty($tag)) {
            $stmt = $pdo->prepare("
                SELECT * FROM posts 
                WHERE status = 'Published' 
                AND is_featured = true
                AND (LOWER(tags) LIKE LOWER(?) OR LOWER(tags) LIKE LOWER(?) OR LOWER(tags) LIKE LOWER(?) OR LOWER(tags) LIKE LOWER(?))
                ORDER BY created_at DESC
            ");
            $tag_pattern1 = "%$tag%";
            $tag_pattern2 = "$tag,%";
            $tag_pattern3 = "%, $tag%";
            $tag_pattern4 = "%, $tag,%";
            $stmt->execute([$tag_pattern1, $tag_pattern2, $tag_pattern3, $tag_pattern4]);
        } else {
            $stmt = $pdo->prepare("
                SELECT * FROM posts 
                WHERE status = 'Published' 
                AND is_featured = true
                ORDER BY created_at DESC
            ");
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Get all tags and posts
$all_tags = getAllTags($pdo);
$posts = getPostsByTag($pdo, $selected_tag);

// Get popular tags (top 10)
$popular_tags = array_slice($all_tags, 0, 10, true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Posts<?php echo !empty($selected_tag) ? ' - ' . htmlspecialchars(ucfirst($selected_tag)) : ''; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .tag-cloud {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .tag-item {
            display: inline-block;
            margin: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 25px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .tag-item:hover {
            background: #fff;
            color: #667eea;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .tag-item.active {
            background: #667eea;
            color: white;
            border-color: #fff;
        }

        .tag-count {
            background: rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            padding: 0.2rem 0.5rem;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }

        .post-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-radius: 15px;
            overflow: hidden;
            border: none;
        }

        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .post-thumbnail {
            height: 200px;
            object-fit: cover;
            border-radius: 15px 15px 0 0;
        }

        .post-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .post-tags {
            margin-top: 1rem;
        }

        .post-tag {
            display: inline-block;
            background: #f8f9fa;
            color: #495057;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            margin: 0.2rem;
            text-decoration: none;
        }

        .post-tag:hover {
            background: #667eea;
            color: white;
            text-decoration: none;
        }

        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
            border-radius: 0 0 50px 50px;
            margin-bottom: 3rem;
        }

        .clear-filter-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .clear-filter-btn:hover {
            background: white;
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-mobile-alt me-2"></i>Device Catalog
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="featured_posts.php">
                            <i class="fas fa-star me-1"></i>Featured Posts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="compare.php">
                            <i class="fas fa-balance-scale me-1"></i>Compare Phones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="phone_finder.php">
                            <i class="fas fa-search me-1"></i>Phone Finder
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Admin Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold">
                <i class="fas fa-star me-3"></i>Featured Posts
            </h1>
            <p class="lead">Discover our latest articles and insights</p>
            <?php if (!empty($selected_tag)): ?>
                <div class="mt-4">
                    <p class="h5">Showing posts tagged with: <strong><?php echo htmlspecialchars(ucfirst($selected_tag)); ?></strong></p>
                    <a href="featured_posts.php" class="clear-filter-btn">
                        <i class="fas fa-times me-2"></i>Clear Filter
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <!-- Popular Tags Cloud -->
        <?php if (!empty($popular_tags)): ?>
            <div class="tag-cloud">
                <h3 class="text-white mb-4 text-center">
                    <i class="fas fa-tags me-2"></i>Popular Tags
                </h3>
                <div class="text-center">
                    <?php foreach ($popular_tags as $tag => $count): ?>
                        <a href="featured_posts.php?tag=<?php echo urlencode($tag); ?>"
                            class="tag-item <?php echo (strtolower($selected_tag) === strtolower($tag)) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars(ucfirst($tag)); ?>
                            <span class="tag-count"><?php echo $count; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Posts Grid -->
        <?php if (!empty($posts)): ?>
            <div class="row">
                <div class="col-12 mb-4">
                    <h2 class="h4">
                        <?php if (!empty($selected_tag)): ?>
                            Posts tagged with "<?php echo htmlspecialchars(ucfirst($selected_tag)); ?>" (<?php echo count($posts); ?> found)
                        <?php else: ?>
                            All Featured Posts (<?php echo count($posts); ?> total)
                        <?php endif; ?>
                    </h2>
                </div>

                <?php foreach ($posts as $post): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card post-card h-100 shadow-sm <?php echo ($post['is_featured'] ?? false) ? 'border-warning' : ''; ?>">
                            <?php if ($post['is_featured'] ?? false): ?>
                                <div class="position-absolute top-0 end-0 m-2" style="z-index: 10;">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-star me-1"></i>Featured
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($post['featured_image'])): ?>
                                <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                                    class="post-thumbnail"
                                    alt="<?php echo htmlspecialchars($post['title']); ?>">
                            <?php else: ?>
                                <div class="post-thumbnail bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">
                                    <a href="post.php?slug=<?php echo urlencode($post['slug']); ?>"
                                        class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                        <?php if ($post['is_featured'] ?? false): ?>
                                            <i class="fas fa-star text-warning ms-2" title="Featured Post"></i>
                                        <?php endif; ?>
                                    </a>
                                </h5>

                                <p class="card-text text-muted flex-grow-1">
                                    <?php
                                    $content = $post['content_body'] ?? '';
                                    $excerpt = !empty($post['excerpt']) ? $post['excerpt'] : (!empty($content) ? strip_tags($content) : '');
                                    echo htmlspecialchars(substr($excerpt, 0, 120)) . (strlen($excerpt) > 120 ? '...' : '');
                                    ?>
                                </p>

                                <div class="post-meta mb-3">
                                    <small>
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('M j, Y', strtotime($post['created_at'])); ?>

                                        <?php if (!empty($post['category'])): ?>
                                            <span class="ms-3">
                                                <i class="fas fa-folder me-1"></i>
                                                <?php echo htmlspecialchars($post['category']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </small>
                                </div>

                                <?php if (!empty($post['tags'])): ?>
                                    <div class="post-tags">
                                        <?php
                                        $tags = array_map('trim', explode(',', $post['tags']));
                                        foreach ($tags as $tag):
                                            if (!empty($tag)):
                                        ?>
                                                <a href="featured_posts.php?tag=<?php echo urlencode(trim($tag)); ?>"
                                                    class="post-tag">
                                                    #<?php echo htmlspecialchars($tag); ?>
                                                </a>
                                        <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-auto pt-3">
                                    <a href="post.php?slug=<?php echo urlencode($post['slug']); ?>"
                                        class="btn btn-primary btn-sm">
                                        <i class="fas fa-arrow-right me-1"></i>Read More
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- No Posts Found -->
            <div class="text-center py-5">
                <i class="fas fa-search fa-5x text-muted mb-4"></i>
                <h3 class="text-muted">No Posts Found</h3>
                <?php if (!empty($selected_tag)): ?>
                    <p class="text-muted">No posts found for the tag "<?php echo htmlspecialchars($selected_tag); ?>"</p>
                    <a href="featured_posts.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>View All Posts
                    </a>
                <?php else: ?>
                    <p class="text-muted">There are no published posts available at the moment.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-mobile-alt me-2"></i>Device Catalog</h5>
                    <p class="mb-0">Your comprehensive mobile device resource</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Device Catalog. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
</body>

</html>