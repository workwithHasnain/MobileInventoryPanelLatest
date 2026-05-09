<section class="da-instore-section" aria-label="In Stores Now">
      <div class="da-instore-inner">
        <div class="da-instore-header">
          <div>
            <div class="da-section-label"><span>Devices</span></div>
            <h2 class="da-section-title">In Stores Now</h2>
          </div>
          <a href="<?php echo $base; ?>brands" class="da-view-all">Browse All <i class="fa fa-arrow-right"></i></a>
        </div>
        <div class="da-slider-wrap">
          <button class="da-slider-btn prev" aria-label="Previous"><i class="fa fa-chevron-left"></i></button>
          <button class="da-slider-btn next" aria-label="Next"><i class="fa fa-chevron-right"></i></button>
          <div class="da-instore-scroll da-auto-slider" id="da-instore-scroll">
            <?php if (empty($latestDevices)): ?>
              <div class="da-empty"><i class="fa fa-mobile-alt"></i>No devices.</div>
            <?php else: ?>
              <?php foreach ($latestDevices as $device): ?>
                <a href="<?php echo $base; ?>device/<?php echo urlencode($device['slug']); ?>" class="da-device-card">
                  <img src="<?php echo htmlspecialchars(getAbsoluteImagePath($device['image'], $base)); ?>" alt="<?php echo htmlspecialchars($device['name']); ?>" loading="lazy" />
                  <div class="da-device-card-name"><?php echo htmlspecialchars($device['name']); ?></div>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>