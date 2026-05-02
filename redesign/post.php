<?php
session_start();
// Device Details - Public page for viewing individual device specifications
// No authentication required

// Database connection
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database_functions.php';
require_once __DIR__ . '/../phone_data.php';

// New clean URL format: domain/post/slug (instead of domain/post.php?slug=xyz)
// The .htaccess file rewrites clean URLs to this page and passes slug as query parameter
// Base path variable is now defined in config.php

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
  include '../404.php';
  exit;
}

// Helper functions for posts
function getPostComments($post_id)
{
  global $pdo;
  $stmt = $pdo->prepare("SELECT * FROM post_comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at ASC");
  $stmt->execute([$post_id]);
  $all = $stmt->fetchAll();

  // Build threaded structure: parent comments with nested replies (1 level deep)
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
  // Attach replies to their parent
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
    return '<span class="avatar-box">' . $initials . '</span>';
  }
}

function parseTags($tags)
{
  if (empty($tags)) return [];
  if (is_array($tags)) return $tags;

  $tagsString = trim($tags);
  // JSON array format: [apple,smartphones]
  if (strlen($tagsString) > 1 && $tagsString[0] === '[' && substr($tagsString, -1) === ']') {
    $tagsString = trim($tagsString, '[]');
    return array_map('trim', explode(',', $tagsString));
  }
  // PostgreSQL array format: {"Apple","iOS","Rumors"}
  if (strlen($tagsString) > 1 && $tagsString[0] === '{' && substr($tagsString, -1) === '}') {
    $tagsString = trim($tagsString, '{}');
    return array_map(function ($tag) {
      return trim($tag, '"');
    }, explode(',', $tagsString));
  }
  // Plain comma-separated
  return array_map('trim', explode(',', $tagsString));
}

// Get post comments and count
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
  // Silent fail
}

// Sidebar data
$latestDevices = getAllPhones();
$latestDevices = array_slice(array_reverse($latestDevices), 0, 15);

