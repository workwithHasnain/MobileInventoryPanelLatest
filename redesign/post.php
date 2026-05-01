<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database_functions.php';
require_once __DIR__ . '/../phone_data.php';

function getAbsoluteImagePath($imagePath, $base)
{
  if (empty($imagePath))
    return '';
  if (filter_var($imagePath, FILTER_VALIDATE_URL))
    return $imagePath;
  if (strpos($imagePath, '/') === 0)
    return $imagePath;
  return $base . ltrim($imagePath, '/');
}

$pdo = getConnection();

// Auth
$isPublicUser = !empty($_SESSION['public_user_id']);
$publicUserName = $_SESSION['public_user_name'] ?? '';
$publicUserInitial = $isPublicUser ? strtoupper(substr($publicUserName, 0, 1)) : '';

if (!isset($_SESSION['notif_seen']))
  $_SESSION['notif_seen'] = false;
$hasUnreadNotifications = $isPublicUser && !$_SESSION['notif_seen'];

// Weekly posts for notifications
try {
  $weekly_stmt = $pdo->prepare("SELECT p.id,p.title,p.slug,p.featured_image,p.created_at FROM posts p WHERE p.status ILIKE 'published' AND p.created_at >= CURRENT_TIMESTAMP - INTERVAL '7 days' ORDER BY p.created_at DESC LIMIT 10");
  $weekly_stmt->execute();
  $weekly_posts = $weekly_stmt->fetchAll();
} catch (Exception $e) {
  $weekly_posts = [];
}

// Get post by slug or ID
$slug = $_GET['slug'] ?? $_GET['id'] ?? null;

if (!$slug) {
    header('Location: ' . $base);
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
    include __DIR__ . '/../404.php';
    exit;
}

// Get post comments and comment count
$postComments = getPostComments($post['id']);
$postCommentCount = getPostCommentCount($post['id']);

// Track view for this post
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    $view_stmt = $pdo->prepare("INSERT INTO content_views (content_type, content_id, ip_address, user_agent) VALUES ('post', CAST(? AS VARCHAR), ?, ?) ON CONFLICT (content_type, content_id, ip_address) DO NOTHING");
    $view_stmt->execute([$post['id'], $user_ip, $user_agent]);

    $update_view_stmt = $pdo->prepare("UPDATE posts SET view_count = (SELECT COUNT(*) FROM content_views WHERE content_type = 'post' AND content_id = CAST(? AS VARCHAR)) WHERE id = ?");
    $update_view_stmt->execute([$post['id'], $post['id']]);
} catch (Exception $e) {
    // Silently ignore view tracking errors
}

