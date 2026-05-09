<?php if (!empty($topComparisons)): ?>
      <section class="da-trending-section" aria-label="Trending Comparisons">
        <div class="da-post-feed-header da-trending-header">
          <div>
            <div class="da-section-label"><span>Compare</span></div>
            <h2 class="da-section-title">Trending Comparisons</h2>
          </div>
          <a href="<?php echo $base; ?>compare" class="da-view-all">Compare Tool <i class="fa fa-arrow-right"></i></a>
        </div>
        <div class="da-slider-wrap">
          <button class="da-slider-btn prev" aria-label="Previous"><i class="fa fa-chevron-left"></i></button>
          <button class="da-slider-btn next" aria-label="Next"><i class="fa fa-chevron-right"></i></button>
          <div class="da-trending-scroll da-auto-slider">
            <?php foreach ($topComparisons as $cmp):
              $s1 = $cmp['device1_slug'] ?? $cmp['device1_id'] ?? '';
              $s2 = $cmp['device2_slug'] ?? $cmp['device2_id'] ?? '';
              $cUrl = $base . 'compare/' . urlencode($s1) . '-vs-' . urlencode($s2);
              $n1 = htmlspecialchars($cmp['device1_name'] ?? 'Device 1');
              $n2 = htmlspecialchars($cmp['device2_name'] ?? 'Device 2');
            ?>
              <a href="<?php echo $cUrl; ?>" class="da-vs-card">
                <div class="da-vs-row">
                  <div class="da-vs-col">
                    <?php if (!empty($cmp['device1_image'])): ?><img src="<?php echo htmlspecialchars(getAbsoluteImagePath($cmp['device1_image'], $base)); ?>" alt="<?php echo $n1; ?>" class="da-vs-img" loading="lazy" /><?php endif; ?>
                    <div class="da-vs-device-name"><?php echo $n1; ?></div>
                  </div>
                  <div class="da-vs-divider">VS</div>
                  <div class="da-vs-col">
                    <?php if (!empty($cmp['device2_image'])): ?><img src="<?php echo htmlspecialchars(getAbsoluteImagePath($cmp['device2_image'], $base)); ?>" alt="<?php echo $n2; ?>" class="da-vs-img" loading="lazy" /><?php endif; ?>
                    <div class="da-vs-device-name"><?php echo $n2; ?></div>
                  </div>
                </div>
                <div class="da-vs-hint">Click to compare →</div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>