$brands_stmt = $pdo->prepare("
    SELECT b.*, COUNT(p.id) as device_count
    FROM brands b
    LEFT JOIN phones p ON b.id = p.brand_id
    GROUP BY b.id, b.name, b.description, b.logo_url, b.website, b.created_at, b.updated_at
    ORDER BY COUNT(p.id) DESC, b.name ASC
    LIMIT 36
");
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll();

$all_brands_stmt = $pdo->prepare("SELECT * FROM brands ORDER BY name ASC");
$all_brands_stmt->execute();
$allBrandsModal = $all_brands_stmt->fetchAll();

// Get top viewed/reviewed for sidebar
try {
  $stmt = $pdo->prepare("
        SELECT p.*, b.name as brand_name, COUNT(cv.id) as view_count
        FROM phones p 
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN content_views cv ON CAST(p.id AS VARCHAR) = cv.content_id AND cv.content_type = 'device'
        GROUP BY p.id, b.name
        ORDER BY view_count DESC
        LIMIT 10
    ");
  $stmt->execute();
  $topViewedDevices = $stmt->fetchAll();
} catch (Exception $e) {
  $topViewedDevices = [];
}

try {
  $stmt = $pdo->prepare("
        SELECT p.*, b.name as brand_name, COUNT(dc.id) as review_count
        FROM phones p 
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN device_comments dc ON CAST(p.id AS VARCHAR) = dc.device_id AND dc.status = 'approved'
        GROUP BY p.id, b.name
        ORDER BY review_count DESC
        LIMIT 10
    ");
  $stmt->execute();
  $topReviewedDevices = $stmt->fetchAll();
} catch (Exception $e) {
  $topReviewedDevices = [];
}

// SEO Metadata setup
$page_title = htmlspecialchars($post['meta_title'] ?? $post['title']) . " - DevicesArena";
$meta_description = htmlspecialchars($post['meta_description'] ?? $post['short_description'] ?? substr(strip_tags($post['content_body']), 0, 160) . '...');
$featured_image_url = getAbsoluteImagePath($post['featured_image'], $base);

// Note: Comment submission now handled via AJAX (see ajax_comment_handler.php)
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <link rel="canonical" href="<?php echo $canonicalBase; ?>/post/<?php echo htmlspecialchars($post['slug']); ?>" />
  <title><?php echo $page_title; ?></title>
  <meta name="description" content="<?php echo $meta_description; ?>" />
  <meta property="og:title" content="<?php echo $page_title; ?>" />
  <meta property="og:description" content="<?php echo $meta_description; ?>" />
  <meta property="og:image" content="<?php echo $featured_image_url; ?>" />
  <meta property="og:type" content="article" />
  <meta name="twitter:card" content="summary_large_image" />

  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>imges/icon-32.png">
  <link rel="shortcut icon" href="<?php echo $base; ?>imges/icon-32.png">
  <meta name="theme-color" content="#0d0f1a">
  
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9906394285054446" crossorigin="anonymous"></script>
  <!-- Google Analytics -->
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
    (function() {
      var savedTheme = localStorage.getItem('da-theme');
      if (savedTheme === 'light' || (!savedTheme && window.matchMedia('(prefers-color-scheme: light)').matches)) {
        document.documentElement.setAttribute('data-theme', 'light');
      }
    })();
  </script>

  <?php
  // Schema data
  $breadcrumbItems = [
    ["@type" => "ListItem", "position" => 1, "name" => "Home", "item" => "https://www.devicesarena.com/"]
  ];

  if ($post) {
    $breadcrumbItems[] = [
      "@type" => "ListItem", "position" => 2, "name" => "Blog", "item" => "https://www.devicesarena.com/posts"
    ];

    $breadcrumbItems[] = [
      "@type" => "ListItem", "position" => 3, "name" => $post['title'],
      "item" => "https://www.devicesarena.com/post/" . htmlspecialchars($post['slug'])
    ];
  }
  ?>
  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": <?php echo json_encode($breadcrumbItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
    }
  </script>

  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BlogPosting",
      "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "<?php echo $canonicalBase; ?>/post/<?php echo htmlspecialchars($post['slug']); ?>"
      },
      "headline": "<?php echo htmlspecialchars($post['title']); ?>",
      "description": "<?php echo $meta_description; ?>",
      "articleBody": "<?php echo addslashes(strip_tags($post['content_body'])); ?>",
      "image": "<?php echo $featured_image_url; ?>",
      "author": {
        "@type": "Person",
        "name": "<?php echo htmlspecialchars($post['author']); ?>"
      },
      "publisher": {
        "@type": "Organization",
        "name": "DevicesArena",
        "logo": {
          "@type": "ImageObject",
          "url": "<?php echo $base; ?>imges/logo.png"
        }
      },
      "datePublished": "<?php echo $post['publish_date'] ?? $post['created_at']; ?>",
      "dateModified": "<?php echo $post['updated_at'] ?? $post['created_at']; ?>",
      "commentCount": "<?php echo $postCommentCount; ?>"
    }
  </script>

  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "NewsArticle",
      "headline": "<?php echo htmlspecialchars($post['title']); ?>",
      "image": [
        "<?php echo $featured_image_url; ?>"
      ],
      "datePublished": "<?php echo $post['publish_date'] ?? $post['created_at']; ?>",
      "dateModified": "<?php echo $post['updated_at'] ?? $post['created_at']; ?>",
      "author": [{
          "@type": "Person",
          "name": "<?php echo htmlspecialchars($post['author']); ?>",
          "url": "<?php echo $base; ?>"
        }]
    }
  </script>

  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [{
          "@type": "Question",
          "name": "What kind of content does DevicesArena blog cover?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "DevicesArena blog features comprehensive articles about smartphones, tablets, smartwatches, and other mobile devices."
          }
        },
        {
          "@type": "Question",
          "name": "How often is the blog updated?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "We regularly publish new articles covering the latest device releases, tech news, and expert reviews."
          }
        }
      ]
    }
  </script>

</head>

