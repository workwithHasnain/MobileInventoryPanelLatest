<?php
require_once 'database_functions.php';
require_once 'includes/database_functions.php';
$pdo = getConnection();

$device_id = $_GET['id'] ?? '';

if (!$device_id) {
    echo '<div class="alert alert-danger">Invalid device ID.</div>';
    exit;
}

// Get device details from database
$device = getPhoneByIdDB($device_id);

if (!$device) {
    echo '<div class="alert alert-danger">Device not found.</div>';
    exit;
}

// Track view for this device (one per IP per day)
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    $view_stmt = $pdo->prepare("INSERT INTO content_views (content_type, content_id, ip_address, user_agent) VALUES ('device', ?, ?, ?) ON CONFLICT (content_type, content_id, ip_address, DATE(viewed_at)) DO NOTHING");
    $view_stmt->execute([$device_id, $user_ip, $user_agent]);
} catch (Exception $e) {
    // Silently ignore view tracking errors
}

// Get comments for this device (parent comments and their replies)
$comments_stmt = $pdo->prepare("
    WITH RECURSIVE comment_tree AS (
        -- Get parent comments (no parent_id)
        SELECT id, device_id, name, email, comment, status, created_at, parent_id, 0 as depth
        FROM device_comments 
        WHERE device_id = ? AND status = 'approved' AND parent_id IS NULL
        
        UNION ALL
        
        -- Get replies recursively
        SELECT dc.id, dc.device_id, dc.name, dc.email, dc.comment, dc.status, dc.created_at, dc.parent_id, ct.depth + 1
        FROM device_comments dc
        INNER JOIN comment_tree ct ON dc.parent_id = ct.id
        WHERE dc.status = 'approved'
    )
    SELECT * FROM comment_tree ORDER BY created_at ASC
");
$comments_stmt->execute([$device_id]);
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
?>

<div class="device-details">
    <!-- Device Header -->
    <div class="mb-4">
        <?php if (isset($device['images']) && !empty($device['images'])): ?>
            <div class="row mb-3">
                <?php foreach ($device['images'] as $index => $image): ?>
                    <div class="col-md-<?php echo count($device['images']) > 2 ? '4' : '6'; ?> mb-3">
                        <img src="<?php echo htmlspecialchars($image); ?>"
                            class="img-fluid rounded" alt="Device Image" style="max-height: 200px; width: 100%; object-fit: cover;">
                    </div>
                    <?php if ($index >= 2) break; // Limit to 3 images 
                    ?>
                <?php endforeach; ?>
            </div>
        <?php elseif (isset($device['image']) && !empty($device['image'])): ?>
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <img src="<?php echo htmlspecialchars($device['image']); ?>"
                        class="img-fluid rounded" alt="Device Image" style="max-height: 200px; width: 100%; object-fit: cover;">
                </div>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <span class="badge bg-success me-2"><?php echo htmlspecialchars($device['brand'] ?? 'Unknown'); ?></span>
            <?php if (isset($device['availability'])): ?>
                <span class="badge bg-<?php echo $device['availability'] === 'Available' ? 'success' : 'secondary'; ?>">
                    <?php echo htmlspecialchars($device['availability']); ?>
                </span>
            <?php endif; ?>
            <?php if (isset($device['announce_date'])): ?>
                <small class="text-muted ms-3">
                    <i class="fas fa-calendar-alt me-1"></i>
                    Announced: <?php echo htmlspecialchars($device['announce_date']); ?>
                </small>
            <?php endif; ?>
        </div>

        <h3><?php echo htmlspecialchars($device['name'] ?? 'Unknown Device'); ?></h3>
        <?php if (isset($device['price'])): ?>
            <p class="lead text-primary fw-bold"><?php echo htmlspecialchars($device['price']); ?></p>
        <?php endif; ?>
    </div>

    <!-- Device Specifications -->
    <div class="mb-4">
        <h5><i class="fas fa-list me-2"></i>Key Specifications</h5>
        <div class="row">
            <div class="col-md-6">
                <ul class="list-unstyled">
                    <?php if (isset($device['display_size'])): ?>
                        <li class="mb-2">
                            <strong><i class="fas fa-tv text-primary me-2"></i>Display:</strong>
                            <?php echo htmlspecialchars($device['display_size']); ?>
                            <?php if (isset($device['display_resolution'])): ?>
                                <br><small class="text-muted ms-4"><?php echo htmlspecialchars($device['display_resolution']); ?></small>
                            <?php endif; ?>
                        </li>
                    <?php endif; ?>

                    <?php if (isset($device['chipset'])): ?>
                        <li class="mb-2">
                            <strong><i class="fas fa-microchip text-warning me-2"></i>Chipset:</strong>
                            <?php echo htmlspecialchars($device['chipset']); ?>
                        </li>
                    <?php endif; ?>

                    <?php if (isset($device['internal_memory'])): ?>
                        <li class="mb-2">
                            <strong><i class="fas fa-memory text-info me-2"></i>Storage:</strong>
                            <?php echo htmlspecialchars($device['internal_memory']); ?>
                        </li>
                    <?php endif; ?>

                    <?php if (isset($device['ram'])): ?>
                        <li class="mb-2">
                            <strong><i class="fas fa-memory text-info me-2"></i>RAM:</strong>
                            <?php echo htmlspecialchars($device['ram']); ?>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="col-md-6">
                <ul class="list-unstyled">
                    <?php if (isset($device['main_camera'])): ?>
                        <li class="mb-2">
                            <strong><i class="fas fa-camera text-success me-2"></i>Main Camera:</strong>
                            <?php echo htmlspecialchars($device['main_camera']); ?>
                        </li>
                    <?php endif; ?>

                    <?php if (isset($device['selfie_camera'])): ?>
                        <li class="mb-2">
                            <strong><i class="fas fa-camera-retro text-success me-2"></i>Selfie Camera:</strong>
                            <?php echo htmlspecialchars($device['selfie_camera']); ?>
                        </li>
                    <?php endif; ?>

                    <?php if (isset($device['battery'])): ?>
                        <li class="mb-2">
                            <strong><i class="fas fa-battery-full text-danger me-2"></i>Battery:</strong>
                            <?php echo htmlspecialchars($device['battery']); ?>
                        </li>
                    <?php endif; ?>

                    <?php if (isset($device['os'])): ?>
                        <li class="mb-2">
                            <strong><i class="fas fa-cogs text-secondary me-2"></i>OS:</strong>
                            <?php echo htmlspecialchars($device['os']); ?>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Comments Section -->
    <div class="comment-section">
        <h5><i class="fas fa-comments me-2"></i>User Comments (<?php echo count($all_comments); ?>)</h5>

        <!-- Add Comment Form -->
        <div class="card mb-4" id="main-comment-form">
            <div class="card-header">
                <h6 class="mb-0">Share Your Experience</h6>
            </div>
            <div class="card-body">
                <form method="post" action="index.php#devices" id="device-comment-form">
                    <input type="hidden" name="action" value="comment_device">
                    <input type="hidden" name="device_id" value="<?php echo htmlspecialchars($device_id); ?>">
                    <input type="hidden" name="parent_id" value="">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="device_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="device_name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="device_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="device_email" name="email" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="device_comment" class="form-label">Comment <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="device_comment" name="comment" rows="4"
                            placeholder="Share your experience with this device..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane me-2"></i>Submit Comment
                    </button>
                    <button type="button" class="btn btn-secondary ms-2 cancel-reply" style="display: none;">
                        <i class="fas fa-times me-2"></i>Cancel Reply
                    </button>
                </form>
            </div>
        </div>

        <!-- Display Comments -->
        <?php if (empty($comments)): ?>
            <div class="text-center py-4">
                <i class="fas fa-comments fa-2x text-muted mb-3"></i>
                <p class="text-muted">No comments yet. Be the first to share your experience!</p>
            </div>
        <?php else: ?>
            <div class="comments-list">
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item card mb-3" data-comment-id="<?php echo $comment['id']; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($comment['name']); ?></strong>
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
                            <p class="mb-2"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>

                            <!-- Replies -->
                            <?php if (isset($comment['replies']) && !empty($comment['replies'])): ?>
                                <div class="replies ms-4 mt-3">
                                    <?php foreach ($comment['replies'] as $reply): ?>
                                        <div class="reply-item card mb-2" data-comment-id="<?php echo $reply['id']; ?>">
                                            <div class="card-body py-2">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($reply['name']); ?></strong>
                                                        <small class="text-muted ms-2">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <?php echo date('M j, Y \a\t g:i A', strtotime($reply['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-primary reply-btn"
                                                        data-comment-id="<?php echo $reply['id']; ?>"
                                                        data-comment-author="<?php echo htmlspecialchars($reply['name']); ?>">
                                                        <i class="fas fa-reply me-1"></i>Reply
                                                    </button>
                                                </div>
                                                <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($reply['comment'])); ?></p>

                                                <!-- Nested replies -->
                                                <?php if (isset($reply['replies']) && !empty($reply['replies'])): ?>
                                                    <div class="nested-replies ms-3 mt-2">
                                                        <?php foreach ($reply['replies'] as $nested_reply): ?>
                                                            <div class="nested-reply-item card mb-1" data-comment-id="<?php echo $nested_reply['id']; ?>">
                                                                <div class="card-body py-1">
                                                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                                                        <div>
                                                                            <strong class="small"><?php echo htmlspecialchars($nested_reply['name']); ?></strong>
                                                                            <small class="text-muted ms-1">
                                                                                <i class="fas fa-clock me-1"></i>
                                                                                <?php echo date('M j, Y \a\t g:i A', strtotime($nested_reply['created_at'])); ?>
                                                                            </small>
                                                                        </div>
                                                                        <button class="btn btn-sm btn-outline-primary reply-btn"
                                                                            data-comment-id="<?php echo $nested_reply['id']; ?>"
                                                                            data-comment-author="<?php echo htmlspecialchars($nested_reply['name']); ?>">
                                                                            <i class="fas fa-reply me-1"></i>Reply
                                                                        </button>
                                                                    </div>
                                                                    <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($nested_reply['comment'])); ?></p>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- JavaScript for reply functionality -->
    <script>
        // Initialize reply functionality for device comments
        function initializeDeviceReplyFunctionality() {
            const replyButtons = document.querySelectorAll('.reply-btn');
            const commentForm = document.getElementById('device-comment-form');

            if (!commentForm) return; // Exit if form not found

            const parentIdField = commentForm.querySelector('input[name="parent_id"]');
            const cancelReplyBtn = commentForm.querySelector('.cancel-reply');
            const commentTextarea = commentForm.querySelector('#device_comment');

            // Remove existing event listeners to avoid duplicates
            replyButtons.forEach(button => {
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);
            });

            // Add event listeners to reply buttons
            document.querySelectorAll('.reply-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const commentId = this.getAttribute('data-comment-id');
                    const commentAuthor = this.getAttribute('data-comment-author');
                    const commentItem = this.closest('.comment-item, .reply-item, .nested-reply-item');

                    // Set parent ID
                    parentIdField.value = commentId;

                    // Update placeholder
                    commentTextarea.placeholder = `Replying to ${commentAuthor}...`;

                    // Move form after the comment being replied to
                    commentItem.after(commentForm.closest('.card'));

                    // Show cancel reply button
                    cancelReplyBtn.style.display = 'inline-block';

                    // Focus on comment textarea
                    commentTextarea.focus();
                });
            });

            // Cancel reply functionality
            if (cancelReplyBtn) {
                const newCancelBtn = cancelReplyBtn.cloneNode(true);
                cancelReplyBtn.parentNode.replaceChild(newCancelBtn, cancelReplyBtn);

                newCancelBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Reset form
                    parentIdField.value = '';
                    commentTextarea.placeholder = 'Share your experience with this device...';

                    // Move form back to original position
                    const mainCommentForm = document.getElementById('main-comment-form');
                    if (mainCommentForm) {
                        mainCommentForm.appendChild(commentForm.closest('.card'));
                    }

                    // Hide cancel reply button
                    this.style.display = 'none';
                });
            }
        }

        // Initialize immediately since this script is loaded within the modal
        initializeDeviceReplyFunctionality();
    </script>
</div>