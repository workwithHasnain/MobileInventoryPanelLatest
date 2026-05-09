<?php
try {
  $stmt = $pdo->prepare("SELECT p.*,b.name as brand_name,COUNT(dc.id) as review_count FROM phones p LEFT JOIN brands b ON p.brand_id=b.id LEFT JOIN device_comments dc ON CAST(p.id AS VARCHAR)=dc.device_id AND dc.status='approved' GROUP BY p.id,b.name ORDER BY review_count DESC LIMIT 10");
  $stmt->execute();
  $topReviewedDevices = $stmt->fetchAll();
} catch (Exception $e) {
  $topReviewedDevices = [];
}
?>

<div class="da-widget">
          <div class="da-widget-header">
            <h3>Top 10 by Fans</h3>
            <div class="da-widget-icon da-widget-icon-blue"><i class="fa fa-star"></i></div>
          </div>
          <div class="da-widget-body">
            <?php if (empty($topReviewedDevices)): ?>
              <div class="da-empty">Not enough data yet.</div>
            <?php else: ?>
              <div class="da-leaderboard">
                <?php foreach ($topReviewedDevices as $i => $device): ?>
                  <a href="<?php echo $base; ?>device/<?php echo urlencode($device['slug'] ?? $device['id']); ?>" class="da-leaderboard-row<?php echo $i < 3 ? ' top3' : ''; ?>">
                    <div class="rank <?php echo $i < 5 ? 'rank-up' : 'rank-down'; ?>">
                      <i class="fa fa-arrow-<?php echo $i < 5 ? 'up' : 'down'; ?>"></i>
                    </div>
                    <div class="device-name"><?php echo htmlspecialchars($device['brand_name'] . ' ' . $device['name']); ?></div>
                    <div class="count-badge"><?php echo $device['review_count']; ?></div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>