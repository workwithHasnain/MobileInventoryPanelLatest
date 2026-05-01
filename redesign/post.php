<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database_functions.php';
require_once __DIR__ . '/../phone_data.php';

function getAbsoluteImagePath($imagePath, $base)
{
  if (empty($imagePath)) return '';
  if (filter_var($imagePath, FILTER_VALIDATE_URL)) return $imagePath;
  if (strpos($imagePath, '/') === 0) return $imagePath;
  return $base . ltrim($imagePath, '/');
}

$pdo = getConnection();

// Get post by slug
$slug = $_GET['slug'] ?? $_GET['id'] ?? null;
if (!$slug) {
  header('Location: ' . $base . 'index.php');
  exit;
}

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
  include __DIR__ . '/../404.php';
  exit;
}

// Function to get threaded comments
function getPostComments($pdo, $post_id)
{
  $stmt = $pdo->prepare("SELECT * FROM post_comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at ASC");
  $stmt->execute([$post_id]);
  $all = $stmt->fetchAll();

  $parents = [];
  $replies = [];
  foreach ($all as $c) {
    if (empty($c['parent_id'])) {
      $c['replies'] = [];
      $parents[$c['id']] = $c;
    } else {
      $replies[] = $c;
    }
  }
  foreach ($replies as $r) {
    $pid = $r['parent_id'];
    if (isset($parents[$pid])) {
      $parents[$pid]['replies'][] = $r;
    } else {
      foreach ($parents as &$p) {
        foreach ($p['replies'] as $existingReply) {
          if ($existingReply['id'] == $pid) {
            $p['replies'][] = $r;
            break 2;
          }
        }
      }
      unset($p);
    }
  }
  return array_reverse(array_values($parents));
}

function getPostCommentCount($pdo, $post_id)
{
  $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM post_comments WHERE post_id = ? AND status = 'approved'");
  $stmt->execute([$post_id]);
  return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
}

function timeAgo($datetime)
{
  $time = time() - strtotime($datetime);
  if ($time < 60) return 'just now';
  if ($time < 3600) return floor($time / 60) . ' minutes ago';
  if ($time < 86400) return floor($time / 3600) . ' hours ago';
  if ($time < 2592000) return floor($time / 86400) . ' days ago';
  if ($time < 31536000) return floor($time / 2592000) . ' months ago';
  return floor($time / 31536000) . ' years ago';
}

function getAvatarDisplay($name, $email)
{
  if (!empty($email)) {
    $hash = md5(strtolower(trim($email)));
    $gravatar = "https://www.gravatar.com/avatar/{$hash}?r=g&s=50&d=identicon";
    return '<img src="' . $gravatar . '" alt="' . htmlspecialchars($name) . '">';
  } else {
    $initial = strtoupper(substr($name, 0, 1));
    return '<span>' . $initial . '</span>';
  }
}

// Track view
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
try {
  $view_stmt = $pdo->prepare("INSERT INTO content_views (content_type, content_id, ip_address, user_agent) VALUES ('post', CAST(? AS VARCHAR), ?, ?) ON CONFLICT (content_type, content_id, ip_address) DO NOTHING");
  $view_stmt->execute([$post['id'], $user_ip, $user_agent]);
  $update_view_stmt = $pdo->prepare("UPDATE posts SET view_count = (SELECT COUNT(*) FROM content_views WHERE content_type = 'post' AND content_id = CAST(? AS VARCHAR)) WHERE id = ?");
  $update_view_stmt->execute([$post['id'], $post['id']]);
} catch (Exception $e) {}

$postComments = getPostComments($pdo, $post['id']);
$postCommentCount = getPostCommentCount($pdo, $post['id']);

// SEO Setup
$page_title = htmlspecialchars($post['meta_title'] ?? $post['title']) . ' - DevicesArena';
$meta_description = htmlspecialchars($post['meta_description'] ?? $post['short_description'] ?? substr(strip_tags($post['content_body']), 0, 160) . '...');
$featured_img = !empty($post['featured_image']) ? getAbsoluteImagePath($post['featured_image'], $base) : $base . 'imges/icon-256.png';