<body>
  <!-- ══════════════════════ NAVBAR ══════════════════════ -->
  <?php include(__DIR__ . '/includes/navbar.php'); ?>

  <!-- ══════════════════════ AUTH MODALS ══════════════════════ -->
  <?php include(__DIR__ . '/includes/login-modal.php'); ?>
  <?php include(__DIR__ . '/includes/signup-modal.php'); ?>
  <?php include(__DIR__ . '/includes/profile-modal.php'); ?>

  <!-- ══════════════════════ MAIN PAGE ══════════════════════ -->
  <div class="da-post-container">
    <div class="da-post-main-grid">
      <!-- Main Content -->
      <main>
        <!-- Hero Section -->
        <header class="da-post-hero">
            <nav class="da-post-hero-meta">
                <a href="<?php echo $base; ?>" class="da-breadcrumb-link">Home</a>
                <span class="da-breadcrumb-sep">/</span>
                <a href="<?php echo $base; ?>posts" class="da-breadcrumb-link">Blog</a>
                <?php if (!empty($post['categories'])): ?>
                    <?php 
                    $cats = $post['categories'];
                    if (is_string($cats)) {
                        $cats = str_replace(['{', '}'], '', $cats);
                        $cats = explode(',', $cats);
                    }
                    foreach ($cats as $cat): ?>
                        <span class="da-category-pill"><?php echo htmlspecialchars(trim($cat, '" ')); ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </nav>
            
            <h1 class="da-post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            
            <div class="da-post-author-meta">
                <div class="da-post-author-img">
                    <?php echo getAvatarDisplay($post['author'], ''); ?>
                </div>
                <div class="da-author-info text-start">
                    <div class="da-comment-author"><?php echo htmlspecialchars($post['author']); ?></div>
                    <div class="da-comment-date"><?php echo date('M j, Y', strtotime($post['publish_date'] ?? $post['created_at'])); ?> • <?php echo $post['view_count']; ?> views</div>
                </div>
            </div>
        </header>

        <!-- Featured Image -->
        <div class="da-post-featured-image-wrapper">
            <img src="<?php echo $featured_image_url; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="da-post-featured-image">
        </div>

        <!-- Jump to Section -->
        <div class="da-post-toc-wrapper mb-4">
          <div class="da-toc-header d-flex align-items-center gap-2">
            <button id="headingPrev" class="da-toc-btn" title="Previous section" style="display:none;"><i class="fa fa-chevron-left"></i></button>
            <select id="headingDropdown" class="da-toc-select" style="display:none;">
              <option value="" disabled selected>Jump to section...</option>
            </select>
            <button id="headingNext" class="da-toc-btn" title="Next section" style="display:none;"><i class="fa fa-chevron-right"></i></button>
          </div>
        </div>

        <!-- Article Content -->
        <article class="da-post-content">
            <?php echo $post['content_body']; ?>
        </article>

        <!-- Tags -->
        <?php 
        $parsedTags = parseTags($post['tags'] ?? null);
        if (!empty($parsedTags)): ?>
            <div class="da-post-tags">
                <?php foreach ($parsedTags as $tag): ?>
                    <a href="<?php echo $base; ?>tag/<?php echo urlencode(trim($tag, '" ')); ?>" class="da-post-tag">#<?php echo htmlspecialchars(trim($tag, '" ')); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Comments Section -->
        <section class="da-comments-section" id="comments">
            <h2 class="da-comments-title"><?php echo $postCommentCount; ?> Thoughts on this post</h2>
            
            <!-- Comment Form -->
            <div class="da-comment-form-card">
                <form id="post-comment-form" class="da-form">
                    <input type="hidden" name="action" value="comment_post">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    <input type="hidden" name="parent_id" id="parent_id" value="">
                    
                    <div id="reply-indicator" class="da-reply-indicator d-none mb-3">
                        <div class="d-flex justify-content-between align-items-center bg-dark p-2 rounded">
                            <span><i class="fa fa-reply me-2"></i>Replying to <strong id="reply-to-name"></strong></span>
                            <button type="button" id="cancel-reply" class="btn btn-sm btn-link text-white p-0"><i class="fa fa-times"></i></button>
                        </div>
                    </div>

                    <div class="da-comment-form-grid">
                        <div class="da-form-group">
                            <input type="text" name="name" class="da-input" placeholder="Name*" required>
                        </div>
                        <div class="da-form-group">
                            <input type="email" name="email" class="da-input" placeholder="Email*" required>
                        </div>
                    </div>
                    <div class="da-form-group mb-4">
                        <textarea name="comment" class="da-input" rows="5" placeholder="Your thought..." required></textarea>
                    </div>
                    
                    <div class="da-form-group da-captcha-group mb-4">
                      <div class="da-captcha-box d-flex align-items-center gap-3">
                        <img src="<?php echo $base; ?>captcha.php" id="captcha-image" alt="CAPTCHA" onclick="refreshCaptcha()" style="cursor:pointer; border-radius: 8px; height: 45px;">
                        <input type="text" class="da-input" name="captcha" placeholder="Code" required style="max-width: 150px;">
                      </div>
                    </div>

                    <button type="submit" class="da-cta-btn w-100 py-3" style="border-radius: 12px; font-weight: 700;">Post Comment</button>
                </form>
            </div>

            <!-- Comment List -->
            <div class="da-comment-list">
                <?php if (empty($postComments)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa fa-comments fa-3x mb-3 opacity-20"></i>
                        <p>No comments yet. Be the first to share your thoughts!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($postComments as $comment): ?>
                        <div class="da-comment-item" id="comment-<?php echo $comment['id']; ?>">
                            <div class="da-comment-card">
                                <div class="da-comment-avatar">
                                    <?php echo getAvatarDisplay($comment['name'], $comment['email']); ?>
                                </div>
                                <div class="da-comment-body">
                                    <div class="da-comment-header">
                                        <span class="da-comment-author"><?php echo htmlspecialchars($comment['name']); ?></span>
                                        <span class="da-comment-date"><?php echo timeAgo($comment['created_at']); ?></span>
                                    </div>
                                    <div class="da-comment-text">
                                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                    </div>
                                    <div class="da-comment-actions">
                                        <div class="da-reply-btn" onclick="setReply(<?php echo $comment['id']; ?>, '<?php echo addslashes(htmlspecialchars($comment['name'])); ?>')">
                                            <i class="fa fa-reply"></i> Reply
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Replies -->
                            <?php if (!empty($comment['replies'])): ?>
                                <div class="da-comment-replies">
                                    <?php foreach ($comment['replies'] as $reply): ?>
                                        <div class="da-comment-item" id="comment-<?php echo $reply['id']; ?>">
                                            <div class="da-comment-card">
                                                <div class="da-comment-avatar">
                                                    <?php echo getAvatarDisplay($reply['name'], $reply['email']); ?>
                                                </div>
                                                <div class="da-comment-body">
                                                    <div class="da-comment-header">
                                                        <span class="da-comment-author"><?php echo htmlspecialchars($reply['name']); ?></span>
                                                        <span class="da-comment-date"><?php echo timeAgo($reply['created_at']); ?></span>
                                                    </div>
                                                    <div class="da-comment-text">
                                                        <?php echo nl2br(htmlspecialchars($reply['comment'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
      </main>

      <!-- Sidebar -->
      <aside class="da-sidebar">
        <?php include(__DIR__ . '/includes/sidebar/ad-placeholder.php'); ?>
        <?php include(__DIR__ . '/includes/sidebar/brands-area.php'); ?>
        <?php include(__DIR__ . '/includes/sidebar/latest-devices.php'); ?>
        <?php include(__DIR__ . '/includes/sidebar/popular-comparisons.php'); ?>
      </aside>
    </div>
  </div>

  <!-- ══════════════════════ FOOTER ══════════════════════ -->
  <?php include(__DIR__ . '/includes/footer.php'); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    window.baseURL = '<?php echo $base; ?>';
    var COMMENT_AJAX_BASE = '<?php echo $base; ?>';
  </script>
  <script src="<?php echo $base; ?>js/comment-ajax.js"></script>

  <script>
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

    // ── Navbar scroll effect ──
    const navbar = document.getElementById('da-navbar');
    window.addEventListener('scroll', () => {
      if(navbar) navbar.classList.toggle('scrolled', window.scrollY > 40);
    }, { passive: true });

    // ── Mobile Menu ──
    const hamburger = document.getElementById('da-hamburger');
    const mobileMenu = document.getElementById('da-mobile-menu');
    if(hamburger && mobileMenu) {
        hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('open');
        mobileMenu.classList.toggle('open');
        document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
        });
    }

    function closeMobileMenu() {
      if(hamburger) hamburger.classList.remove('open');
      if(mobileMenu) mobileMenu.classList.remove('open');
      document.body.style.overflow = '';
    }

    // ── Post Page Logic ──
    function refreshCaptcha() {
      const captchaImg = document.getElementById('captcha-image');
      if(captchaImg) captchaImg.src = '<?php echo $base; ?>captcha.php?' + Date.now();
    }

    function setReply(id, name) {
      document.getElementById('parent_id').value = id;
      document.getElementById('reply-to-name').innerText = name;
      document.getElementById('reply-indicator').classList.remove('d-none');
      document.getElementById('comments').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    document.getElementById('cancel-reply')?.addEventListener('click', function() {
      document.getElementById('parent_id').value = '';
      document.getElementById('reply-indicator').classList.add('d-none');
    });

    // --- Post Comment Submission ---
    document.getElementById('post-comment-form')?.addEventListener('submit', function(e) {
      e.preventDefault();
      const form = this;
      const formData = new FormData(form);
      const btn = form.querySelector('button[type="submit"]');
      const originalText = btn.innerText;

      btn.innerText = 'Submitting...';
      btn.disabled = true;

      fetch('<?php echo $base; ?>ajax_comment_handler.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if(data.success) {
          alert('Comment posted successfully! It will appear after moderation.');
          form.reset();
          document.getElementById('cancel-reply')?.click();
          refreshCaptcha();
        } else {
          alert(data.message || 'Error posting comment.');
          refreshCaptcha();
        }
      })
      .catch(err => {
        console.error(err);
        alert('An error occurred. Please try again.');
      })
      .finally(() => {
        btn.innerText = originalText;
        btn.disabled = false;
      });
    });

    // ── Heading Jump Controls ──
    window.addEventListener('load', function() {
        const content = document.querySelector('.da-post-content');
        const dropdown = document.getElementById('headingDropdown');
        if (!content || !dropdown) return;

        const headings = content.querySelectorAll('h1, h2, h3');
        if (!headings.length) return;

        headings.forEach((h, idx) => {
            const text = (h.textContent || '').trim();
            if (!text) return;

            let id = h.id || text.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-');
            h.id = id;

            const opt = document.createElement('option');
            opt.value = id;
            opt.textContent = text.length > 50 ? text.slice(0, 47) + '...' : text;
            dropdown.appendChild(opt);
        });

        dropdown.style.display = 'block';
        const prevBtn = document.getElementById('headingPrev');
        const nextBtn = document.getElementById('headingNext');
        if(prevBtn) prevBtn.style.display = 'flex';
        if(nextBtn) nextBtn.style.display = 'flex';

        dropdown.addEventListener('change', function() {
            const target = document.getElementById(this.value);
            if (target) {
                const nav = document.getElementById('da-navbar');
                const offset = (nav ? nav.offsetHeight : 0) + 20;
                window.scrollTo({
                    top: target.offsetTop - offset,
                    behavior: 'smooth'
                });
            }
        });

        if(prevBtn) prevBtn.addEventListener('click', () => {
            if(dropdown.selectedIndex > 1) {
                dropdown.selectedIndex--;
                dropdown.dispatchEvent(new Event('change'));
            }
        });
        if(nextBtn) nextBtn.addEventListener('click', () => {
            if(dropdown.selectedIndex < dropdown.options.length - 1) {
                dropdown.selectedIndex++;
                dropdown.dispatchEvent(new Event('change'));
            }
        });
    });
  </script>
</body>
</html>
