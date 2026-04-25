<div class="da-section-label"><span>Devices</span></div>
        <div class="da-widget">
          <div class="da-widget-header">
            <h3>Latest Devices</h3>
            <div class="da-widget-icon"><i class="fa fa-mobile-screen-button"></i></div>
          </div>
          <div class="da-widget-body">
            <?php if (empty($latestDevices)): ?>
              <div class="da-empty"><i class="fa fa-mobile-alt"></i>No devices yet.</div>
            <?php else: ?>
              <div class="da-device-list">
                <?php foreach (array_slice($latestDevices, 0, 8) as $device): ?>
                  <a href="<?php echo $base; ?>device/<?php echo urlencode($device['slug']); ?>" class="da-device-row">
                    <div class="da-device-img-wrapper">
                      <img src="<?php echo htmlspecialchars(getAbsoluteImagePath($device['image'] ?? '', $base)); ?>" alt="<?php echo htmlspecialchars($device['name']); ?>" loading="lazy" />
                    </div>
                    <div class="da-device-info">
                      <div class="da-device-name"><?php echo htmlspecialchars($device['name']); ?></div>
                      <div class="da-device-specs">
                        <?php if (!empty($device['display_size'])): ?>
                          <div class="da-device-spec-item"><i class="fa fa-mobile-screen"></i> <?php echo $device['display_size']; ?>"</div>
                        <?php endif; ?>
                        <?php if (!empty($device['ram'])): ?>
                          <div class="da-device-spec-item"><i class="fa fa-memory"></i> <?php echo $device['ram']; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($device['battery_capacity'])): ?>
                          <div class="da-device-spec-item"><i class="fa fa-battery-full"></i> <?php echo $device['battery_capacity']; ?> mAh</div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>