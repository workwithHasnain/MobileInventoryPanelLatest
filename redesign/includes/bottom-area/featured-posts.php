<?php
$tickerLabel = $tickerLabel ?? 'Stories';
$tickerTitle = $tickerTitle ?? 'All Featured Posts';
$tickerLink = $tickerLink ?? 'featured';
?>
<section class="da-ticker-section" aria-label="All Posts">
      <div class="da-ticker-header">
        <div>
          <div class="da-section-label"><span><?php echo htmlspecialchars($tickerLabel); ?></span></div>
          <h2 class="da-section-title"><?php echo htmlspecialchars($tickerTitle); ?></h2>
        </div>
        <a href="<?php echo $base; ?><?php echo htmlspecialchars($tickerLink); ?>" class="da-view-all">See All <i class="fa fa-arrow-right"></i></a>
      </div>
      <div class="da-slider-wrap">
        <button class="da-slider-btn prev" aria-label="Previous"><i class="fa fa-chevron-left"></i></button>
        <button class="da-slider-btn next" aria-label="Next"><i class="fa fa-chevron-right"></i></button>
        <div class="da-ticker-scroll da-auto-slider" id="featured-scroll-container">
          <?php foreach ($posts as $post): ?>
            <a href="<?php echo $base; ?>post/<?php echo urlencode($post['slug']); ?>" class="da-ticker-item">
              <div class="da-ticker-item-img">
                <?php if (!empty($post['featured_image'])): ?>
                  <img src="<?php echo htmlspecialchars(getAbsoluteImagePath($post['featured_image'], $base)); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" loading="lazy" />
                <?php else: ?>
                  <div class="da-img-fallback-icon"><i class="fa fa-newspaper" style="font-size:20px;"></i></div>
                <?php endif; ?>
              </div>
              <div class="da-ticker-item-body">
                <div class="da-ticker-item-title"><?php echo htmlspecialchars($post['title']); ?></div>
              </div>
            </a>
          <?php endforeach; ?>
          <div id="featured-load-more" style="display:<?php echo count($posts) >= 20 ? 'flex' : 'none'; ?>;align-items:center;justify-content:center;min-width:80px;">
            <div class="spinner-border spinner-border-sm text-secondary" role="status"><span class="visually-hidden">Loading...</span></div>
          </div>
        </div>
      </div>
    </section>