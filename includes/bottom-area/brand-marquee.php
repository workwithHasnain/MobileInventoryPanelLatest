<section class="da-marquee-section" aria-label="All Brands">
      <div class="da-marquee-container">
        <div class="da-marquee-track">
          <!-- Original set -->
          <div class="da-marquee-content">
            <?php foreach ($brands as $brand):
              $brandSlug = strtolower(preg_replace('/\s+/', '-', trim($brand['name'])));
            ?>
              <a href="<?php echo $base; ?>brand/<?php echo urlencode($brandSlug); ?>" class="da-marquee-pill"><?php echo htmlspecialchars($brand['name']); ?></a>
            <?php endforeach; ?>
          </div>
          <!-- Duplicated set for seamless loop -->
          <div class="da-marquee-content" aria-hidden="true">
            <?php foreach ($brands as $brand):
              $brandSlug = strtolower(preg_replace('/\s+/', '-', trim($brand['name'])));
            ?>
              <a href="<?php echo $base; ?>brand/<?php echo urlencode($brandSlug); ?>" class="da-marquee-pill"><?php echo htmlspecialchars($brand['name']); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>