// Tags parsing
$tags = $post['tags'] ?? '';
if (is_string($tags)) {
    $tagsString = trim($tags);
    if (strlen($tagsString) > 1 && $tagsString[0] === '[' && substr($tagsString, -1) === ']') {
        $tagsString = trim($tagsString, '[]');
        $tags = array_map('trim', explode(',', $tagsString));
    } elseif (strlen($tagsString) > 1 && $tagsString[0] === '{' && substr($tagsString, -1) === '}') {
        $tagsString = trim($tagsString, '{}');
        $tags = array_map(function ($tag) { return trim($tag, '"'); }, explode(',', $tagsString));
    } else {
        $tags = array_map('trim', explode(',', $tagsString));
    }
}
$tags = array_filter((array)$tags);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <link rel="canonical" href="<?php echo $base; ?>post/<?php echo htmlspecialchars($post['slug']); ?>" />
  <title><?php echo $page_title; ?></title>
  <meta name="description" content="<?php echo $meta_description; ?>" />
  <meta property="og:title" content="<?php echo $page_title; ?>" />
  <meta property="og:description" content="<?php echo $meta_description; ?>" />
  <meta property="og:image" content="<?php echo $featured_img; ?>" />
  <meta property="og:type" content="article" />
  <meta name="twitter:card" content="summary_large_image" />
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>imges/icon-32.png">
  <link rel="shortcut icon" href="<?php echo $base; ?>imges/icon-32.png">
  <meta name="theme-color" content="#0d0f1a">

  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9906394285054446" crossorigin="anonymous"></script>
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-2LDCSSMXJT"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', 'G-2LDCSSMXJT');
  </script>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?php echo $base; ?>redesign/style.css">

  <script>
    (function () {
      var savedTheme = localStorage.getItem('da-theme');
      if (savedTheme === 'light' || (!savedTheme && window.matchMedia('(prefers-color-scheme: light)').matches)) {
        document.documentElement.setAttribute('data-theme', 'light');
      }
    })();
  </script>

  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BlogPosting",
      "headline": "<?php echo addslashes($post['title']); ?>",
      "image": "<?php echo $featured_img; ?>",
      "datePublished": "<?php echo $post['publish_date'] ?? $post['created_at']; ?>",
      "author": {
        "@type": "Organization",
        "name": "DevicesArena"
      }
    }
  </script>
</head>

