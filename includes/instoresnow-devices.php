<h4 class="section-heading">In stores now</h4>

<?php if (empty($latestDevices)): ?>

    <div class="text-center py-5">
        <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
        <h4 class="text-muted">No Devices Available</h4>
        <p class="text-muted">Check back later for new devices!</p>
    </div>

<?php else: ?>

    <!-- DESKTOP VIEW -->
    <div class="d-none d-lg-flex"
        style="overflow-y: auto; max-height:390px; width:100%; justify-content: center; flex-wrap:wrap; overflow-x:hidden; gap: 10px; padding: 0; margin: 0;">

        <?php foreach ($latestDevices as $device): ?>
            <a href="<?php echo $base; ?>device/<?php echo urlencode($device['slug']); ?>" class="module-phones-link">
                <img src="<?php echo htmlspecialchars(getAbsoluteImagePath($device['image'], $base)); ?>" alt="">
                <br><?php echo htmlspecialchars($device['name']); ?>
            </a>
        <?php endforeach; ?>

    </div>

    <!-- MOBILE SWIPER VIEW -->
    <div id="instores-container"
        class="d-block d-lg-none swiper-container material-card swiper-container-horizontal">

        <div class="scroller-phones swiper-wrapper">

            <?php
            // split into chunks of 2 items (because mobile shows 2 per slide)
            $mobileChunks = array_chunk($latestDevices, 2);
            ?>

            <?php foreach ($mobileChunks as $chunk): ?>
                <div class="swiper-slide">
                    <?php foreach ($chunk as $device): ?>
                        <div class="swiper-half-slide">
                            <a href="<?php echo $base; ?>device/<?php echo urlencode($device['slug']); ?>">
                                <span>
                                    <img src="<?php echo htmlspecialchars(getAbsoluteImagePath($device['image'], $base)); ?>"
                                        alt="<?php echo htmlspecialchars($device['name']); ?>">
                                </span>
                                <strong><?php echo htmlspecialchars($device['name']); ?></strong>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

        </div>

        <div class="swiper-scrollbar"></div>
    </div>

<?php endif; ?>
<style>
    a {
        outline: none;
        color: #212121;
        text-decoration: none;
    }

    .module {
        padding: 0;
        margin-bottom: 30px;
    }

    .module,
    a.module-phones-link {
        position: relative;
    }

    .section-heading {
        margin-top: 10px !important;
        margin: 0 0 0 -10px;
        width: calc(100% + 10px);
        background: #fff;
    }

    .section-heading {
        border-bottom: none;
        border-left: 10px solid;
        border-color: #d9d9d9;
        padding: 5px 10px;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
        margin-bottom: 0;
        margin-left: -10px;
        margin-top: 12px;
        width: 100%;
        font-size: 20px;
        color: #777;
        background: 0 0;
        font-weight: 700;
    }


    .module .section-heading {
        margin-top: 0 !important;
        margin: 0 0 0 -10px;
        width: calc(100% + 10px);
        background: #fff;
    }

    .sidebar .section-heading {
        border-bottom: none;
        border-left: 10px solid;
        border-color: #d9d9d9;
        padding: 5px 10px;
        margin-bottom: 0;
        margin-left: -10px;
        width: 100%;
        color: #777;
        background: 0 0;
    }

    .module-phones>h4~div {
        padding-top: 5px;
    }

    .module-latest div {
        scrollbar-width: thin;
        display: -ms-flexbox;
        display: flex;
        -ms-flex-wrap: wrap;
        flex-wrap: wrap;
        -ms-flex-pack: distribute;
        justify-content: space-around;
    }

    .module-latest .module-phones-link {
        margin: 0 7px;
    }

    .module,
    a.module-phones-link {
        position: relative;
    }

    .module a {
        font: 700 13px / 18px system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
        color: #444;
    }

    .module-phones-link {
        float: left;
        display: block;
        width: 80px;
        min-height: 182px;
        text-align: center;
        font: 700 13px system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
        color: #333;
        margin: 0 10px;
        border: none;
        position: relative;
    }

    .module a img {
        transition: transform 1s cubic-bezier(0.26, 0.695, 0.375, 0.965);
    }

    @supports (filter:brightness(0.953)) {
        .module-phones-link img {
            filter: brightness(0.953);
        }
    }

    .module-phones-link img {
        height: 119px;
        left: -5px;
        /* position: relative; */
        padding: 5px;
        z-index: 1;
        opacity: .953;
    }

    .swiper-container {
        margin-left: auto;
        margin-right: auto;
        position: relative;
        overflow: scroll;
        z-index: 1;
        background-color: var(--color-card-background);
        padding-bottom: 5px;
        padding-left: 10px;
        padding-top: 15px;
    }

    .material-card,
    .top-shadow {
        /* box-shadow: 0px 2px 4px var(--color-card-shadow, #cfcfcf); */
        background-color: var(--color-card-background);
        padding: 15px 0px;
        margin: 10px 0px;

    }

    .swiper-container-android .swiper-slide,
    .swiper-wrapper {
        -ms-transform: translateZ(0);
        transform: translateZ(0);
    }

    .swiper-wrapper {
        position: relative;
        width: 100%;
        height: 100%;
        z-index: 1;
        display: -ms-flexbox;
        display: flex;
        transition-property: transform;
        box-sizing: content-box;
    }


    .swiper-container .scroller-phones .swiper-slide {
        padding: 0 0 10px 0x;
        box-sizing: border-box;
        background: none;
        margin-top: 5px;
    }

    @media (orientation: landscape) {
        .swiper-slide {
            width: 42%;
        }
    }

    .swiper-slide {
        -ms-flex: 1 0 auto;
        flex: 1 0 auto;
        width: 85%;
        position: relative;
        display: -ms-inline-flexbox;
        display: inline-flex;
    }

    .swiper-half-slide {
        width: 50%;
        margin-right: 10px;
    }

    .swiper-container .scroller-phones a {
        width: 100%;
        display: block;
        float: left;
        text-align: center;
    }

    .swiper-container .scroller-phones a span {
        width: 100%;
        background: var(--color-card-background);
        display: block;
        margin-bottom: 5px;
    }

    .swiper-container .scroller-phones a img {
        display: block;
        margin: 0 5px 0 0;
        position: relative;
        margin: 0 auto;
        /* width: 120px;
            aspect-ratio: 160 / 212; */
    }

    .swiper-slide img {
        float: none;
    }


    .swiper-container .scroller-phones a strong {
        display: block;
        text-align: center;
        font-size: 15px;
        line-height: 1.5;
        white-space: normal;
    }

    .compare-checkbox input::placeholder {
        color: black;
        padding: 15px;
        font-size: 14px;
        padding-left: 3px;
    }
</style>