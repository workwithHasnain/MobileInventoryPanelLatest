<?php
  try {
    $stmt = $pdo->prepare("SELECT p.*,b.name as brand_name,COUNT(cv.id) as view_count FROM phones p LEFT JOIN brands b ON p.brand_id=b.id LEFT JOIN content_views cv ON CAST(p.id AS VARCHAR)=cv.content_id AND cv.content_type='device' GROUP BY p.id,b.name ORDER BY view_count DESC LIMIT 10");
    $stmt->execute();
    $topViewedDevices = $stmt->fetchAll();
  } catch (Exception $e) {
    $topViewedDevices = [];
  }
?>
<div class="da-widget">
          <div class="da-widget-header">
            <h3>Top 10 Daily Interest</h3>
            <div class="da-widget-icon da-widget-icon-red"><i class="fa fa-fire"></i></div>
          </div>
          <div class="da-widget-body">
            <?php if (empty($topViewedDevices)): ?>
              <div class="da-empty">Not enough data yet.</div>
            <?php else: ?>
              <div class="da-leaderboard">
                <?php foreach ($topViewedDevices as $i => $device): ?>
                  <a href="<?php echo $base; ?>device/<?php echo urlencode($device['slug'] ?? $device['id']); ?>" class="da-leaderboard-row<?php echo $i < 3 ? ' top3' : ''; ?>">
                    <div class="rank <?php echo $i < 5 ? 'rank-up' : 'rank-down'; ?>">
                      <i class="fa fa-arrow-<?php echo $i < 5 ? 'up' : 'down'; ?>"></i>
                    </div>
                    <div class="device-name"><?php echo htmlspecialchars($device['brand_name'] . ' ' . $device['name']); ?></div>
                    <div class="count-badge"><?php echo $device['view_count']; ?></div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>