<body>
  <?php include('includes/navbar.php'); ?>
  <?php include('includes/login-modal.php'); ?>
  <?php include('includes/signup-modal.php'); ?>
  <?php include('includes/profile-modal.php'); ?>

  <div class="da-page">
    <div class="da-content-area">
      <main>
        <article class="da-article-wrap">
          <!-- Post Cover -->
          <div class="da-post-cover">
            <?php if (!empty($post['featured_image'])): ?>
              <img src="<?php echo htmlspecialchars(getAbsoluteImagePath($post['featured_image'], $base)); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
            <?php else: ?>
              <img src="<?php echo $base; ?>imges/icon-256.png" alt="DevicesArena">
            <?php endif; ?>
            <div class="da-post-cover-overlay"></div>
          </div>

          <!-- Meta Bar -->
          <div class="da-post-meta-bar">
            <div class="da-post-author-chip">
              <i class="fa fa-user-pen"></i>
              <span><?php echo htmlspecialchars($post['author'] ?? 'DevicesArena Team'); ?></span>
            </div>
            <div class="da-post-date">
              <i class="fa fa-calendar-day"></i>
              <span><?php echo date('j F Y', strtotime($post['publish_date'] ?? $post['created_at'])); ?></span>
            </div>
            <div class="da-post-views">
              <i class="fa fa-eye"></i>
              <span><?php echo number_format($post['view_count'] ?? 0); ?> views</span>
            </div>
          </div>

          <!-- Tags -->
          <?php if (!empty($tags)): ?>
            <div class="da-post-tags">
              <?php foreach ($tags as $tag): ?>
                <span class="da-post-tag"><?php echo htmlspecialchars($tag); ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <!-- Heading Nav -->
          <div class="da-heading-nav">
            <button id="headingPrev" class="da-heading-nav-btn" disabled><i class="fa fa-chevron-left"></i></button>
            <select id="headingDropdown" class="da-heading-dropdown">
              <option value="" disabled selected>Jump to section...</option>
            </select>
            <button id="headingNext" class="da-heading-nav-btn" disabled><i class="fa fa-chevron-right"></i></button>
          </div>

          <!-- Article Content -->
          <div class="da-article-body">
            <h1 class="da-article-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            <div class="da-article-content">
              <?php
              $content = $post['content_body'];
              if (strip_tags($content) != $content) {
                echo $content;
              } else {
                echo nl2br(htmlspecialchars($content));
              }
              ?>
            </div>
          </div>
        </article>

        <!-- Comments Section -->
        <section class="da-post-comments-section" id="comments">
          <div class="da-post-comments-header">
            <h3 class="da-post-comments-title"><i class="fa fa-comments"></i> User Opinions</h3>
            <span class="da-post-comments-count"><?php echo $postCommentCount; ?> Comments</span>
          </div>

          <div class="da-post-comments-list">
            <?php if (!empty($postComments)): ?>
              <?php foreach ($postComments as $comment): ?>
                <div class="da-post-comment-thread" id="comment-<?php echo $comment['id']; ?>">
                  <div class="da-post-comment-avatar">
                    <?php echo getAvatarDisplay($comment['name'], $comment['email']); ?>
                  </div>
                  <div class="da-post-comment-body">
                    <div class="da-post-comment-meta">
                      <span class="da-post-comment-name"><?php echo htmlspecialchars($comment['name']); ?></span>
                      <span class="da-post-comment-time"><i class="fa fa-clock"></i> <?php echo timeAgo($comment['created_at']); ?></span>
                    </div>
                    <div class="da-post-comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></div>
                    <button class="da-post-reply-btn" data-comment-id="<?php echo $comment['id']; ?>" data-comment-name="<?php echo htmlspecialchars($comment['name']); ?>">
                      <i class="fa fa-reply"></i> Reply
                    </button>

                    <?php if (!empty($comment['replies'])): ?>
                      <div class="da-post-comment-replies">
                        <?php foreach ($comment['replies'] as $reply): ?>
                          <div class="da-post-comment-thread" id="comment-<?php echo $reply['id']; ?>">
                            <div class="da-post-comment-avatar">
                              <?php echo getAvatarDisplay($reply['name'], $reply['email']); ?>
                            </div>
                            <div class="da-post-comment-body">
                              <div class="da-post-comment-meta">
                                <span class="da-post-comment-name"><?php echo htmlspecialchars($reply['name']); ?></span>
                                <span class="da-post-replied-tag"><i class="fa fa-reply"></i> replied</span>
                                <span class="da-post-comment-time"><i class="fa fa-clock"></i> <?php echo timeAgo($reply['created_at']); ?></span>
                              </div>
                              <div class="da-post-comment-text"><?php echo nl2br(htmlspecialchars($reply['comment'])); ?></div>
                              <button class="da-post-reply-btn" data-comment-id="<?php echo $comment['id']; ?>" data-comment-name="<?php echo htmlspecialchars($comment['name']); ?>">
                                <i class="fa fa-reply"></i> Reply
                              </button>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="da-post-no-comments">
                <i class="fa fa-comment-dots"></i>
                No comments yet. Be the first to share your opinion!
              </div>
            <?php endif; ?>
          </div>

          <!-- Comment Form -->
          <div class="da-post-comment-form-wrap">
            <h4 class="da-post-form-title">Leave a Comment</h4>
            
            <div id="reply-indicator" class="da-reply-indicator d-none">
              <span><i class="fa fa-reply"></i> Replying to <strong id="reply-to-name"></strong></span>
              <button id="cancel-reply" class="da-reply-cancel-btn"><i class="fa fa-times"></i> Cancel</button>
            </div>

            <?php
            $loggedInName = $_SESSION['public_user_name'] ?? ($_SESSION['username'] ?? '');
            $loggedInEmail = $_SESSION['public_user_email'] ?? '';
            $isUserLoggedIn = !empty($loggedInName);
            ?>
            <form id="post-comment-form" method="POST" class="da-form">
              <input type="hidden" name="action" value="comment_post">
              <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
              <input type="hidden" name="parent_id" id="parent_id" value="">

              <div class="da-form-row">
                <div class="da-form-group">
                  <input type="text" class="da-input" name="name" placeholder="Your Name" required <?php if ($isUserLoggedIn): ?>value="<?php echo htmlspecialchars($loggedInName); ?>" disabled<?php endif; ?>>
                  <?php if ($isUserLoggedIn): ?><input type="hidden" name="name" value="<?php echo htmlspecialchars($loggedInName); ?>"><?php endif; ?>
                </div>
                <div class="da-form-group">
                  <input type="email" class="da-input" name="email" placeholder="Your Email" required <?php if ($isUserLoggedIn && $loggedInEmail): ?>value="<?php echo htmlspecialchars($loggedInEmail); ?>" disabled<?php endif; ?>>
                  <?php if ($isUserLoggedIn && $loggedInEmail): ?><input type="hidden" name="email" value="<?php echo htmlspecialchars($loggedInEmail); ?>"><?php endif; ?>
                </div>
              </div>

              <div class="da-form-group">
                <textarea class="da-input" name="comment" rows="5" placeholder="Share your thoughts about this article..." required></textarea>
              </div>

              <div class="da-post-captcha-row">
                <img src="<?php echo $base; ?>captcha.php" id="captcha-image" class="da-post-captcha-img" alt="CAPTCHA" onclick="refreshCaptcha()">
                <button type="button" class="da-captcha-refresh-btn" onclick="refreshCaptcha()"><i class="fa fa-rotate"></i></button>
                <input type="text" class="da-input da-post-captcha-input" name="captcha" placeholder="Enter words..." required autocomplete="off">
              </div>

              <div class="da-post-form-footer">
                <button type="submit" class="da-cta-btn">Post Comment</button>
                <small>Moderated content. Approval required.</small>
              </div>
            </form>
          </div>
          <div class="da-post-comment-count-text">
            Total reader comments: <strong><?php echo $postCommentCount; ?></strong>
          </div>
        </section>
      </main>

      <aside class="da-sidebar">
        <?php include('includes/sidebar/latest-devices.php'); ?>
        <?php include('includes/sidebar/popular-comparisons.php'); ?>
        <?php include('includes/sidebar/top-daily-interests.php'); ?>
      </aside>
    </div>

    <?php include('includes/bottom-area/featured-posts.php'); ?>
    <?php include('includes/bottom-area/brand-marquee.php'); ?>
  </div>

  <?php include('includes/footer.php'); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    window.baseURL = '<?php echo $base; ?>';
    
    // Heading Nav Logic
    document.addEventListener('DOMContentLoaded', function() {
      const container = document.querySelector('.da-article-content');
      const dropdown = document.getElementById('headingDropdown');
      const prevBtn = document.getElementById('headingPrev');
      const nextBtn = document.getElementById('headingNext');
      if (!container || !dropdown) return;

      const headings = container.querySelectorAll('h1, h2, h3');
      const headingEls = Array.from(headings);
      let activeIdx = -1;

      headings.forEach((h, idx) => {
        const id = h.id || 'section-' + idx;
        h.id = id;
        const opt = document.createElement('option');
        opt.value = idx;
        opt.textContent = h.textContent.trim();
        dropdown.appendChild(opt);
      });

      if (headingEls.length > 0) {
        nextBtn.disabled = false;
      }

      function scrollToHeading(idx) {
        if (idx < 0 || idx >= headingEls.length) return;
        activeIdx = idx;
        dropdown.value = idx;
        prevBtn.disabled = activeIdx <= 0;
        nextBtn.disabled = activeIdx >= headingEls.length - 1;
        
        const offset = document.getElementById('da-navbar').offsetHeight + 20;
        const target = headingEls[idx].getBoundingClientRect().top + window.pageYOffset - offset;
        window.scrollTo({ top: target, behavior: 'smooth' });
      }

      dropdown.addEventListener('change', (e) => scrollToHeading(parseInt(e.target.value)));
      prevBtn.addEventListener('click', () => scrollToHeading(activeIdx - 1));
      nextBtn.addEventListener('click', () => scrollToHeading(activeIdx + 1));
    });

    // CAPTCHA Refresh
    function refreshCaptcha() {
      document.getElementById('captcha-image').src = window.baseURL + 'captcha.php?' + Date.now();
    }

    // Reply Logic
    document.addEventListener('click', function(e) {
      if (e.target.closest('.da-post-reply-btn')) {
        const btn = e.target.closest('.da-post-reply-btn');
        const id = btn.getAttribute('data-comment-id');
        const name = btn.getAttribute('data-comment-name');
        
        document.getElementById('parent_id').value = id;
        document.getElementById('reply-to-name').textContent = name;
        document.getElementById('reply-indicator').classList.remove('d-none');
        document.querySelector('#post-comment-form textarea').focus();
        document.getElementById('comments').scrollIntoView({ behavior: 'smooth' });
      }
    });

    document.getElementById('cancel-reply').addEventListener('click', function() {
      document.getElementById('parent_id').value = '';
      document.getElementById('reply-indicator').classList.add('d-none');
    });

    // Form Submit
    document.getElementById('post-comment-form').addEventListener('submit', function(e) {
      e.preventDefault();
      const form = this;
      const btn = form.querySelector('button[type="submit"]');
      const originalText = btn.textContent;
      
      btn.disabled = true;
      btn.textContent = 'Posting...';
      
      const formData = new FormData(form);
      fetch(window.baseURL + 'ajax_comment_handler.php', {
        method: 'POST',
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        alert(data.message);
        if (data.success) {
          form.reset();
          document.getElementById('cancel-reply').click();
          refreshCaptcha();
        }
        btn.disabled = false;
        btn.textContent = originalText;
      })
      .catch(() => {
        alert('An error occurred. Please try again.');
        btn.disabled = false;
        btn.textContent = originalText;
      });
    });
  </script>
</body>
</html>