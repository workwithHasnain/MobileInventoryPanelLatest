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

function getPostComments($post_id)
{
  global $pdo;
  $stmt = $pdo->prepare("SELECT * FROM post_comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at ASC");
  $stmt->execute([$post_id]);
  $all = $stmt->fetchAll();
  $parents = []; $replies = [];
  foreach ($all as $c) {
    if (empty($c['parent_id'])) { $c['replies'] = []; $parents[$c['id']] = $c; }
    else $replies[] = $c;
  }
  foreach ($replies as $r) {
    $pid = $r['parent_id'];
    if (isset($parents[$pid])) $parents[$pid]['replies'][] = $r;
    else { foreach ($parents as &$p) { foreach ($p['replies'] as $er) { if ($er['id'] == $pid) { $p['replies'][] = $r; break 2; } } } unset($p); }
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

function getAvatarDisplay($name, $email)
{
  if (!empty($email)) return '<img src="' . getGravatarUrl($email) . '" alt="' . htmlspecialchars($name) . '">';
  $initials = strtoupper(substr($name, 0, 1));
  $colors = ['#6366f1','#8b5cf6','#ec4899','#14b8a6','#f59e0b','#10b981'];
  $color = $colors[abs(crc32($name)) % count($colors)];
  return '<span class="da-comment-avatar-initial" style="background:' . $color . '">' . $initials . '</span>';
}

function timeAgo($datetime)
{
  $time = time() - strtotime($datetime);
  if ($time < 60) return 'just now';
  if ($time < 3600) return floor($time / 60) . 'm ago';
  if ($time < 86400) return floor($time / 3600) . 'h ago';
  if ($time < 2592000) return floor($time / 86400) . 'd ago';
  if ($time < 31536000) return floor($time / 2592000) . 'mo ago';
  return floor($time / 31536000) . 'y ago';
}

$pdo = getConnection();

// Auth
$isPublicUser = !empty($_SESSION['public_user_id']);
$publicUserName = $_SESSION['public_user_name'] ?? '';
$publicUserInitial = $isPublicUser ? strtoupper(substr($publicUserName, 0, 1)) : '';
if (!isset($_SESSION['notif_seen'])) $_SESSION['notif_seen'] = false;
$hasUnreadNotifications = $isPublicUser && !$_SESSION['notif_seen'];

// Weekly posts for notifications
try {
  $weekly_stmt = $pdo->prepare("SELECT p.id,p.title,p.slug,p.featured_image,p.created_at FROM posts p WHERE p.status ILIKE 'published' AND p.created_at >= CURRENT_TIMESTAMP - INTERVAL '7 days' ORDER BY p.created_at DESC LIMIT 10");
  $weekly_stmt->execute();
  $weekly_posts = $weekly_stmt->fetchAll();
} catch (Exception $e) { $weekly_posts = []; }

// Posts — featured only (for bottom ticker)
$posts_stmt = $pdo->prepare("SELECT p.*,(SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id=p.id AND pc.status='approved') as comment_count FROM posts p WHERE p.status ILIKE 'published' AND p.is_featured = true ORDER BY p.created_at DESC LIMIT 20");
$posts_stmt->execute();
$posts = $posts_stmt->fetchAll();

// Comparisons
try { $topComparisons = getPopularComparisons(10); } catch (Exception $e) { $topComparisons = []; }

// Latest devices
$latestDevices = getAllPhones();
$latestDevices = array_slice(array_reverse($latestDevices), 0, 15);

// Brands
$brands_stmt = $pdo->prepare("SELECT b.*,COUNT(p.id) as device_count FROM brands b LEFT JOIN phones p ON b.id=p.brand_id GROUP BY b.id,b.name,b.description,b.logo_url,b.website,b.created_at,b.updated_at ORDER BY COUNT(p.id) DESC,b.name ASC LIMIT 36");
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll();

// All brands alphabetically for brand/device modal
$all_brands_stmt = $pdo->prepare("SELECT * FROM brands ORDER BY name ASC");
$all_brands_stmt->execute();
$allBrandsModal = $all_brands_stmt->fetchAll();

// ── Resolve post by slug ──
$slug = $_GET['slug'] ?? $_GET['id'] ?? null;
if (!$slug) { header('Location: ' . $base); exit; }

if (is_numeric($slug)) {
  $stmt = $pdo->prepare("SELECT * FROM posts WHERE (slug = ? OR id = ?) AND status ILIKE 'published'");
  $stmt->execute([$slug, intval($slug)]);
} else {
  $stmt = $pdo->prepare("SELECT * FROM posts WHERE slug = ? AND status ILIKE 'published'");
  $stmt->execute([$slug]);
}
$post = $stmt->fetch();

if (!$post) { header('HTTP/1.0 404 Not Found'); include __DIR__ . '/../404.php'; exit; }

// Comments
$postComments = getPostComments($post['id']);
$postCommentCount = getPostCommentCount($post['id']);

// View tracking
try {
  $user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
  $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
  $view_stmt = $pdo->prepare("INSERT INTO content_views (content_type, content_id, ip_address, user_agent) VALUES ('post', CAST(? AS VARCHAR), ?, ?) ON CONFLICT (content_type, content_id, ip_address) DO NOTHING");
  $view_stmt->execute([$post['id'], $user_ip, $user_agent]);
  $upd = $pdo->prepare("UPDATE posts SET view_count = (SELECT COUNT(*) FROM content_views WHERE content_type = 'post' AND content_id = CAST(? AS VARCHAR)) WHERE id = ?");
  $upd->execute([$post['id'], $post['id']]);
} catch (Exception $e) {}

// Parse tags helper — exact match of legacy post.php tag parsing logic
function parseTags($tags) {
  if (empty($tags)) return [];
  if (is_string($tags)) {
    $tagsString = trim($tags);
    // JSON array format: [apple,smartphones]
    if (strlen($tagsString) > 1 && $tagsString[0] === '[' && substr($tagsString, -1) === ']') {
      $tagsString = trim($tagsString, '[]');
      return array_filter(array_map('trim', explode(',', $tagsString)));
    }
    // PostgreSQL array format: {"Apple","iOS","Rumors"}
    elseif (strlen($tagsString) > 1 && $tagsString[0] === '{' && substr($tagsString, -1) === '}') {
      $tagsString = trim($tagsString, '{}');
      return array_filter(array_map(function($tag) {
        return trim($tag, '"');
      }, explode(',', $tagsString)));
    }
    // Plain comma-separated
    else {
      return array_filter(array_map('trim', explode(',', $tagsString)));
    }
  }
  return is_array($tags) ? $tags : [];
}
$postTags = parseTags($post['tags'] ?? '');

// Session logged-in user for comment form prefill — matches legacy logic exactly
$loggedInName = '';
$loggedInEmail = '';
$isUserLoggedIn = false;
if (!empty($_SESSION['public_user_id'])) {
  $loggedInName  = $_SESSION['public_user_name'] ?? '';
  $loggedInEmail = $_SESSION['public_user_email'] ?? '';
  $isUserLoggedIn = true;
} elseif (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
  // Admin/staff session fallback
  $loggedInName  = $_SESSION['username'] ?? '';
  $loggedInEmail = '';
  $isUserLoggedIn = true;
}

// Newsletter server-side handler
$newsletter_success = '';
$newsletter_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'newsletter_subscribe') {
  $nl_email = trim($_POST['newsletter_email'] ?? '');
  $nl_name  = trim($_POST['newsletter_name'] ?? '');
  if (empty($nl_email)) {
    $newsletter_error = 'Email address is required.';
  } elseif (!filter_var($nl_email, FILTER_VALIDATE_EMAIL)) {
    $newsletter_error = 'Please enter a valid email address.';
  } else {
    $chk = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $chk->execute([$nl_email]);
    if ($chk->fetch()) {
      $newsletter_error = 'This email is already subscribed to our newsletter.';
    } else {
      $ins = $pdo->prepare("INSERT INTO newsletter_subscribers (email, name, status, subscribed_at) VALUES (?, ?, 'active', NOW())");
      if ($ins->execute([$nl_email, $nl_name])) {
        $newsletter_success = 'Thank you for subscribing! You will receive the latest tech updates.';
      } else {
        $newsletter_error = 'Failed to subscribe. Please try again.';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<?php
  $postTitle = htmlspecialchars($post['meta_title'] ?? $post['title']);
  $postDesc  = htmlspecialchars($post['meta_description'] ?? $post['short_description'] ?? substr(strip_tags($post['content_body'] ?? ''), 0, 160));
  $postImage = !empty($post['featured_image']) ? getAbsoluteImagePath($post['featured_image'], 'https://www.devicesarena.com/') : 'https://www.devicesarena.com/imges/icon-256.png';
  $postUrl   = 'https://www.devicesarena.com/post/' . htmlspecialchars($post['slug']);
  $postDate  = isset($post['created_at']) ? date('Y-m-d', strtotime($post['created_at'])) : date('Y-m-d');
  $postModified = !empty($post['updated_at']) ? date('Y-m-d', strtotime($post['updated_at'])) : $postDate;
  $breadcrumbItems = [
    ["@type"=>"ListItem","position"=>1,"name"=>"Home","item"=>"https://www.devicesarena.com/"],
    ["@type"=>"ListItem","position"=>2,"name"=>"Blog","item"=>"https://www.devicesarena.com/news"],
    ["@type"=>"ListItem","position"=>3,"name"=>$post['title'],"item"=>$postUrl],
  ];
?>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <link rel="canonical" href="<?php echo $canonicalBase; ?>/post/<?php echo htmlspecialchars($post['slug']); ?>" />
  <title><?php echo $postTitle; ?> — DevicesArena</title>
  <meta name="description" content="<?php echo $postDesc; ?>" />
  <meta property="og:title" content="<?php echo $postTitle; ?>" />
  <meta property="og:description" content="<?php echo $postDesc; ?>" />
  <meta property="og:image" content="<?php echo $postImage; ?>" />
  <meta property="og:type" content="article" />
  <meta property="og:url" content="<?php echo $postUrl; ?>" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?php echo $postTitle; ?>" />
  <meta name="twitter:description" content="<?php echo $postDesc; ?>" />
  <meta name="twitter:image" content="<?php echo $postImage; ?>" />
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>imges/icon-32.png">
  <link rel="shortcut icon" href="<?php echo $base; ?>imges/icon-32.png">
  <meta name="theme-color" content="#0d0f1a">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9906394285054446" crossorigin="anonymous"></script>
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-2LDCSSMXJT"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','G-2LDCSSMXJT');</script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?php echo $base; ?>redesign/style.css">
  <!-- Breadcrumb Schema -->
  <script type="application/ld+json">{"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":<?php echo json_encode($breadcrumbItems,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>}</script>
  <!-- BlogPosting Schema -->
  <script type="application/ld+json"><?php echo json_encode([
    "@context"=>"https://schema.org","@type"=>"BlogPosting",
    "headline"=>$post['title'],
    "description"=>$postDesc,
    "image"=>$postImage,
    "url"=>$postUrl,
    "datePublished"=>$postDate,
    "dateModified"=>$postModified,
    "author"=>["@type"=>"Organization","name"=>"DevicesArena"],
    "publisher"=>["@type"=>"Organization","name"=>"DevicesArena","logo"=>["@type"=>"ImageObject","url"=>"https://www.devicesarena.com/imges/icon-256.png"]],
    "commentCount"=>$postCommentCount,
    "keywords"=>"smartphones, devices, reviews, specifications, tech news",
    "articleSection"=>"Technology"
  ],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?></script>
  <!-- Theme Init (Prevents FOUC) -->
  <script>(function(){var t=localStorage.getItem('da-theme');if(t==='light'||(!t&&window.matchMedia('(prefers-color-scheme: light)').matches)){document.documentElement.setAttribute('data-theme','light');}})();</script>
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4554952734894265" crossorigin="anonymous"></script>
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

    <!-- ── POST HERO ── -->
    <div class="cp-hero da-post-hero">
      <div class="cp-hero-bg-container">
        <?php if (!empty($post['featured_image'])): ?>
          <img class="cp-hero-bg-img" src="<?php echo htmlspecialchars(getAbsoluteImagePath($post['featured_image'], $base)); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
        <?php else: ?>
          <img class="cp-hero-bg-img" src="<?php echo $base; ?>hero-images/about-hero.png" alt="Post hero background">
        <?php endif; ?>
      </div>
      <div class="cp-hero-inner">
        <div class="cp-hero-left da-post-hero-left">
          <!-- Breadcrumb -->
          <nav class="da-post-breadcrumb" aria-label="breadcrumb">
            <a href="<?php echo $base; ?>"><i class="fa fa-home"></i></a>
            <span class="da-post-breadcrumb-sep">/</span>
            <?php
              $bcLabel = !empty($post['is_news']) ? 'News' : (!empty($post['is_review']) ? 'Reviews' : 'Posts');
              $bcLink  = !empty($post['is_news']) ? 'news' : (!empty($post['is_review']) ? 'reviews' : 'news');
            ?>
            <a href="<?php echo $base . $bcLink; ?>"><?php echo $bcLabel; ?></a>
            <span class="da-post-breadcrumb-sep">/</span>
            <span><?php $t=$post['title']; echo htmlspecialchars(strlen($t)>42 ? substr($t,0,39).'...' : $t); ?></span>
          </nav>
          <!-- Category / Post type chip -->
          <?php
            if (!empty($post['is_news'])) $heroLabel = 'News';
            elseif (!empty($post['is_review'])) $heroLabel = 'Review';
            elseif (!empty($post['is_featured'])) $heroLabel = 'Featured';
            else $heroLabel = !empty($post['category']) ? $post['category'] : 'Post';
          ?>
          <div class="cp-hero-label"><span><?php echo htmlspecialchars($heroLabel); ?></span></div>
          <h1 class="cp-hero-title da-post-hero-title"><?php echo htmlspecialchars($post['title']); ?></h1>
          <!-- Post meta -->
          <div class="da-post-hero-meta">
            <span><i class="fa fa-user"></i> <?php echo htmlspecialchars($post['author'] ?? 'DevicesArena'); ?></span>
            <span><i class="fa fa-calendar"></i> <?php echo !empty($post['publish_date']) ? date('j M Y', strtotime($post['publish_date'])) : date('j M Y', strtotime($post['created_at'])); ?></span>
            <span><i class="fa fa-comments"></i> <?php echo $postCommentCount; ?> Comments</span>
            <?php if (!empty($post['view_count'])): ?>
              <span><i class="fa fa-eye"></i> <?php echo number_format($post['view_count']); ?> Views</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right: Brand widget -->
        <div class="cp-hero-right">
          <div class="da-section-label"><span>Brands</span></div>
          <div class="da-classic-brand-widget">
            <div class="da-cbw-header">
              <a href="<?php echo $base; ?>phonefinder"><i class="fa fa-mobile-screen"></i> PHONE FINDER</a>
            </div>
            <div class="da-cbw-grid">
              <?php foreach (array_slice($brands, 0, 32) as $brand):
                $brandSlug = $brand['slug'] ?? strtolower(preg_replace('/\s+/', '-', trim($brand['name'])));
              ?>
                <a href="<?php echo $base; ?>brand/<?php echo urlencode($brandSlug); ?>" class="da-cbw-item" title="<?php echo htmlspecialchars($brand['name']); ?>">
                  <?php echo strtoupper(htmlspecialchars($brand['name'])); ?>
                </a>
              <?php endforeach; ?>
            </div>
            <div class="da-cbw-footer">
              <a href="<?php echo $base; ?>brands" class="da-cbw-btn left"><i class="fa fa-bars"></i> ALL BRANDS</a>
              <a href="<?php echo $base; ?>rumored" class="da-cbw-btn right"><i class="fa fa-bullhorn"></i> RUMORS MILL</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── ARTICLE + SIDEBAR ── -->
    <div class="da-content-area">
      <main>
        <!-- Article body -->
        <article class="da-post-article">

          <!-- Heading jump nav -->
          <div class="da-post-jump-nav" id="da-jump-nav" style="display:none;">
            <button id="da-heading-prev" class="da-jump-btn" title="Previous section" disabled><i class="fa fa-chevron-left"></i></button>
            <select id="da-heading-dropdown" class="da-jump-select" aria-label="Jump to section"></select>
            <button id="da-heading-next" class="da-jump-btn" title="Next section"><i class="fa fa-chevron-right"></i></button>
          </div>

          <!-- Author + date bar -->
          <div class="da-post-meta-bar">
            <div class="da-post-meta-left">
              <span class="da-post-author-chip"><?php echo htmlspecialchars($post['author'] ?? 'DevicesArena'); ?></span>
              <span class="da-post-date"><?php echo !empty($post['publish_date']) ? date('j F Y', strtotime($post['publish_date'])) : date('j F Y', strtotime($post['created_at'])); ?></span>
            </div>
            <!-- Tags -->
            <div class="da-post-tags">
              <?php foreach ($postTags as $tag): ?>
                <span class="da-post-tag"><?php echo htmlspecialchars($tag); ?></span>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Content body -->
          <div class="da-post-content-body" id="da-post-content">
            <?php if (!empty($post['content_body'])):
              $content = $post['content_body'];
              if (strip_tags($content) !== $content) echo $content;
              else echo nl2br(htmlspecialchars($content));
            else: ?>
              <p class="da-post-placeholder">Content is being updated. Please check back later.</p>
            <?php endif; ?>
          </div>

          <!-- Share bar -->
          <div class="da-post-share-bar">
            <span class="da-post-share-label"><i class="fa fa-share-nodes"></i> Share</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($postUrl); ?>" target="_blank" rel="noopener" class="da-share-btn da-share-fb" title="Share on Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($postUrl); ?>&text=<?php echo urlencode($post['title']); ?>" target="_blank" rel="noopener" class="da-share-btn da-share-tw" title="Share on X/Twitter"><i class="fab fa-x-twitter"></i></a>
            <a href="https://wa.me/?text=<?php echo urlencode($post['title'] . ' ' . $postUrl); ?>" target="_blank" rel="noopener" class="da-share-btn da-share-wa" title="Share on WhatsApp"><i class="fab fa-whatsapp"></i></a>
            <button class="da-share-btn da-share-copy" id="da-copy-link" title="Copy link" data-url="<?php echo htmlspecialchars($postUrl); ?>"><i class="fa fa-link"></i></button>
          </div>

        </article>

        <!-- ── COMMENTS ── -->
        <section class="da-comments-section">
          <h2 class="da-comments-title"><i class="fa fa-comments"></i> Reader Comments <span class="da-comments-count"><?php echo $postCommentCount; ?></span></h2>

          <div class="da-comments-list">
            <?php if (!empty($postComments)): ?>
              <?php foreach ($postComments as $comment): ?>
                <div class="da-comment" id="comment-<?php echo $comment['id']; ?>">
                  <div class="da-comment-avatar"><?php echo getAvatarDisplay($comment['name'], $comment['email'] ?? ''); ?></div>
                  <div class="da-comment-body">
                    <div class="da-comment-header">
                      <span class="da-comment-name"><?php echo htmlspecialchars($comment['name']); ?></span>
                      <span class="da-comment-time"><?php echo timeAgo($comment['created_at']); ?></span>
                    </div>
                    <p class="da-comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                    <button class="da-reply-btn" data-comment-id="<?php echo $comment['id']; ?>" data-comment-name="<?php echo htmlspecialchars($comment['name']); ?>"><i class="fa fa-reply"></i> Reply</button>
                  </div>
                </div>
                <?php if (!empty($comment['replies'])): ?>
                  <?php foreach ($comment['replies'] as $reply): ?>
                    <div class="da-comment da-comment-reply" id="comment-<?php echo $reply['id']; ?>">
                      <div class="da-comment-avatar"><?php echo getAvatarDisplay($reply['name'], $reply['email'] ?? ''); ?></div>
                      <div class="da-comment-body">
                        <div class="da-comment-header">
                          <span class="da-comment-name"><?php echo htmlspecialchars($reply['name']); ?></span>
                          <span class="da-comment-reply-badge"><i class="fa fa-reply fa-xs"></i> replied</span>
                          <span class="da-comment-time"><?php echo timeAgo($reply['created_at']); ?></span>
                        </div>
                        <p class="da-comment-text"><?php echo nl2br(htmlspecialchars($reply['comment'])); ?></p>
                        <button class="da-reply-btn" data-comment-id="<?php echo $comment['id']; ?>" data-comment-name="<?php echo htmlspecialchars($comment['name']); ?>"><i class="fa fa-reply"></i> Reply</button>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="da-comments-empty"><i class="fa fa-comment-slash"></i><p>No comments yet. Be the first to share your opinion!</p></div>
            <?php endif; ?>
          </div>

          <!-- Comment form -->
          <div class="da-comment-form-wrap">
            <h3 class="da-comment-form-title">Share Your Opinion</h3>
            <div class="da-reply-indicator d-none" id="da-reply-indicator">
              <i class="fa fa-reply"></i> Replying to <strong id="da-reply-to-name"></strong>
              <button type="button" id="da-cancel-reply"><i class="fa fa-times"></i> Cancel</button>
            </div>
            <form id="da-post-comment-form" class="da-comment-form">
              <input type="hidden" name="action" value="comment_post">
              <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
              <input type="hidden" name="parent_id" id="da-parent-id" value="">
              <div class="da-form-row">
                <input type="text" class="da-form-input" name="name" id="da-comment-name" placeholder="Your Name *" required <?php if ($isUserLoggedIn && $loggedInName): ?>value="<?php echo htmlspecialchars($loggedInName); ?>" readonly<?php endif; ?>>
                <input type="email" class="da-form-input" name="email" id="da-comment-email" placeholder="Your Email *" required <?php if ($isUserLoggedIn && $loggedInEmail): ?>value="<?php echo htmlspecialchars($loggedInEmail); ?>" readonly<?php endif; ?>>
              </div>
              <?php if ($isUserLoggedIn && $loggedInName): ?>
                <input type="hidden" name="name" value="<?php echo htmlspecialchars($loggedInName); ?>">
              <?php endif; ?>
              <?php if ($isUserLoggedIn && $loggedInEmail): ?>
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($loggedInEmail); ?>">
              <?php endif; ?>
              <textarea class="da-form-textarea" name="comment" rows="4" placeholder="Share your thoughts about this article..." required></textarea>
              <!-- CAPTCHA -->
              <div class="da-captcha-row">
                <label><i class="fa fa-shield-halved"></i> Type the words shown below</label>
                <div class="da-captcha-inner">
                  <img src="<?php echo $base; ?>captcha.php" id="da-captcha-img" alt="CAPTCHA" title="Click to refresh" onclick="this.src='<?php echo $base; ?>captcha.php?'+Date.now()">
                  <button type="button" onclick="document.getElementById('da-captcha-img').src='<?php echo $base; ?>captcha.php?'+Date.now()" title="Refresh"><i class="fa fa-rotate-right"></i></button>
                  <input type="text" class="da-form-input" name="captcha" placeholder="Enter the words" required autocomplete="off">
                </div>
              </div>
              <div class="da-form-footer">
                <button type="submit" class="da-comment-submit" id="da-comment-submit-btn"><i class="fa fa-paper-plane"></i> Post Comment</button>
                <span class="da-comment-moderation">Comments are moderated.</span>
              </div>
              <div id="da-comment-msg"></div>
            </form>
            <div class="da-comments-total">Total comments: <strong><?php echo $postCommentCount; ?></strong></div>
          </div>
        </section>
      </main>

      <!-- Sidebar -->
      <aside class="da-sidebar">
        <?php include('includes/sidebar/latest-devices.php'); ?>
        <?php include('includes/sidebar/popular-comparisons.php'); ?>
      </aside>
    </div>

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

  <!-- ── Post-Page Scripts ── -->
  <script>
    // ── Heading Jump Navigator ──
    window.addEventListener('load', function () {
      const content = document.getElementById('da-post-content');
      const nav = document.getElementById('da-jump-nav');
      const dropdown = document.getElementById('da-heading-dropdown');
      const prevBtn = document.getElementById('da-heading-prev');
      const nextBtn = document.getElementById('da-heading-next');
      if (!content || !dropdown) return;
      const headings = content.querySelectorAll('h1,h2,h3');
      if (!headings.length) return;
      const usedIds = new Set();
      const slug = s => s.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-+|-+$/, '');
      const opt0 = document.createElement('option');
      opt0.textContent = 'Jump to section…'; opt0.disabled = true; opt0.selected = true;
      dropdown.appendChild(opt0);
      const hEls = [];
      headings.forEach((h, i) => {
        let text = (h.textContent || '').trim().replace(/\s+/g, ' ') || `Section ${i + 1}`;
        let id = h.id || slug(text) || `section-${i + 1}`;
        let base2 = id, c = 2;
        while (usedIds.has(id)) id = `${base2}-${c++}`;
        if (!h.id) h.id = id;
        usedIds.add(id);
        hEls.push(h);
        const o = document.createElement('option');
        o.value = '#' + id;
        o.textContent = text.length > 80 ? text.slice(0, 77) + '…' : text;
        dropdown.appendChild(o);
      });
      nav.style.display = 'flex';
      let activeIdx = -1;
      const scrollTo = el => window.scrollTo({ top: el.getBoundingClientRect().top + window.pageYOffset - 80, behavior: 'smooth' });
      const update = () => { prevBtn.disabled = activeIdx <= 0; nextBtn.disabled = activeIdx >= hEls.length - 1 && activeIdx !== -1; };
      const setActive = idx => { if (idx < 0 || idx >= hEls.length) return; activeIdx = idx; dropdown.selectedIndex = idx + 1; update(); scrollTo(hEls[idx]); };
      dropdown.addEventListener('change', () => setActive(dropdown.selectedIndex - 1));
      prevBtn.addEventListener('click', () => setActive(activeIdx <= 0 ? 0 : activeIdx - 1));
      nextBtn.addEventListener('click', () => setActive(activeIdx === -1 ? 0 : activeIdx + 1));
      update();
    });

    // ── Reply buttons ──
    document.addEventListener('click', function (e) {
      const btn = e.target.closest('.da-reply-btn');
      if (!btn) return;
      const id = btn.dataset.commentId;
      const name = btn.dataset.commentName;
      document.getElementById('da-parent-id').value = id;
      document.getElementById('da-reply-to-name').textContent = name;
      const ind = document.getElementById('da-reply-indicator');
      ind.classList.remove('d-none');
      document.getElementById('da-post-comment-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    const cancelReplyBtn = document.getElementById('da-cancel-reply');
    if (cancelReplyBtn) cancelReplyBtn.addEventListener('click', function () {
      document.getElementById('da-parent-id').value = '';
      document.getElementById('da-reply-indicator').classList.add('d-none');
    });

    // ── Comment Form AJAX ──
    const commentForm = document.getElementById('da-post-comment-form');
    if (commentForm) {
      commentForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('da-comment-submit-btn');
        const msg = document.getElementById('da-comment-msg');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Posting...';
        fetch(window.baseURL + 'ajax_comment_handler.php', {
          method: 'POST',
          body: new FormData(this)
        }).then(r => r.json()).then(data => {
          msg.innerHTML = `<div class="da-comment-response ${data.success ? 'success' : 'error'}">${data.message}</div>`;
          if (data.success) { commentForm.reset(); document.getElementById('da-parent-id').value = ''; document.getElementById('da-reply-indicator').classList.add('d-none'); }
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-paper-plane"></i> Post Comment';
        }).catch(() => {
          msg.innerHTML = '<div class="da-comment-response error">An error occurred. Please try again.</div>';
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-paper-plane"></i> Post Comment';
        });
      });
    }

    // ── Copy link ──
    const copyBtn = document.getElementById('da-copy-link');
    if (copyBtn) copyBtn.addEventListener('click', function () {
      navigator.clipboard.writeText(this.dataset.url).then(() => {
        this.innerHTML = '<i class="fa fa-check"></i>';
        setTimeout(() => { this.innerHTML = '<i class="fa fa-link"></i>'; }, 2000);
      });
    });
  </script>

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

    function openProfileModal() {
      const modal = new bootstrap.Modal(document.getElementById('profileModal'));
      userAuthFetch('get_profile', new FormData()).then(data => {
        if (data.success && data.user) {
          document.getElementById('profile-name').value = data.user.name;
          document.getElementById('profile-email').value = data.user.email;
        }
      });
      document.getElementById('profile-current-password').value = '';
      document.getElementById('profile-new-password').value = '';
      document.getElementById('delete-account-password').value = '';
      document.getElementById('profile-message').style.display = 'none';
      modal.show();
    }

    const profileForm = document.getElementById('profileUpdateForm');
    if (profileForm) profileForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const btn = document.getElementById('profileUpdateBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Saving...';
      userAuthFetch('update_profile', new FormData(this)).then(data => {
        showAuthMsg('profile-message', data.message, data.success ? 'success' : 'danger');
        if (data.success) setTimeout(() => location.reload(), 1000);
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-save me-1"></i>Save Changes';
      }).catch(() => {
        showAuthMsg('profile-message', 'An error occurred.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-save me-1"></i>Save Changes';
      });
    });

    function deletePublicAccount() {
      if (!confirm('Permanently delete your account? This cannot be undone.')) return;
      const pwd = document.getElementById('delete-account-password').value.trim();
      if (!pwd) {
        showAuthMsg('profile-message', 'Please enter your password.', 'warning');
        return;
      }
      const btn = document.getElementById('deleteAccountBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Deleting...';
      const fd = new FormData();
      fd.append('password', pwd);
      userAuthFetch('delete_account', fd).then(data => {
        showAuthMsg('profile-message', data.message, data.success ? 'success' : 'danger');
        if (data.success) setTimeout(() => location.reload(), 1000);
        else {
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-trash me-1"></i>Delete Account';
        }
      }).catch(() => {
        showAuthMsg('profile-message', 'An error occurred.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-trash me-1"></i>Delete Account';
      });
    }

    function publicUserLogout() {
      fetch(baseURL + 'notification_handler.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=reset'
      })
        .finally(() => {
          userAuthFetch('logout', new FormData()).then(() => location.reload());
        });
    }

    function switchToSignup() {
      bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
      setTimeout(() => new bootstrap.Modal(document.getElementById('signupModal')).show(), 300);
    }

    function switchToLogin() {
      bootstrap.Modal.getInstance(document.getElementById('signupModal')).hide();
      setTimeout(() => new bootstrap.Modal(document.getElementById('loginModal')).show(), 300);
    }

    // ── Infinite horizontal post scroll ──
    (function () {
      let page = 1,
        loading = false,
        hasMore = <?php echo count($posts) >= 20 ? 'true' : 'false'; ?>;
      const container = document.getElementById('featured-scroll-container');
      const loader = document.getElementById('featured-load-more');
      if (!container) return;
      container.addEventListener('scroll', function () {
        if (loading || !hasMore) return;
        if (this.scrollLeft + this.clientWidth >= this.scrollWidth - 300) loadMore();
      }, {
        passive: true
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
  <!-- Legacy comment-ajax.js compatibility (COMMENT_AJAX_BASE used by shared script) -->
  <script>var COMMENT_AJAX_BASE = '<?php echo $base; ?>';</script>
  <script src="<?php echo $base; ?>js/comment-ajax.js"></script>
</body>

</html>