<div class="da-section-label"><span>Brands</span></div>
<div class="da-classic-brand-widget">
    <!-- Top header -->
    <div class="da-cbw-header">
    <a href="<?php echo $base; ?>phonefinder">
        <i class="fa fa-mobile-screen"></i> PHONE FINDER
    </a>
    </div>

    <!-- Brand Grid -->
    <div class="da-cbw-grid">
    <?php foreach (array_slice($brands, 0, 32) as $index => $brand):
        $brandSlug = strtolower(preg_replace('/\s+/', '-', trim($brand['name'])));
    ?>
        <a href="<?php echo $base; ?>brand/<?php echo urlencode($brandSlug); ?>" class="da-cbw-item" title="<?php echo htmlspecialchars($brand['name']); ?>">
        <?php echo strtoupper(htmlspecialchars($brand['name'])); ?>
        </a>
    <?php endforeach; ?>
    </div>

    <!-- Bottom buttons -->
    <div class="da-cbw-footer">
    <a href="<?php echo $base; ?>brands" class="da-cbw-btn left">
        <i class="fa fa-bars"></i> ALL BRANDS
    </a>
    <a href="<?php echo $base; ?>rumored" class="da-cbw-btn right">
        <i class="fa fa-bullhorn"></i> RUMORS MILL
    </a>
    </div>
</div>