// Get comments for posts (threaded: parents first, then replies grouped)
function getPostComments($post_id)
{
    global $pdo;
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

function getPostCommentCount($post_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM post_comments WHERE post_id = ? AND status = 'approved'");
    $stmt->execute([$post_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
}

function getGravatarUrl($email, $size = 50)
{
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/{$hash}?r=g&s={$size}&d=identicon";
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
        return '<img src="' . getGravatarUrl($email) . '" alt="' . htmlspecialchars($name) . '">';
    } else {
        $initials = strtoupper(substr($name, 0, 1));
        $colors = ['#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6f42c1', '#e83e8c'];
        $color = $colors[abs(crc32($name)) % count($colors)];
        return '<span class="da-avatar-initial" style="background-color: ' . $color . '; color: white;">' . $initials . '</span>';
    }
}

// Comparisons
try {
  $topComparisons = getPopularComparisons(10);
} catch (Exception $e) {
  $topComparisons = [];
}

// Latest devices
$latestDevices = getAllPhones();
$latestDevices = array_slice(array_reverse($latestDevices), 0, 15);

// Brands
$brands_stmt = $pdo->prepare("SELECT b.*,COUNT(p.id) as device_count FROM brands b LEFT JOIN phones p ON b.id=p.brand_id GROUP BY b.id,b.name,b.description,b.logo_url,b.website,b.created_at,b.updated_at ORDER BY COUNT(p.id) DESC,b.name ASC LIMIT 36");
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <link rel="canonical" href="<?php echo $canonicalBase; ?>/post/<?php echo htmlspecialchars($slug); ?>" />
  <title><?php echo htmlspecialchars($post['meta_title'] ?? $post['title']); ?> — DevicesArena</title>
  <meta name="description"
    content="<?php echo htmlspecialchars($post['meta_description'] ?? $post['short_description'] ?? substr(strip_tags($post['content_body']), 0, 160) . '...'); ?>" />
  <meta property="og:title" content="<?php echo htmlspecialchars($post['meta_title'] ?? $post['title']); ?> — DevicesArena" />
  <meta property="og:description"
    content="<?php echo htmlspecialchars($post['meta_description'] ?? $post['short_description'] ?? substr(strip_tags($post['content_body']), 0, 160) . '...'); ?>" />
  <meta property="og:image" content="<?php echo getAbsoluteImagePath($post['featured_image'], $base); ?>" />
  <meta property="og:type" content="article" />
  <meta name="twitter:card" content="summary_large_image" />
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>imges/icon-32.png">
  <link rel="shortcut icon" href="<?php echo $base; ?>imges/icon-32.png">
  <meta name="theme-color" content="#0d0f1a">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9906394285054446"
    crossorigin="anonymous"></script>
  <!-- Google Analytics -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-2LDCSSMXJT"></script>
  <script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
      dataLayer.push(arguments);
    }
    gtag('js', new Date());
    gtag('config', 'G-2LDCSSMXJT');
  </script>

  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?php echo $base; ?>redesign/style.css">

  <?php
  // Build breadcrumb schema
  $breadcrumbItems = [
    [
      "@type" => "ListItem",
      "position" => 1,
      "name" => "Home",
      "item" => "https://www.devicesarena.com/"
    ],
    [
      "@type" => "ListItem",
      "position" => 2,
      "name" => "News & Reviews",
      "item" => "https://www.devicesarena.com/"
    ],
    [
      "@type" => "ListItem",
      "position" => 3,
      "name" => $post['title'],
      "item" => "https://www.devicesarena.com/post/" . htmlspecialchars($slug)
    ]
  ];
  ?>

  <!-- Breadcrumb Schema -->
  <script type="application/ld+json">
      {
          "@context": "https://schema.org",
          "@type": "BreadcrumbList",
          "itemListElement": <?php echo json_encode($breadcrumbItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
      }
  </script>

  <!-- Article Schema -->
  <script type="application/ld+json">
      {
          "@context": "https://schema.org",
          "@type": "Article",
          "headline": <?php echo json_encode($post['title'], JSON_UNESCAPED_UNICODE); ?>,
          "image": [
            <?php echo json_encode(getAbsoluteImagePath($post['featured_image'], $base), JSON_UNESCAPED_SLASHES); ?>
           ],
          "datePublished": "<?php echo date('c', strtotime($post['publish_date'] ?? $post['created_at'])); ?>",
          "dateModified": "<?php echo date('c', strtotime($post['updated_at'] ?? $post['created_at'])); ?>",
          "author": [{
              "@type": "Person",
              "name": <?php echo json_encode($post['author'] ?? 'DevicesArena', JSON_UNESCAPED_UNICODE); ?>
          }]
      }
  </script>

  <!-- Theme Initialization Script (Prevents FOUC) -->
  <script>
    (function () {
      var savedTheme = localStorage.getItem('da-theme');
      if (savedTheme === 'light' || (!savedTheme && window.matchMedia('(prefers-color-scheme: light)').matches)) {
        document.documentElement.setAttribute('data-theme', 'light');
      }
    })();
  </script>

  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4554952734894265"
    crossorigin="anonymous"></script>
</head>

<body>

  <!-- ══════════════════════ NAVBAR ══════════════════════ -->
  <?php include('includes/navbar.php'); ?>

  <!-- ══════════════════════ AUTH MODALS ══════════════════════ -->
  <!-- Login -->
  <?php include('includes/login-modal.php'); ?>
  <!-- Sign Up -->
  <?php include('includes/signup-modal.php'); ?>

  <!-- Profile -->
  <?php include('includes/profile-modal.php'); ?>

  <!-- ══════════════════════ MAIN PAGE ══════════════════════ -->
  <div class="da-page">

  <!-- ══════════════════════ MAIN PAGE ══════════════════════ -->
  <div class="da-page">

    <div class="da-content-area da-article-layout">
      <!-- Article Content (Main) -->
      <main class="da-article-main">
        <article class="da-article">
          <header class="da-article-header">
            <div class="da-article-meta-top">
              <div class="da-article-jump">
                <button id="headingPrev" class="da-jump-btn" title="Previous section" aria-label="Previous section" style="display:none;"><i class="fa fa-chevron-left"></i></button>
                <select id="headingDropdown" class="da-jump-select" aria-label="Jump to section" style="display:none;"></select>
                <button id="headingNext" class="da-jump-btn" title="Next section" aria-label="Next section" style="display:none;"><i class="fa fa-chevron-right"></i></button>
              </div>
              <div class="da-article-date">
                <i class="fa-regular fa-calendar-days"></i> <?php echo !empty($post['publish_date']) ? date('F j, Y', strtotime($post['publish_date'])) : date('F j, Y', strtotime($post['created_at'])); ?>
              </div>
            </div>

            <h1 class="da-article-title"><?php echo htmlspecialchars($post['title']); ?></h1>

            <div class="da-article-meta-bottom">
              <div class="da-article-author">
                <i class="fa-regular fa-circle-user"></i> By <span><?php echo htmlspecialchars($post['author'] ?? 'DevicesArena'); ?></span>
              </div>
              <div class="da-article-tags">
                <?php
                $tags = $post['tags'] ?? null;
                if (!empty($tags)) {
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
                  if (is_array($tags)) {
                    foreach ($tags as $tag) {
                      if (!empty($tag)) {
                        echo '<span class="da-tag">' . htmlspecialchars($tag) . '</span>';
                      }
                    }
                  }
                }
                ?>
              </div>
            </div>
          </header>

          <?php if (!empty($post['featured_image'])): ?>
          <div class="da-article-featured-image">
            <img src="<?php echo getAbsoluteImagePath($post['featured_image'], $base); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
          </div>
          <?php endif; ?>

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
        </article>

        <!-- Comments Section -->
        <div class="da-widget mt-5" id="comments">
          <div class="da-widget-header">
            <h3>Reader Opinions and Reviews</h3>
            <div class="da-widget-icon"><i class="fa fa-comments"></i></div>
          </div>
          <div class="da-widget-body">
            
            <div class="da-comments-list">
              <?php if (!empty($postComments)): ?>
                <?php foreach ($postComments as $comment): ?>
                  <div class="da-comment-thread" id="comment-<?php echo $comment['id']; ?>">
                    <div class="da-comment-avatar">
                      <?php echo getAvatarDisplay($comment['name'], $comment['email']); ?>
                    </div>
                    <div class="da-comment-content">
                      <div class="da-comment-header">
                        <span class="da-comment-name"><?php echo htmlspecialchars($comment['name']); ?></span>
                        <span class="da-comment-time"><i class="fa-regular fa-clock"></i> <?php echo timeAgo($comment['created_at']); ?></span>
                      </div>
                      <div class="da-comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></div>
                      <div class="da-comment-footer">
                        <button type="button" class="da-comment-reply-btn" 
                                data-comment-id="<?php echo $comment['id']; ?>" 
                                data-comment-name="<?php echo htmlspecialchars($comment['name']); ?>">
                          <i class="fa fa-reply"></i> Reply
                        </button>
                      </div>
                    </div>

                    <?php if (!empty($comment['replies'])): ?>
                      <?php foreach ($comment['replies'] as $reply): ?>
                        <div class="da-comment-thread da-reply" id="comment-<?php echo $reply['id']; ?>">
                          <div class="da-comment-avatar">
                            <?php echo getAvatarDisplay($reply['name'], $reply['email']); ?>
                          </div>
                          <div class="da-comment-content">
                            <div class="da-comment-header">
                              <span class="da-comment-name"><?php echo htmlspecialchars($reply['name']); ?> <small class="da-reply-indicator-text"><i class="fa fa-share"></i> replied</small></span>
                              <span class="da-comment-time"><i class="fa-regular fa-clock"></i> <?php echo timeAgo($reply['created_at']); ?></span>
                            </div>
                            <div class="da-comment-text"><?php echo nl2br(htmlspecialchars($reply['comment'])); ?></div>
                            <div class="da-comment-footer">
                              <button type="button" class="da-comment-reply-btn" 
                                      data-comment-id="<?php echo $comment['id']; ?>" 
                                      data-comment-name="<?php echo htmlspecialchars($comment['name']); ?>">
                                <i class="fa fa-reply"></i> Reply
                              </button>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="da-empty-state">
                  <i class="fa fa-comments"></i>
                  <p>No comments yet. Be the first to share your opinion!</p>
                </div>
              <?php endif; ?>
            </div>

            <!-- Comment Form -->
            <div class="da-comment-form-wrap mt-5">
              <h4 class="da-form-title">Share Your Opinion</h4>
              <div id="reply-indicator" class="da-reply-indicator d-none">
                <div class="da-reply-info"><i class="fa fa-reply me-2"></i>Replying to <strong id="reply-to-name"></strong></div>
                <button type="button" id="cancel-reply" class="da-btn-close"><i class="fa fa-times"></i></button>
              </div>
              
              <form id="post-comment-form" method="POST" class="da-form">
                <input type="hidden" name="action" value="comment_post">
                <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post['id']); ?>">
                <input type="hidden" name="parent_id" id="parent_id" value="">
                
                <div class="da-form-row">
                  <div class="da-form-group">
                    <input type="text" class="da-input" name="name" placeholder="Your Name" required <?php if ($isPublicUser && $publicUserName): ?>value="<?php echo htmlspecialchars($publicUserName); ?>" disabled<?php endif; ?>>
                    <?php if ($isPublicUser && $publicUserName): ?><input type="hidden" name="name" value="<?php echo htmlspecialchars($publicUserName); ?>"><?php endif; ?>
                  </div>
                  <div class="da-form-group">
                    <input type="email" class="da-input" name="email" placeholder="Your Email" required <?php if ($isPublicUser && !empty($_SESSION['public_user_email'])): ?>value="<?php echo htmlspecialchars($_SESSION['public_user_email']); ?>" disabled<?php endif; ?>>
                    <?php if ($isPublicUser && !empty($_SESSION['public_user_email'])): ?><input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['public_user_email']); ?>"><?php endif; ?>
                  </div>
                </div>
                
                <div class="da-form-group">
                  <textarea class="da-input" name="comment" rows="5" placeholder="Share your thoughts about this article..." required></textarea>
                </div>
                
                <div class="da-form-group da-captcha-group">
                  <label class="da-form-label">Type the words shown below</label>
                  <div class="da-captcha-box">
                    <img src="<?php echo $base; ?>captcha.php" id="captcha-image" alt="CAPTCHA" onclick="refreshCaptcha()" title="Click to refresh">
                    <button type="button" class="da-cta-btn secondary" onclick="refreshCaptcha()" title="Refresh CAPTCHA"><i class="fa fa-rotate-right"></i></button>
                    <input type="text" class="da-input" name="captcha" id="captcha-input" placeholder="Enter the words" required autocomplete="off">
                  </div>
                </div>
                
                <div class="da-form-footer">
                  <div class="da-form-submit-info">
                    <button type="submit" class="da-cta-btn">Post Your Comment</button>
                    <small class="da-form-note">Comments are moderated and will appear after approval.</small>
                  </div>
                  <div class="da-comments-count-text">
                    Total reader comments: <b class="da-text-gradient"><?php echo $postCommentCount; ?></b>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </main>

      <!-- Sidebar -->
      <aside class="da-sidebar">
        <?php include(__DIR__ . '/includes/sidebar/brands-area.php'); ?>
        <?php include(__DIR__ . '/includes/sidebar/ad-placeholder.php'); ?>
        <?php include(__DIR__ . '/includes/sidebar/latest-devices.php'); ?>
        <?php include(__DIR__ . '/includes/sidebar/popular-comparisons.php'); ?>
        <?php include(__DIR__ . '/includes/sidebar/top-daily-interests.php'); ?>
      </aside>
    </div>
    <!-- BOTTOM AREA -->

    <!-- ── IN STORES NOW ── -->
    <?php include('includes/bottom-area/in-stores-now.php'); ?>

    <!-- ── TRENDING COMPARISONS ── -->
    <?php include('includes/bottom-area/trending-comparisons.php'); ?>

    <!-- ── FEATURED POSTS TICKER ── -->
    <?php
    $tickerLabel = 'Featured';
    $tickerTitle = 'All Featured Posts';
    $tickerLink = 'featured';
    include('includes/bottom-area/featured-posts.php');
    ?>

    <!-- ── INFINITE BRAND MARQUEE ── -->
    <?php include('includes/bottom-area/brand-marquee.php'); ?>

  </div>

  <!-- ══════════════════════ NEW FOOTER ══════════════════════ -->
  <?php include('includes/footer.php'); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    window.baseURL = '<?php echo $base; ?>';

    // ── Theme Toggle ──
    const themeToggles = [document.getElementById('da-theme-toggle'), document.getElementById('da-mobile-theme-toggle')];

    function updateThemeIcons() {
      const isLight = document.documentElement.getAttribute('data-theme') === 'light';
      themeToggles.forEach(btn => {
        if (!btn) return;
        const icon = btn.querySelector('i');
        if (icon) {
          icon.className = isLight ? 'fa fa-moon' : 'fa fa-sun';
        }
      });
    }
    updateThemeIcons();

    themeToggles.forEach(btn => {
      if (btn) {
        btn.addEventListener('click', () => {
          if (document.documentElement.getAttribute('data-theme') === 'light') {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('da-theme', 'dark');
          } else {
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('da-theme', 'light');
          }
          updateThemeIcons();
        });
      }
    });

    // Auto-Sliders moved to redesign/sliders.js

    // ── Navbar scroll effect ──
    const navbar = document.getElementById('da-navbar');
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 40);
    }, {
      passive: true
    });

    // ── Mobile Menu ──
    const hamburger = document.getElementById('da-hamburger');
    const mobileMenu = document.getElementById('da-mobile-menu');
    hamburger.addEventListener('click', () => {
      hamburger.classList.toggle('open');
      mobileMenu.classList.toggle('open');
      document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
    });

    function closeMobileMenu() {
      hamburger.classList.remove('open');
      mobileMenu.classList.remove('open');
      document.body.style.overflow = '';
    }

    // ── Brand Strip Arrows ──
    const brandScroll = document.getElementById('brand-strip-scroll');
    document.getElementById('brand-strip-left').addEventListener('click', () => brandScroll.scrollBy({
      left: -300,
      behavior: 'smooth'
    }));
    document.getElementById('brand-strip-right').addEventListener('click', () => brandScroll.scrollBy({
      left: 300,
      behavior: 'smooth'
    }));

    // ── Live Search ──
    const searchInput = document.getElementById('da-search-input');
    const searchResults = document.getElementById('da-search-results');
    if (searchInput && searchResults) {
      let searchTimer;
      searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length < 2) {
          searchResults.classList.remove('open');
          return;
        }
        searchTimer = setTimeout(() => {
          Promise.all([
            fetch(baseURL + 'api_get_devices.php?q=' + encodeURIComponent(q) + '&limit=4').then(r => r.json()).catch(() => ({
              devices: []
            })),
            fetch(baseURL + 'api_get_posts.php?q=' + encodeURIComponent(q) + '&limit=4').then(r => r.json()).catch(() => ({
              posts: []
            }))
          ]).then(([devData, postData]) => {
            const devices = devData.devices || [];
            const posts = postData.posts || [];
            if (!devices.length && !posts.length) {
              searchResults.innerHTML = '<div class="da-search-result-item"><div class="sr-text">No results found</div></div>';
              searchResults.classList.add('open');
              return;
            }
            let html = '';
            devices.forEach(d => {
              html += `<a href="${baseURL}device/${encodeURIComponent(d.slug || d.id)}" class="da-search-result-item">
          ${d.image ? `<img src="${d.image}" onerror="this.style.display='none'">` : ''}
          <div><div class="sr-text">${d.name}</div><div class="sr-meta"><i class="fa fa-mobile-screen me-1"></i>${d.brand_name || 'Device'}</div></div>
        </a>`;
            });
            posts.forEach(p => {
              html += `<a href="${baseURL}post/${encodeURIComponent(p.slug)}" class="da-search-result-item">
          ${p.featured_image ? `<img src="${p.featured_image}" onerror="this.style.display='none'">` : ''}
          <div><div class="sr-text">${p.title}</div><div class="sr-meta"><i class="fa fa-newspaper me-1"></i>${p.created_at ? p.created_at.substring(0, 10) : 'Article'}</div></div>
        </a>`;
            });
            searchResults.innerHTML = html;
            searchResults.classList.add('open');
          });
        }, 320);
      });
      document.addEventListener('click', (e) => {
        const wrap = document.getElementById('da-search-wrap');
        if (wrap && !wrap.contains(e.target)) searchResults.classList.remove('open');
      });
    }

    // ── Newsletter ──
    document.getElementById('da-newsletter-btn').addEventListener('click', function () {
      const email = document.getElementById('da-newsletter-email').value.trim();
      const msg = document.getElementById('da-newsletter-msg');
      if (!email) {
        msg.textContent = 'Please enter your email.';
        msg.className = 'error';
        return;
      }
      this.disabled = true;
      this.textContent = 'Subscribing...';
      const btn = this;
      fetch(baseURL + 'handle_newsletter.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'newsletter_email=' + encodeURIComponent(email)
      })
        .then(r => r.json())
        .then(data => {
          msg.textContent = data.message;
          msg.className = data.success ? 'success' : 'error';
          if (data.success) document.getElementById('da-newsletter-email').value = '';
          btn.disabled = false;
          btn.textContent = 'Subscribe';
        }).catch(() => {
          msg.textContent = 'An error occurred.';
          msg.className = 'error';
          btn.disabled = false;
          btn.textContent = 'Subscribe';
        });
    });

    // ── Notification mark seen ──
    function markNotificationsAsSeen() {
      const dots = ['notifDotDesktop'];
      dots.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
      });
      fetch(baseURL + 'notification_handler.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=mark_seen'
      }).catch(() => { });
    }
    const bell = document.getElementById('notificationBellDesktop');
    if (bell) bell.addEventListener('click', () => setTimeout(markNotificationsAsSeen, 100));

    // ── Auth helpers ──
    function userAuthFetch(action, fd) {
      fd.append('action', action);
      return fetch(baseURL + 'user_auth_handler.php', {
        method: 'POST',
        body: fd
      }).then(r => r.json());
    }

    function showAuthMsg(id, msg, type) {
      const el = document.getElementById(id);
      el.className = 'alert alert-' + type + ' alert-dismissible fade show';
      el.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
      el.style.display = 'block';
    }

    const loginForm = document.getElementById('publicLoginForm');
    if (loginForm) loginForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const btn = document.getElementById('loginSubmitBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Logging in...';
      userAuthFetch('login', new FormData(this)).then(data => {
        if (data.success) {
          showAuthMsg('login-message', data.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showAuthMsg('login-message', data.message, 'danger');
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-right-to-bracket me-1"></i>Login';
        }
      }).catch(() => {
        showAuthMsg('login-message', 'An error occurred.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-right-to-bracket me-1"></i>Login';
      });
    });

    const signupForm = document.getElementById('publicSignupForm');
    if (signupForm) signupForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const btn = document.getElementById('signupSubmitBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Creating account...';
      userAuthFetch('register', new FormData(this)).then(data => {
        if (data.success) {
          showAuthMsg('signup-message', data.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showAuthMsg('signup-message', data.message, 'danger');
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-user-plus me-1"></i>Create Account';
        }
      }).catch(() => {
        showAuthMsg('signup-message', 'An error occurred.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-user-plus me-1"></i>Create Account';
      });
    });


      function loadMore() {
        if (loading || !hasMore) return;
        loading = true;
        page++;
        if (loader) loader.style.display = 'flex';
        fetch(baseURL + 'load_posts.php?page=' + page + '&type=featured&format=block')
          .then(r => r.json())
          .then(data => {
            if (data.success && data.html) {
              if (loader) loader.insertAdjacentHTML('beforebegin', data.html);
              hasMore = data.hasMore;
              // Re-skin new items
              container.querySelectorAll('.div-block').forEach(el => {
                if (!el.classList.contains('da-ticker-item')) el.classList.add('da-ticker-compat');
              });
            } else {
              hasMore = false;
            }
            if (!hasMore && loader) loader.style.display = 'none';
            loading = false;
          }).catch(() => {
            loading = false;
            if (loader) loader.style.display = 'none';
          });
      }
    })();
  </script>
  <script src="<?php echo $base; ?>redesign/sliders.js"></script>
</body>

</html>