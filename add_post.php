<?php
ob_start();
require_once 'auth.php';

// Require login for this page
requireLogin();

$user_role = $_SESSION['role'] ?? 'employee';
$current_user = $_SESSION['username'] ?? 'Unknown';

// Initialize database
require_once 'database_functions.php';
$pdo = getConnection();

$errors = [];
$success = '';

// Get categories for dropdown
$categories_stmt = $pdo->query("SELECT * FROM post_categories ORDER BY name");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to generate slug from title
function generateSlug($title)
{
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return trim($slug, '-');
}

// Handle form submission
if ($_POST) {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $author = trim($_POST['author'] ?? $current_user);
    $publish_date = $_POST['publish_date'] ?? '';
    $short_description = trim($_POST['short_description'] ?? '');
    $content_body = $_POST['content_body'] ?? '';
    $selected_categories = $_POST['categories'] ?? [];
    $tags = trim($_POST['tags'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $status = $_POST['status'] ?? 'Draft';
    $is_featured = isset($_POST['is_featured']) && $_POST['is_featured'] ? 1 : 0;

    // Validation
    if (empty($title)) {
        $errors[] = "Title is required.";
    }

    if (empty($slug)) {
        $slug = generateSlug($title);
    }

    if (empty($publish_date)) {
        $errors[] = "Publish date is required.";
    }

    if (empty($short_description)) {
        $errors[] = "Short description is required.";
    } elseif (strlen($short_description) > 200) {
        $errors[] = "Short description must be 200 characters or less.";
    }

    if (empty($content_body)) {
        $errors[] = "Content body is required.";
    }

    // Clean and validate HTML content from editor
    $content_body = trim($content_body);
    if (!empty($content_body)) {
        // Sanitization - only allow semantic tags (no divs/spans)
        $allowed_tags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><a><img>';
        $content_body = strip_tags($content_body, $allowed_tags);

        // Remove all inline style attributes and class attributes
        $content_body = preg_replace('/ style="[^"]*"/', '', $content_body);
        $content_body = preg_replace('/ class="[^"]*"/', '', $content_body);
        $content_body = preg_replace('/ id="[^"]*"/', '', $content_body);
        $content_body = preg_replace('/ width="[^"]*"/', '', $content_body);
        $content_body = preg_replace('/ height="[^"]*"/', '', $content_body);

        // Convert <b> to <strong> and <i> to <em> for semantic HTML
        $content_body = str_replace(['<b>', '</b>', '<b/>', '<b />'], ['<strong>', '</strong>', '<strong />', '<strong />'], $content_body);
        $content_body = str_replace(['<i>', '</i>', '<i/>', '<i />'], ['<em>', '</em>', '<em />', '<em />'], $content_body);
    }

    // Handle featured image upload
    $featured_image = '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/posts/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_info = pathinfo($_FILES['featured_image']['name']);
        $file_extension = strtolower($file_info['extension']);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'featured_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
                $featured_image = $upload_path;
            } else {
                $errors[] = "Failed to upload featured image.";
            }
        } else {
            $errors[] = "Featured image must be a valid image file (JPG, PNG, GIF, WebP).";
        }
    } else {
        $errors[] = "Featured image is required.";
    }

    // Handle media gallery upload
    $media_gallery = [];
    if (isset($_FILES['media_gallery']) && !empty($_FILES['media_gallery']['name'][0])) {
        $upload_dir = 'uploads/posts/gallery/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        foreach ($_FILES['media_gallery']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['media_gallery']['error'][$key] === UPLOAD_ERR_OK) {
                $file_info = pathinfo($_FILES['media_gallery']['name'][$key]);
                $file_extension = strtolower($file_info['extension']);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($file_extension, $allowed_extensions)) {
                    $new_filename = 'gallery_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        $media_gallery[] = $upload_path;
                    }
                }
            }
        }
    }

    // Check for duplicate slug
    if (!empty($slug)) {
        $slug_check = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
        $slug_check->execute([$slug]);
        if ($slug_check->fetch()) {
            $errors[] = "URL slug already exists. Please choose a different one.";
        }
    }

    // If no errors, save the post
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO posts (title, slug, author, publish_date, featured_image, short_description, content_body, media_gallery, categories, tags, meta_title, meta_description, status, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");


            // Convert PHP arrays to PostgreSQL array literal format
            function to_pg_array($set)
            {
                if (empty($set)) return '{}';
                foreach ($set as &$v) {
                    $v = '"' . str_replace('"', '\"', $v) . '"';
                }
                return '{' . implode(",", $set) . '}';
            }


            $pg_media_gallery = to_pg_array($media_gallery);
            $pg_categories = to_pg_array($selected_categories);
            // Convert comma-separated tags to array
            $tags_array = array_filter(array_map('trim', explode(',', $tags)));
            $pg_tags = to_pg_array($tags_array);

            $stmt->execute([
                $title,
                $slug,
                $author,
                $publish_date,
                $featured_image,
                $short_description,
                $content_body,
                $pg_media_gallery,
                $pg_categories,
                $pg_tags,
                $meta_title ?: $title,
                $meta_description,
                $status,
                $is_featured
            ]);

            $success = "Post created successfully!";

            // Redirect to posts list after success
            header("Location: posts.php?success=" . urlencode($success));
            exit();
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$page_title = "Create New Post";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-plus-circle"></i> Create New Post</h2>
                <a href="posts.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Posts
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <!-- Basic Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title"
                                        value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                        required onkeyup="generateSlugFromTitle()">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="slug" class="form-label">URL Slug</label>
                                    <input type="text" class="form-control" id="slug" name="slug"
                                        value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>"
                                        placeholder="Auto-generated from title">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="author" class="form-label">Author</label>
                                    <input type="text" class="form-control" id="author" name="author"
                                        value="<?php echo htmlspecialchars($_POST['author'] ?? $current_user); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="publish_date" class="form-label">Publish Date/Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="publish_date" name="publish_date"
                                        value="<?php echo $_POST['publish_date'] ?? date('Y-m-d\TH:i'); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-edit"></i> Content</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="featured_image" class="form-label">Featured Image <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="featured_image" name="featured_image"
                                accept="image/*" required>
                            <div class="form-text">Upload a featured image for your post (JPG, PNG, GIF, WebP)</div>

                            <!-- Image Guidance Feedback Area -->
                            <div id="image-guidance" class="mt-3" style="display: none;"></div>
                        </div>

                        <div class="mb-3">
                            <label for="short_description" class="form-label">Short Description/Excerpt <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="short_description" name="short_description"
                                rows="3" maxlength="200" required
                                placeholder="Brief description of your post (max 200 characters)"><?php echo htmlspecialchars($_POST['short_description'] ?? ''); ?></textarea>
                            <div class="form-text">
                                <span id="char_count">0</span>/200 characters
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="content_body" class="form-label">Content Body <span class="text-danger">*</span></label>

                            <!-- Rich Text Editor Toolbar -->
                            <div class="border rounded-top p-2 bg-light" id="editor-toolbar">
                                <div class="btn-group btn-group-sm me-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatText('bold')" title="Bold">
                                        <i class="fas fa-bold"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatText('italic')" title="Italic">
                                        <i class="fas fa-italic"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatText('underline')" title="Underline">
                                        <i class="fas fa-underline"></i>
                                    </button>
                                </div>

                                <div class="btn-group btn-group-sm me-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatHeading('h1')" title="Heading 1">H1</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatHeading('h2')" title="Heading 2">H2</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatHeading('h3')" title="Heading 3">H3</button>
                                </div>

                                <div class="btn-group btn-group-sm me-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatList('insertUnorderedList')" title="Bullet List">
                                        <i class="fas fa-list-ul"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatList('insertOrderedList')" title="Numbered List">
                                        <i class="fas fa-list-ol"></i>
                                    </button>
                                </div>

                                <div class="btn-group btn-group-sm me-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatText('justifyLeft')" title="Align Left">
                                        <i class="fas fa-align-left"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatText('justifyCenter')" title="Align Center">
                                        <i class="fas fa-align-center"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="formatText('justifyRight')" title="Align Right">
                                        <i class="fas fa-align-right"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Rich Text Editor Content Area -->
                            <div class="form-control" id="editor-content"
                                contenteditable="true"
                                style="min-height: 300px; border-top: none; border-top-left-radius: 0; border-top-right-radius: 0;"
                                placeholder="Write your post content here..."><?php echo $_POST['content_body'] ?? ''; ?></div>

                            <!-- Hidden textarea to store the content for form submission -->
                            <textarea class="d-none" id="content_body" name="content_body" required><?php echo htmlspecialchars($_POST['content_body'] ?? ''); ?></textarea>

                            <div class="form-text">Full content of your post with rich text formatting</div>
                        </div>

                        <!-- <div class="mb-3">
                            <label for="media_gallery" class="form-label">Media Gallery (Optional)</label>
                            <input type="file" class="form-control" id="media_gallery" name="media_gallery[]"
                                accept="image/*" multiple>
                            <div class="form-text">Upload additional images for your post gallery</div>
                        </div> -->
                    </div>
                </div>

                <!-- Categorization -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-tags"></i> Keywords</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Categories</label>
                                    <?php //foreach ($categories as $category): 
                                    ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                name="categories[]" value="<?php //echo htmlspecialchars($category['name']); 
                                                                            ?>"
                                                id="cat_<?php //echo $category['id']; 
                                                        ?>"
                                                <?php //echo (isset($_POST['categories']) && in_array($category['name'], $_POST['categories'])) ? 'checked' : ''; 
                                                ?>>
                                            <label class="form-check-label" for="cat_<?php //echo $category['id']; 
                                                                                        ?>">
                                                <?php //echo htmlspecialchars($category['name']); 
                                                ?>
                                            </label>
                                        </div>
                                    <?php //endforeach; 
                                    ?>
                                </div>
                            </div> -->
                            <div class="col-md-12">
                                <div class="mb-6">
                                    <label for="tags" class="form-label">Tags/Keywords</label>
                                    <input type="text" class="form-control" id="tags" name="tags"
                                        value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>"
                                        placeholder="mobile, smartphone, review, tech">
                                    <div class="form-text">Separate tags with commas</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-search"></i> SEO Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="meta_title" class="form-label">Meta Title</label>
                            <input type="text" class="form-control" id="meta_title" name="meta_title"
                                value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>"
                                placeholder="Leave empty to use post title">
                        </div>
                        <div class="mb-3">
                            <label for="meta_description" class="form-label">Meta Description</label>
                            <textarea class="form-control" id="meta_description" name="meta_description"
                                rows="3" placeholder="Brief description for search engines"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cogs"></i> Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="Draft" <?php echo ($_POST['status'] ?? 'Draft') === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="Published" <?php echo ($_POST['status'] ?? '') === 'Published' ? 'selected' : ''; ?>>Published</option>
                                <option value="Archived" <?php echo ($_POST['status'] ?? '') === 'Archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1"
                                    <?php echo isset($_POST['is_featured']) && $_POST['is_featured'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_featured">
                                    <i class="fas fa-star text-warning me-1"></i>
                                    <strong>Featured Post</strong>
                                </label>
                                <div class="form-text">
                                    Featured posts will be highlighted on the Featured Posts page and given priority display.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex justify-content-end gap-2 mb-4">
                    <a href="posts.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Post
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Image Upload Guidance System
    // =============================
    // Required aspect ratio constant: 1.532:1 (width:height)
    const REQUIRED_ASPECT_RATIO = 1.532;
    const RATIO_TOLERANCE = 0.01; // Allow ±0.01 variation
    const OPTIMAL_WIDTH = 1000;
    const OPTIMAL_HEIGHT = 653;

    // Handle featured image file selection
    document.getElementById('featured_image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const guidanceDiv = document.getElementById('image-guidance');

        if (!file) {
            guidanceDiv.style.display = 'none';
            return;
        }

        // Read the image file to get dimensions
        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                const uploadedWidth = img.naturalWidth;
                const uploadedHeight = img.naturalHeight;

                // Calculate the uploaded image's aspect ratio (3 decimal places)
                const uploadedRatio = parseFloat((uploadedWidth / uploadedHeight).toFixed(3));

                // Check if ratio matches required ratio (within tolerance)
                const ratioMin = REQUIRED_ASPECT_RATIO - RATIO_TOLERANCE;
                const ratioMax = REQUIRED_ASPECT_RATIO + RATIO_TOLERANCE;
                const isRatioValid = uploadedRatio >= ratioMin && uploadedRatio <= ratioMax;

                // Generate guidance message
                let guidanceHTML = buildGuidanceMessage(
                    uploadedWidth,
                    uploadedHeight,
                    uploadedRatio,
                    isRatioValid
                );

                guidanceDiv.innerHTML = guidanceHTML;
                guidanceDiv.style.display = 'block';
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });

    /**
     * Build the guidance message based on image dimensions
     * @param {number} width - Uploaded image width in pixels
     * @param {number} height - Uploaded image height in pixels
     * @param {number} ratio - Calculated aspect ratio
     * @param {boolean} isValid - Whether ratio is within tolerance
     * @returns {string} HTML guidance message
     */
    function buildGuidanceMessage(width, height, ratio, isValid) {
        let html = '';

        // Display current image info
        html += '<div class="card border-info">';
        html += '<div class="card-body">';
        html += '<h6 class="card-title mb-2"><i class="fas fa-image"></i> Image Details</h6>';
        html += `<p class="mb-2"><strong>Your image resolution:</strong> ${width} × ${height} px</p>`;
        html += `<p class="mb-3"><strong>Your aspect ratio:</strong> ${ratio}:1</p>`;

        if (isValid) {
            // Success state: ratio matches
            html += '<div class="alert alert-success mb-2" role="alert">';
            html += '<i class="fas fa-check-circle"></i> <strong>Perfect!</strong> Your image has the correct aspect ratio.';
            html += '</div>';
        } else {
            // Guidance state: ratio doesn't match
            // Calculate nearest correct widths and heights
            const correctWidthFromHeight = Math.round(height * REQUIRED_ASPECT_RATIO);
            const correctHeightFromWidth = Math.round(width / REQUIRED_ASPECT_RATIO);

            // Calculate balanced option: average of both adjustments while maintaining correct ratio
            const balancedWidth = Math.round((correctWidthFromHeight + width) / 2);
            const balancedHeight = Math.round((height + correctHeightFromWidth) / 2);

            html += '<div class="alert alert-warning mb-3" role="alert">';
            html += '<i class="fas fa-exclamation-triangle"></i> <strong>Aspect ratio mismatch detected.</strong>';
            html += '<p class="mb-0 mt-2">Your image doesn\'t match the recommended shape, but you can still upload it.</p>';
            html += '</div>';

            html += '<div class="bg-light p-3 rounded mb-3">';
            html += '<p class="mb-2"><strong>Here are your options to match the correct ratio:</strong></p>';
            html += '<ul class="mb-0">';
            html += `<li><strong>Keep your height (${height} px):</strong> Adjust width to <strong>${correctWidthFromHeight} × ${height} px</strong></li>`;
            html += `<li><strong>Keep your width (${width} px):</strong> Adjust height to <strong>${width} × ${correctHeightFromWidth} px</strong></li>`;
            html += `<li><strong>Balanced adjustment:</strong> Resize to <strong>${balancedWidth} × ${balancedHeight} px</strong> (minimizes changes to both dimensions)</li>`;
            html += '</ul>';
            html += '</div>';
        }

        // Optimal recommendation (always shown)
        html += '<div class="bg-light p-3 rounded">';
        html += '<p class="mb-2"><strong><i class="fas fa-lightbulb"></i> Recommended upload size:</strong></p>';
        html += `<p class="mb-1"><strong>${OPTIMAL_WIDTH} × ${OPTIMAL_HEIGHT} px</strong> (best quality)</p>`;
        html += `<p class="text-muted small mb-0">Larger images with the same ratio ( ${REQUIRED_ASPECT_RATIO}:1 ) also work great!</p>`;
        html += '</div>';

        html += '</div>';
        html += '</div>';

        return html;
    }

    // Generate slug from title
    function generateSlugFromTitle() {
        const title = document.getElementById('title').value;
        const slug = title.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s-]+/g, '-')
            .replace(/^-+|-+$/g, '');
        document.getElementById('slug').value = slug;
    }

    // Character counter for short description
    document.getElementById('short_description').addEventListener('input', function() {
        const charCount = this.value.length;
        document.getElementById('char_count').textContent = charCount;

        if (charCount > 200) {
            document.getElementById('char_count').style.color = 'red';
        } else {
            document.getElementById('char_count').style.color = '';
        }
    });

    // Update character count on page load
    document.getElementById('short_description').dispatchEvent(new Event('input'));

    // Rich Text Editor Functions
    function formatText(command) {
        document.execCommand(command, false, null);
        document.getElementById('editor-content').focus();
        updateHiddenTextarea();
    }

    function formatHeading(tag) {
        document.execCommand('formatBlock', false, tag);
        document.getElementById('editor-content').focus();
        updateHiddenTextarea();
    }

    function formatList(command) {
        document.execCommand(command, false, null);
        document.getElementById('editor-content').focus();
        updateHiddenTextarea();
    }

    function cleanHTML(html) {
        // Create a temporary div to parse HTML
        const temp = document.createElement('div');
        temp.innerHTML = html;

        // Remove all style attributes, classes, and IDs
        temp.querySelectorAll('*').forEach(el => {
            el.removeAttribute('style');
            el.removeAttribute('class');
            el.removeAttribute('id');

            // Replace <b> with <strong> and <i> with <em>
            if (el.tagName === 'B') {
                const strong = document.createElement('strong');
                strong.innerHTML = el.innerHTML;
                el.parentNode.replaceChild(strong, el);
            } else if (el.tagName === 'I') {
                const em = document.createElement('em');
                em.innerHTML = el.innerHTML;
                el.parentNode.replaceChild(em, el);
            }
        });

        // Remove divs and spans, keep their content
        temp.querySelectorAll('div, span').forEach(el => {
            const parent = el.parentNode;
            while (el.firstChild) {
                parent.insertBefore(el.firstChild, el);
            }
            parent.removeChild(el);
        });

        return temp.innerHTML;
    }

    function updateHiddenTextarea() {
        let editorContent = document.getElementById('editor-content').innerHTML;
        // Clean the HTML before saving
        editorContent = cleanHTML(editorContent);
        document.getElementById('content_body').value = editorContent;
    }

    // Handle paste events - strip formatting but allow images
    document.getElementById('editor-content').addEventListener('paste', function(e) {
        e.preventDefault();

        // Try to get HTML content first (for images)
        const html = (e.clipboardData || window.clipboardData).getData('text/html');

        if (html) {
            // Parse HTML to extract images and clean content
            const temp = document.createElement('div');
            temp.innerHTML = html;

            // Find all images and clean them
            const images = temp.querySelectorAll('img');
            images.forEach(img => {
                // Keep only src and alt attributes
                const src = img.getAttribute('src');
                const alt = img.getAttribute('alt') || '';

                // Remove all attributes
                while (img.attributes.length > 0) {
                    img.removeAttribute(img.attributes[0].name);
                }

                // Re-add only src and alt (no height, width, style, class, etc.)
                if (src) {
                    img.setAttribute('src', src);
                    img.setAttribute('alt', alt);
                }
            });

            // Get plain text and images combined
            let cleanContent = '';
            temp.childNodes.forEach(node => {
                if (node.nodeName === 'IMG') {
                    cleanContent += node.outerHTML;
                } else {
                    cleanContent += node.textContent || '';
                }
            });

            // Insert the cleaned content
            document.execCommand('insertHTML', false, cleanContent);
        } else {
            // Fallback to plain text if no HTML
            const text = (e.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, text);
        }

        updateHiddenTextarea();
    });

    // Update hidden textarea when content changes
    document.getElementById('editor-content').addEventListener('input', updateHiddenTextarea);
    document.getElementById('editor-content').addEventListener('blur', updateHiddenTextarea);

    // Initialize editor with existing content
    document.addEventListener('DOMContentLoaded', function() {
        const hiddenTextarea = document.getElementById('content_body');
        const editorContent = document.getElementById('editor-content');

        if (hiddenTextarea.value) {
            editorContent.innerHTML = hiddenTextarea.value;
        }

        // Set placeholder behavior
        editorContent.addEventListener('focus', function() {
            if (this.innerHTML === '' || this.innerHTML === '<br>') {
                this.innerHTML = '';
            }
        });

        editorContent.addEventListener('blur', function() {
            if (this.innerHTML === '' || this.innerHTML === '<br>') {
                this.innerHTML = '';
            }
            updateHiddenTextarea();
        });
    });
</script>

<?php include 'includes/footer.php'; ?>