<div class="da-widget">
          <div class="da-widget-header">
            <h3>Popular Comparisons</h3>
            <div class="da-widget-icon"><i class="fa fa-scale-balanced"></i></div>
          </div>
          <div class="da-widget-body">
            <?php if (empty($topComparisons)): ?>
              <div class="da-empty">No comparisons yet.</div>
            <?php else: ?>
              <div class="da-comparison-list">
                <?php foreach (array_slice($topComparisons, 0, 5) as $i => $cmp):
                  $slug1 = $cmp['device1_slug'] ?? $cmp['device1_id'] ?? '';
                  $slug2 = $cmp['device2_slug'] ?? $cmp['device2_id'] ?? '';
                  $cUrl = $base . 'compare/' . urlencode($slug1) . '-vs-' . urlencode($slug2);
                  $n1 = htmlspecialchars($cmp['device1_name'] ?? 'Device');
                  $n2 = htmlspecialchars($cmp['device2_name'] ?? 'Device');
                ?>
                  <a href="<?php echo $cUrl; ?>" class="da-sidebar-vs-card">
                    <div style="text-align:center;margin-top:3px;">
                      <span class="count-badge da-badge-count">
                        <i class="fa fa-scale-balanced da-icon-blue"></i> compared <?php echo number_format($cmp['comparison_count']); ?> times
                      </span>
                    </div>
                    <div class="da-sidebar-vs-row">
                      <div class="da-vs-col">
                        <?php if (!empty($cmp['device1_image'])): ?><img src="<?php echo htmlspecialchars(getAbsoluteImagePath($cmp['device1_image'], $base)); ?>" alt="<?php echo $n1; ?>" class="da-sidebar-vs-img" loading="lazy" /><?php endif; ?>
                        <div class="da-sidebar-vs-name"><?php echo $n1; ?></div>
                      </div>
                      <div class="da-sidebar-vs-divider">VS</div>
                      <div class="da-vs-col">
                        <?php if (!empty($cmp['device2_image'])): ?><img src="<?php echo htmlspecialchars(getAbsoluteImagePath($cmp['device2_image'], $base)); ?>" alt="<?php echo $n2; ?>" class="da-sidebar-vs-img" loading="lazy" /><?php endif; ?>
                        <div class="da-sidebar-vs-name"><?php echo $n2; ?></div>
                      </div>
                    </div>
                    <div class="da-card-btn-wrap">
                      <button class="da-card-cta-btn" onclick="window.location.href='<?php echo $cUrl; ?>'; return false;">Compare Now →</button>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>