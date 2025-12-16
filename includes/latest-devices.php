<style>


.swiper-container .scroller-phones a {
    width: 100%;
    display: block;
    justify-content: flex-start;
    text-align: center;
    gap:12px
}

.swiper-container .scroller-phones a span {
    font-weight: 600;
}
/* MOBILE IMAGES */
.scroller-phones a img {
      display: block;
    margin: 0 5px 0 0;
    position: relative;
    width: 100%;
    margin: 0 auto;
}

#instores-container .swiper-slide {
    display: flex;
    flex-direction: row;
    /* justify-content: space-between; */
    padding: 0;
    margin: 0;
}
.scroller-phones a img {
    width: 100%;
    height: 140px;        /* perfect height, no stretch */
    object-fit: contain;
    margin: 0;
    padding: 0;
}
.swiper-half-slide {
    width: auto;       /* 2 devices per slide */
    padding: 0;
    margin: 0;
    display: flex;
    justify-content: center;
}
.scroller-phones {
    margin: 0 !important;
    padding: 0 !important;
}

</style>


<h4 class="section-heading">Latest Devices</h4>

    <?php if (empty($devices)): ?>

        <div class="text-center py-5">
            <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No Devices Available</h4>
            <p class="text-muted">Check back later for new devices!</p>
        </div>

    <?php else: ?>

        <!-- DESKTOP VIEW -->
        <div class="d-none d-lg-flex"
            style="overflow-y: auto; max-height:390px; width:320px; flex-wrap:wrap; overflow-x:hidden;">

            <?php foreach ($devices as $device): ?>
                <a href="device.php?id=<?php echo $device['id']; ?>" class="module-phones-link" style="    display: flex;
    align-items: center;
    justify-content: flex-start;    flex-direction: column;" >
                    <img style="position:relative;left:0;" src="<?php echo htmlspecialchars($device['image']); ?>" alt="">
                    <?php echo htmlspecialchars($device['name']); ?>
                </a>
            <?php endforeach; ?>

        </div>

        <!-- MOBILE SWIPER VIEW -->
        <div id="instores-container"
            class="d-flex d-lg-none swiper-container material-card swiper-container-horizontal">

    <?php foreach ($devices as $device): ?>
            <div class="swiper-half-slide swiper-slide" style="width:auto;">
                <div class="scroller-phones">
        <a href="device.php?id=<?php echo $device['id']; ?>" 
           class="module-phones-link">

            <img style="width:100%;" src="<?php echo htmlspecialchars($device['image']); ?>" alt="">
            <span style="width:75%;"><?php echo htmlspecialchars($device['name']); ?></span>

        </a>
        </div>
            </div>
    <?php endforeach; ?>



            <div class="swiper-scrollbar"></div>
        </div>

    <?php endif; ?>
    