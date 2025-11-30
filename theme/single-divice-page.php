<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GSMArena Single Device Page</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
    crossorigin="anonymous"></script>

  <!-- Font Awesome (for icons) -->
  <script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <script>

  </script>

  <link rel="stylesheet" href="style.css">
</head>

<body style="background-color: #EFEBE9; overflow-x: hidden;">
  <!-- Desktop Navbar of Gsmarecn -->
  <div class="main-wrapper">
    <!-- Top Navbar -->
    <nav class="navbar navbar-dark  d-lg-inline d-none" id="navbar">
      <div class="container const d-flex align-items-center justify-content-between">
        <button class="navbar-toggler mb-2" type="button" onclick="toggleMenu()">
          <img style="height: 40px;"
            src="https://cdn.prod.website-files.com/67f21c9d62aa4c4c685a7277/684091b39228b431a556d811_download-removebg-preview.png"
            alt="">
        </button>

        <a class="navbar-brand d-flex align-items-center" href="#">
          <img src="imges/download.png" alt="GSMArena Logo" />
        </a>

        <div class="controvecy mb-2">
          <div class="icon-container">
            <button type="button" class="btn border-right" data-bs-toggle="tooltip" data-bs-placement="left"
              title="YouTube">
              <img src="iccons/youtube-color-svgrepo-com.svg" alt="YouTube" width="30px">
            </button>

            <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left" title="Instagram">
              <img src="iccons/instagram-color-svgrepo-com.svg" alt="Instagram" width="22px">
            </button>

            <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left" title="WiFi">
              <i class="fa-solid fa-wifi fa-lg" style="color: #ffffff;"></i>
            </button>

            <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left" title="Car">
              <i class="fa-solid fa-car fa-lg" style="color: #ffffff;"></i>
            </button>

            <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left" title="Cart">
              <i class="fa-solid fa-cart-shopping fa-lg" style="color: #ffffff;"></i>
            </button>
          </div>
        </div>

        <form action="" class="central d-flex align-items-center">
          <input type="text" class="no-focus-border" placeholder="Search">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" height="24" width="24" class="ms-2">
            <path fill="#ffffff"
              d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z" />
          </svg>
        </form>

        <div>
          <button type="button" class="btn mb-2" data-bs-toggle="tooltip" data-bs-placement="left" title="Login">
            <i class="fa-solid fa-right-to-bracket fa-lg" style="color: #ffffff;"></i>
          </button>

          <button type="button" class="btn mb-2" data-bs-toggle="tooltip" data-bs-placement="left" title="Register">
            <i class="fa-solid fa-user-plus fa-lg" style="color: #ffffff;"></i>
          </button>
        </div>
      </div>

    </nav>

  </div>
  <!-- Mobile Navbar of Gsmarecn -->
  <nav id="navbar" class="mobile-navbar d-lg-none d-flex justify-content-between  align-items-center">

    <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu"
      aria-controls="mobileMenu" aria-expanded="false" aria-label="Toggle navigation">
      <img style="height: 40px;"
        src="https://cdn.prod.website-files.com/67f21c9d62aa4c4c685a7277/684091b39228b431a556d811_download-removebg-preview.png"
        alt="">
    </button>
    <a class="navbar-brand d-flex align-items-center" href="#">
      <a class="logo text-white " href="#">GSMArena</a>
    </a>
    <div class="d-flex justify-content-end">
      <button type="button" class="btn float-end ml-5" data-bs-toggle="tooltip" data-bs-placement="left">
        <i class="fa-solid fa-right-to-bracket fa-lg" style="color: #ffffff;"></i>
      </button>
      <button type="button" class="btn float-end " data-bs-toggle="tooltip" data-bs-placement="left">
        <i class="fa-solid fa-user-plus fa-lg" style="color: #ffffff;"></i>
      </button>
    </div>
  </nav>
  <!-- Mobile Collapse of Gsmarecn -->
  <div class="collapse mobile-menu d-lg-none" id="mobileMenu">
    <div class="menu-icons">
      <i class="fas fa-home"></i>
      <i class="fab fa-facebook-f"></i>
      <i class="fab fa-instagram"></i>
      <i class="fab fa-tiktok"></i>
      <i class="fas fa-share-alt"></i>
    </div>
    <div class="column">
      <a href="index.html">Home</a>
      <a href="news.html">News</a>
      <a href="rewies.html">Reviews</a>
      <a href="videos.html">Videos</a>
      <a href="featured.html">Featured</a>
      <a href="phonefinder.html">Phone Finder</a>
      <a href="compare.html">Compare</a>
      <a href="#">Coverage</a>
      <a href="contact">Contact Us</a>
      <a href="#">Merch</a>
      <a href="#">Tip Us</a>
      <a href="#">Privacy</a>
    </div>
    <div class="brand-grid">
      <a href="#">Samsung</a>
      <a href="#">Xiaomi</a>
      <a href="#">OnePlus</a>
      <a href="#">Google</a>
      <a href="#">Apple</a>
      <a href="#">Sony</a>
      <a href="#">Motorola</a>
      <a href="#">Vivo</a>
      <a href="#">Huawei</a>
      <a href="#">Honor</a>
      <a href="#">Oppo</a>
      <a href="#">[...]</a>
    </div>
    <div class="menu-buttons d-flex justify-content-center ">
      <button class="btn btn-danger w-50">📱 Phone Finder</button>
      <button class="btn btn-primary w-50">📲 My Phone</button>
    </div>
  </div>
  <!-- Display Menu of Gsmarecn -->
  <div id="leftMenu" class="container show">
    <div class="row">
      <div class="col-12 d-flex align-items-center   colums-gap">
        <a href="index.html" class="nav-link">Home</a>
        <a href="compare.html" class="nav-link">Compare</a>
        <a href="videos.html" class="nav-link">Videos</a>
        <a href="rewies.html" class="nav-link ">Reviews</a>
        <a href="news.html" class="nav-link d-lg-block d-none">News</a>
        <a href="featured.html" class="nav-link d-lg-block d-none">Featured</a>
        <a href="phonefinder.html" class="nav-link d-lg-block d-none">Phone Finder</a>
        <a href="contact.html" class="nav-link d-lg-block d-none">Contact</a>
        <div style="background-color: #d50000; border-radius: 7px;" class="d-lg-none py-2"><svg
            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" height="16" width="16" class="mx-3">
            <path fill="#ffffff"
              d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z" />
          </svg></div>
      </div>
    </div>
  </div>
  <style>
    span {
      font-family: 'oswald';
      color: black;
      font-size: 12px;
      font-weight: 300;
    }

    .stat-item {
      align-items: center;
      height: 98px;
      width: 131px;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      display: flex;
      margin: 5px 0 12px;
      border-left: 1px solid #ccc;
      float: left;
      padding: 0px 10px;
      text-shadow: 1px 1px 0px hsla(0, 0%, 100%, .4) !important;

      position: relative;
      z-index: 1;
    }

    /* Desktop (side by side) */
    .specs-table tr {
      display: grid;
      grid-template-columns: 200px 1fr;
      /* left fixed, right flexible */
    }

    /* Mobile (stack) */
    @media (max-width: 768px) {
      .specs-table tr {
        grid-template-columns: 1fr;
        /* single column */
      }
    }

    .spec-title {
      font-weight: 400;
      font-family: 'oswald';
      font-size: 1.5rem;
      color: black;
    }

    .stat-item {
      /* padding: 24px; */
      border-left: 1px solid hsla(0, 0%, 100%, .5);
    }

    .spec-item {
      padding: 12px 20px;
      display: flex;
      row-gap: 8px;
      flex-direction: column;
      align-items: baseline;
      justify-content: space-around;
    }

    .stat-item :nth-child(1) {
      font-size: 1.6rem;
      font-weight: 600;
      text-shadow: 1px 1px 1px rgba(0, 0, 0, .4);
    }

    .stat-item :nth-child(2) {
      font-family: 'oswald';
      text-shadow: 1px 1px 1px rgba(0, 0, 0, .4);
    }

    .bg-blur {
      position: relative;
      z-index: 1;
    }

    .bg-blur::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      /* background: linear-gradient(135deg, rgba(107, 115, 255, 0.7), rgba(0, 13, 255, 0.7)); */
      filter: blur(8px);
      z-index: 0;
      border-radius: 8px;
    }

    .bg-blur>* {
      position: relative;
      z-index: 2;
    }


    .spec-subtitle {
      font-family: 'oswald';
      font-weight: 100;
      font-size: 14px;
      color: black;
    }


    .card-header {
      position: relative;
      overflow: hidden;
    }

    .card-header::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: inherit;
      /* same background lega */
      filter: blur(5px);
      z-index: 1;
    }

    .card-header * {
      position: relative;
      z-index: 2;
      /* content clear dikhayega */
    }

    .vr-hide {
      float: left;
      padding-left: 10px;
      font: 300 28px / 47px Google-Oswald, Arial, sans-serif;
      text-shadow: none;
      color: #fff;
      margin-bottom: 0px;
      margin-top: -2px;
    }

    .phone-image:after {
      content: "";
      position: absolute;
      top: 0;
      left: 165px;
      width: 229px;
      height: 100%;
      background: linear-gradient(90deg, #fff 0%, #fcfeff 2%, rgba(125, 185, 232, 0));
      z-index: 1;
    }
    
    .phone-image {
     margin-left: 5px;
    display: block;
    height: -webkit-fill-available;
    width: 165px;
    position: relative;
    z-index: 2;
    background: url("https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-s24-ultra-5g-sm-s928-stylus.jpg");
    /* background: #fff; */
    background-position: right;
    background-color: #fff;
    background-size: contain;
    background-repeat: no-repeat;
}

    tr {
      background-color: white;
      margin-bottom: 10px;
    }

    table td,
    table th {
      vertical-align: top;
      padding: 8px 12px;
      font-family: Arial, sans-serif;
      font-size: 14px;
      line-height: 1.5;
    }

    table tbody tr {
      background-color: white;
      border-bottom: 1px solid #ddd;
    }

    table tbody tr:last-child {
      border-bottom: none;
    }

    .spec-label {
      width: 120px;
      color: #d50000;
      font-weight: 400;
      text-transform: uppercase;
    }

    td strong {
      display: inline-block;

      width: 90px;
      font-weight: 600;
    }
  </style>


  <div class="d-lg-none d-block">
    <div class="card" role="region" aria-label="Vivo V60 Phone Info">

      <div class="article-info">
        <div class="bg-blur">
          <p class="vr-hide"
            style=" font-family: 'oswald'; text-transform: capitalize; text-shadow: 1px 1px 2px rgba(0, 0, 0, .4);">
            vivo V60
          </p>
          <svg class="float-end mx-3 mt-1" xmlns="http://www.w3.org/2000/svg" height="34" width="34"
            viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
            <path fill="#ffffff"
              d="M448 256C501 256 544 213 544 160C544 107 501 64 448 64C395 64 352 107 352 160C352 165.4 352.5 170.8 353.3 176L223.6 248.1C206.7 233.1 184.4 224 160 224C107 224 64 267 64 320C64 373 107 416 160 416C184.4 416 206.6 406.9 223.6 391.9L353.3 464C352.4 469.2 352 474.5 352 480C352 533 395 576 448 576C501 576 544 533 544 480C544 427 501 384 448 384C423.6 384 401.4 393.1 384.4 408.1L254.7 336C255.6 330.8 256 325.5 256 320C256 314.5 255.5 309.2 254.7 304L384.4 231.9C401.3 246.9 423.6 256 448 256z" />
          </svg>
        </div>
      </div>
      <div class="d-lg-flex  d-block" style="align-items: flex-start; ">

        <!-- Left: Phone Image -->
         <div style=" 
         height: 200px;
    height: -webkit-fill-available;
    background: white;
   padding-top: 5px;    
">
            <!-- Left: Phone Image -->
            <div class="phone-image me-3 pt-2 px-2" style="height: 200px;"></div>
</div>

        <!-- Right: Details + Stats + Specs -->
        <div class="flex-grow-1 position-relative" style="z-index: 100;">

          <!-- Phone Details + Stats -->
          <div class="d-flex justify-content-between mb-3">

            <ul class="phone-details d-lg-block d-none list-unstyled mb-0">
              <li><span>📅 Released 2025, August 19</span></li>
              <li><span>⚖️ 192g or 201g, 7.5mm thickness</span></li>
              <li><span>🆔 Android 15, up to 4 major upgrades</span></li>
              <li><span>💾 128GB/256GB/512GB storage, no card slot</span></li>
            </ul>

            <div class="d-flex stats-bar text-center">
              <div class="stat-item">
                <div>53%</div>
                <div class="stat-label">605,568 HITS</div>
              </div>
              <div class="stat-item">
                <div> <i class="fa-solid fa-heart fa-md" style="color: #ffffff;"></i> 80</div>
                <div class="stat-label">BECOME A FAN</div>
              </div>
            </div>
          </div>

          <!-- Specs Row (aligned with image) -->
           <div class="row text-center d-block g-0  pt-2 specs-bar">
                <div class="col-3 spec-item">
                  <img src="imges/vrer.png" style="width: 25px;" alt="">

                  <div class="spec-title"> 6.77"</div>
                  <div class="spec-subtitle">1080x2392 px</div>
                </div>
                <div class="col-3 spec-item border-start">
                  <img src="imges/bett-removebg-preview.png" style="width: 35px;" alt="">

                  <div class="spec-title">50MP</div>
                  <div class="spec-subtitle">2160p</div>
                </div>
                <div class="col-3 spec-item border-start">
                  <img src="imges/encypt-removebg-preview.png" style="width: 38px;" alt="">

                  <div class="spec-title">8-16GB</div>
                  <div class="spec-subtitle">Snapdragon 7</div>
                </div>
                <div class="col-3 spec-item border-start">
                  <img src="imges/lowtry-removebg-preview.png" style="width: 35px;" alt="">

                  <div class="spec-title">6500mAh</div>
                  <div class="spec-subtitle">90W</div>
                </div>
              </div>

        </div>
      </div>
      <div class="article-info">
        <div class="bg-blur">
          <div class="d-lg-none d-block justify-content-end">
            <div class="d-flex flexiable mt-2">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px" class="mt-2">Review (17)
              </h5>
            </div>
            <div class="d-flex flexiable mt-2">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">OPINION </h5>
            </div>
            <div class="d-flex flexiable mt-2">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">COMPARE </h5>
            </div>
            <div class="d-flex flexiable mt-2">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">PICTURES </h5>
            </div>
            <div class="d-flex flexiable mt-2">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16 px;" class="mt-2">PRICES</h5>
            </div>
          </div>


        </div>
      </div>

    </div>
  </div>
  <div class="container d-md-block d-none">
    <div class="row">
      <div class="article-info">
        <div class="bg-blur">
          <div class="d-block d-md-none justify-content-end">
            <div class="d-flex flexiable ">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px" class="mt-2">Review (17)
              </h5>
            </div>
            <div class="d-flex flexiable ">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">OPINION </h5>
            </div>
            <div class="d-flex flexiable ">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">COMPARE </h5>
            </div>
            <div class="d-flex flexiable ">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">PICTURES </h5>
            </div>
            <div class="d-flex flexiable ">
              <img src="/imges/download-removebg-preview.png" alt="">
              <h5 style="font-family:'oswald' ; font-size: 16 px;" class="mt-2">PRICES</h5>
            </div>
          </div>


        </div>
      </div>

    </div>
  </div>
  <div class="container  d-lg-block d-none support content-wrapper" id="Top"
    style=" margin-top: 2rem; padding-left: 0;">
    <div class="row">
      <div class="col-md-8 ">
        <div class="card" role="region" aria-label="Vivo V60 Phone Info">

          <div class="article-info">
            <div class="bg-blur">
              <p class="vr-hide"
                style=" font-family: 'oswald'; text-transform: capitalize; text-shadow: 1px 1px 2px rgba(0, 0, 0, .4);">
                vivo V60
              </p>
              <svg class="float-end mx-3 mt-1" xmlns="http://www.w3.org/2000/svg" height="34" width="34"
                viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
                <path fill="#ffffff"
                  d="M448 256C501 256 544 213 544 160C544 107 501 64 448 64C395 64 352 107 352 160C352 165.4 352.5 170.8 353.3 176L223.6 248.1C206.7 233.1 184.4 224 160 224C107 224 64 267 64 320C64 373 107 416 160 416C184.4 416 206.6 406.9 223.6 391.9L353.3 464C352.4 469.2 352 474.5 352 480C352 533 395 576 448 576C501 576 544 533 544 480C544 427 501 384 448 384C423.6 384 401.4 393.1 384.4 408.1L254.7 336C255.6 330.8 256 325.5 256 320C256 314.5 255.5 309.2 254.7 304L384.4 231.9C401.3 246.9 423.6 256 448 256z" />
              </svg>
            </div>
          </div>
          <div class="d-flex" style="align-items: flex-start;">

            <!-- Left: Phone Image -->
             <div style="
    height: -webkit-fill-available;
    background: white;
">
            <!-- Left: Phone Image -->
            <div class="phone-image me-3 pt-2 px-2"></div>
</div>
          
            <!-- Right: Details + Stats + Specs -->
            <div class="flex-grow-1 position-relative" style="z-index: 100;">

              <!-- Phone Details + Stats -->
              <div class="d-flex justify-content-between">
                <ul class="phone-details list-unstyled mb-0 d-lg-block d-none">
                  <li><span>📅 Released 2025, August 19</span></li>
                  <li><span>⚖️ 192g or 201g, 7.5mm thickness</span></li>
                  <li><span>🆔 Android 15, up to 4 major upgrades</span></li>
                  <li><span>💾 128GB/256GB/512GB storage, no card slot</span></li>
                </ul>

                <div class="d-flex stats-bar text-center">
                  <div class="stat-item">
                    <div>53%</div>
                    <div class="stat-label">605,568 HITS</div>
                  </div>
                  <div class="stat-item">
                    <div> <i class="fa-solid fa-heart fa-md" style="color: #ffffff;"></i> 80</div>
                    <div class="stat-label">BECOME A FAN</div>
                  </div>
                </div>
              </div>

              <!-- Specs Row (aligned with image) -->
              <div class="row text-center g-0  pt-2 specs-bar">
                <div class="col-3 spec-item">
                  <img src="imges/vrer.png" style="width: 25px;" alt="">

                  <div class="spec-title"> 6.77"</div>
                  <div class="spec-subtitle">1080x2392 px</div>
                </div>
                <div class="col-3 spec-item border-start">
                  <img src="imges/bett-removebg-preview.png" style="width: 35px;" alt="">

                  <div class="spec-title">50MP</div>
                  <div class="spec-subtitle">2160p</div>
                </div>
                <div class="col-3 spec-item border-start">
                  <img src="imges/encypt-removebg-preview.png" style="width: 38px;" alt="">

                  <div class="spec-title">8-16GB</div>
                  <div class="spec-subtitle">Snapdragon 7</div>
                </div>
                <div class="col-3 spec-item border-start">
                  <img src="imges/lowtry-removebg-preview.png" style="width: 35px;" alt="">

                  <div class="spec-title">6500mAh</div>
                  <div class="spec-subtitle">90W</div>
                </div>
              </div>

            </div>
          </div>
          <div class="article-info">
            <div class="bg-blur">
              <div class="d-flex justify-content-end">
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:'oswald' ; font-size: 16px" class="mt-2">Review (17)
                  </h5>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">OPINION </h5>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">COMPARE </h5>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:'oswald' ; font-size: 16px;" class="mt-2">PICTURES </h5>
                </div>
                <div class="d-flex flexiable ">
                  <img src="/imges/download-removebg-preview.png" alt="">
                  <h5 style="font-family:'oswald' ; font-size: 16 px;" class="mt-2">PRICES</h5>
                </div>
              </div>


            </div>
          </div>

        </div>


      </div>
        <div class="col-md-4 col-5 d-none d-lg-block">
                <button class="solid w-100 py-2">
                    <i class="fa-solid fa-mobile fa-sm mx-2" style="color: white;"></i>
                    Phone Finder</button>
                <div class="devor">
                    <button>Samsung</button>
                    <button>Xiaomi</button>
                    <button >Asus</button>
                    <button>Infinix</button>
                    <button>Apple</button>
                    <button >Google</button>
                    <button >AlCatel</button>
                    <button >Ulefone</button>
                    <button >Huawei</button>
                    <button >Honor</button>
                    <button >Zte</button>
                    <button >Tecno</button>
                    <button >Nokia</button>
                    <button >Oppo</button>
                    <button >Microsoft </button>
                    <button >Dooge</button>
                    <button >Sony</button>
                    <button >Realme</button>
                    <button >Unidegi</button>
                    <button >Blackview</button>
                    <button >Lg </button>
                    <button >OnePlus</button>
                    <button >Coolpad</button>
                    <button >Cubot</button>
                    <button >HTc</button>
                    <button >Nothing</button>
                    <button >Oscal</button>
                    <button >oukitel</button>
                    <button >Motrola</button>
                    <button >Vivo</button>
                    <button >Shrap</button>
                    <button >Itel</button>
                    <button >Lenovo</button>
                    <button >meizu</button>
                    <button >Micromax</button>
                    <button >Tcl</button>
                </div>
                <div class="d-flex">

                    <button class="solid w-50 py-2">
                        <i class="fa-solid 
                        fa-bars fa-sm mx-2"></i> All Brands</button>
                    <button class="solid w-50 py-2" style="/* width: 177px; */">
                        <i class="fa-solid fa-volume-high fa-sm mx-2"></i>
                        RUMORS MILL</button>

                </div>
            </div>
    </div>

  </div>

  <div class="container my-2" style="
    padding-left: 0;
    padding-right: -2px;">
    <div class="row">
      <div class="col-lg-8 col-md-7 order-2 order-md-1">
        <div class="bg-white">


          <table class="table fora  t">
            <tbody>
              <tr>
                <th class="spec-label">NETWORK</th>
                <td><strong>Technology</strong> GSM / HSPA / LTE / 5G</td>
              </tr>
              <tr>
                <th class="spec-label">LAUNCH</th>
                <br>
                <td>
                  <strong>Announced</strong> 2025, August 12<br>
                  <strong>Status</strong> Available. Released 2025, August 19
                </td>
              </tr>
              <tr>
                <th class="spec-label">DISPLAY</th>
                <td>
                  <strong>Type</strong> AMOLED, 1B colors, HDR10+, 120Hz, 1500 nits (HBM), 5000 nits (peak)<br>
                  <strong>Size</strong> 6.77 inches, 110.9 cm<sup>2</sup> (~88.1% screen-to-body ratio)<br>
                  <strong>Resolution</strong> 1080 x 2392 pixels (~388 ppi density)<br>
                  <strong>Protection</strong> Schott Xensation Core
                </td>
              </tr>
              <tr>
                <th class="spec-label">PLATFORM</th>
                <td>
                  <strong>OS</strong> Android 15, up to 4 major Android upgrades, Funtouch 15<br>
                  <strong>Chipset</strong> Qualcomm SM7750-AB Snapdragon 7 Gen 4 (4 nm)<br>
                  <strong>CPU</strong> Octa-core (1x2.8 GHz Cortex-720 & 4x2.4 GHz Cortex-720 & 3x1.8 GHz
                  Cortex-520)<br>
                  <strong>GPU</strong> Adreno 722
                </td>
              </tr>
              <tr>
                <th class="spec-label">MEMORY</th>
                <td>
                  <strong>Card slot</strong> No<br>
                  <strong>Internal</strong> 128GB 8GB RAM, 256GB 8GB RAM, 256GB 12GB RAM, 512GB 12GB RAM, 512GB 16GB
                  RAM<br>
                  UFS 2.2
                </td>
              </tr>
              <tr>
                <th class="spec-label">MAIN CAMERA</th>
                <td>
                  <strong>Triple</strong><br>
                  50 MP, f/1.9, 23mm (wide), 1/1.56", 1.0µm, PDAF, OIS<br>
                  50 MP, f/2.7, 73mm (periscope telephoto), 1/1.95", 0.8µm, PDAF, OIS, 3x optical zoom<br>
                  8 MP, f/2.0, 15mm, 120° (ultrawide)<br>
                  <strong>Features</strong> Zeiss optics, Ring-LED flash, panorama, HDR<br>
                  <strong>Video</strong> 4K@30fps, 1080p@30fps, gyro-EIS, OIS
                </td>
              </tr>
              <tr>
                <th class="spec-label">SELFIE CAMERA</th>
                <td>
                  <strong>Single</strong> 50 MP, f/2.2, 21mm (wide), 1/2.76", 0.64µm, AF<br>
                  <strong>Features</strong> Zeiss optics, HDR<br>
                  <strong>Video</strong> 4K@30fps, 1080p@30fps
                </td>
              </tr>
              <tr>
                <th class="spec-label">SOUND</th>
                <td>
                  <strong>Loudspeaker</strong> Yes, with stereo speakers<br>
                  <!-- <strong>3.5mm jack</strong> No -->
                </td>
              </tr>
              <tr>
                <th class="spec-label">COMS</th>
                <td>
                  <strong>WLAN</strong> Wi-Fi 802.11 a/b/g/n/ac, dual-band<br>
                  <strong>bluetooth</strong>5.4, A2DP, LE<br>
                  <strong>Positioning </strong>GPS, GALILEO, GLONASS, QZSS, BDS, NavIC<br>
                  <strong>NFC </strong>Yes<br>

                  <strong>Radio </strong>No<br>
                  <strong>USB </strong>USB Type-C 2.0, OTG<br>
                </td>
              </tr>
              <tr>
                <th class="spec-label">TESTS</th>
                <td> <STRong>loudspeaker</STRong> -24.7 LUFS (Very good)</td> <br>
                <!-- <STRong>3.5mm jack</STRong>No -->
              </tr>
              <tr>
                <th class="spec-label">SELFIE CAMERA</th>
                <td> <STRong>Single</STRong> 50 MP, f/2.2, 21mm (wide), 1/2.76", 0.64µm, AF</td>
                <!-- <STRong>Features</STRong> Zeiss optics, HDR <br> -->
                <!-- <STRong>Video</STRong> 4K@30fps, 1080p@30fps <br> -->
              </tr>
              <tr>
                <th class="spec-label">Battery</th>
                <td> <strong>Type</strong> Si/C Li-Ion 6500 mAh</td>
              </tr>
            </tbody>
          </table>

          <p style="font-size: 13px;
    text-transform: capitalize;
    padding: 6px 19px;"> <strong>Disclaimer</strong>. We can not guarantee that the information on this page is 100%
            correct. Read more</p>

          <div class="d-block d-lg-flex">
            <button class="pad">REVIEW</button> <button class="pad">OPINION</button> <button
              class="pad">COMPARE</button> <button class="pad">PICTURES</button> <button class="pad">PRICES</button>
          </div>
          <table class="pricing inline widget" style="    border: unset;
    border-left: 10px solid #17819f;">

            <caption class="d-flex">

              <h6 class="d-flex justify-content-between mx-3" style="font-size: 25px; font-weight: 600;">Pricing
                <span data-bs-toggle="tooltip" title="Info" class="text-muted" style="cursor:pointer;"> &nbsp;
                  &#9432;</span>
              </h6>
            </caption>
            <tbody>
              <tr>
                <td style="vertical-align: middle;">128GB 8GB RAM</td>
                <td><a>₹&thinsp;36,999</a><img alt="" style="height: 30px; margin-left: 12px;"
                    src="https://fdn.gsmarena.com/imgroot/static/stores/amazon-co-in1.png"></td>
              </tr>
              <tr>
                <td style="vertical-align: middle;">256GB 8GB RAM</td>

                <td><a>₹&thinsp;38,999</a><img alt="" style="    height: 30px; margin-left: 12px;"
                    src="https://fdn.gsmarena.com/imgroot/static/stores/amazon-co-in1.png"></td>

                <td></td>
              </tr>
              <tr>
                <td style="vertical-align: middle;">256GB 12GB RAM</td>

                <td><a>₹&thinsp;40,999</a><img alt="" style="    height: 30px; margin-left: 12px;"
                    src="https://fdn.gsmarena.com/imgroot/static/stores/amazon-co-in1.png"></td>

                <td></td>
              </tr>


            </tbody>

          </table>
          <div class="review-widget d-flex my-4">
            <img class="" src="https://fdn.gsmarena.com/imgroot/reviews/25/vivo-v60/-1220x526/gsmarena_001.jpg" alt="">
            <div class="side">
              <h3 class="text-white">vivo V60 review</h3>
              <p>
                The vivo V-series has long been about bringing premium-looking smartphones with a strong camera focus to
                the mid-range market, and the new V60 carries that mission forward. As the successor to... </p>
              <button class="red-button">READ</button>
            </div>
          </div>
          <div class="comments">
            <h5 class="border-bottom reader  py-3 mx-2">vivo V60 - user opinions and reviews</h5>
            <div class="first-user" style="background-color: #EDEEEE;">
              <div class="user-thread">
                <div class="uavatar">
                  <img src="https://www.gravatar.com/avatar/e029eb57250a4461ec444c00df28c33e?r=g&amp;s=50" alt="">
                </div>
                <ul class="uinfo2">

                  <li class="uname"><a href="" style="color: #555; text-decoration: none;">jiyen235</a>
                  </li>
                  <li class="ulocation">
                    <i class="fa-solid fa-location-dot fa-sm"></i>
                    <span title="Encoded anonymized location">XNA</span>
                  </li>
                  <li class="upost"> <i class="fa-regular fa-clock fa-sm mx-1"></i>7 hours ago</time></li>

                </ul>
                <p class="uopin">ofc it does, samsung sells phones in every price range</p>
                <ul class="uinfo">
                  <li class="ureply" style="list-style: none;">
                    <span title="Reply to this post">
                      <p href="">Reply</p>
                    </span>
                  </li>
                </ul>


              </div>
              <div class="user-thread">
                <div class="uavatar">
                  <img src="https://www.gravatar.com/avatar/e029eb57250a4461ec444c00df28c33e?r=g&amp;s=50" alt="">
                </div>
                <ul class="uinfo2">

                  <li class="uname"><a href="" style="color: #555; text-decoration: none;">jiyen235</a>
                  </li>
                  <li class="ulocation">
                    <i class="fa-solid fa-location-dot fa-sm"></i>
                    <span title="Encoded anonymized location">nyc</span>
                  </li>
                  <li class="upost"> <i class="fa-regular fa-clock fa-sm mx-1"></i>15 Minates ago</time>
                  </li>

                </ul>
                <p class="uopin">what's your point?</p>
                <ul class="uinfo">
                  <li class="ureply" style="list-style: none;">
                    <span title="Reply to this post">
                      <p href="">Reply</p>
                    </span>
                  </li>
                </ul>
              </div>
              <div class="user-thread">
                <div class="uavatar">
                  <span class="avatar-box">D</span>
                </div>
                <ul class="uinfo2">

                  <li class="uname"><a href="" style="color: #555; text-decoration: none;">jiyen235</a>
                  </li>
                  <li class="ulocation">
                    <i class="fa-solid fa-location-dot fa-sm"></i>
                    <span title="Encoded anonymized location">QNA</span>
                  </li>
                  <li class="upost"> <i class="fa-regular fa-clock fa-sm mx-1"></i>14 hours ago</time>
                  </li>

                </ul>
                <p class="uopin">There are other phone brands bro... Lower the fanboy speak a bit..</p>
                <ul class="uinfo">
                  <li class="ureply" style="list-style: none;">
                    <span title="Reply to this post">
                      <p href="">Reply</p>
                    </span>
                  </li>
                </ul>
              </div>
              <div class="button-secondary-div d-flex justify-content-between align-items-center ">
                <div class="d-flex">
                  <button class="button-links">read all optnions</button>
                  <button class="button-links">post your opinion</button>
                </div>
                <p class="div-last">Total reader comments: <b>34</b> </p>
              </div>
            </div>
          </div>

          <img  src="https://fdn.gsmarena.com/imgroot/static/banners/self/review-pixel-9-pro-xl-728x90.jpg" alt="" style="width: -webkit-fill-available;">
        </div>
      </div>

      <!-- Left Section -->
      <div class="col-lg-4 bg-white col-md-5 order-1 order-md-2">
        <div class="mb-4">
          <img style="width: 100%;"
            src="https://fdn.gsmarena.com/imgroot/static/banners/self/review-galaxy-a56-300x250.jpg" alt="vivo V60"
            class="img-fluid" />
          <h6
            style="border-left: solid 5px grey ; color: #555; text-transform: uppercase; font-weight: 900; margin-top: 12px;"
            class="px-3">Prices</h6>
          <div class="price-section">
            <h6 class="d-flex justify-content-between">vivo V60
              <span data-bs-toggle="tooltip" title="Info" class="text-muted" style="cursor:pointer;">&#9432;</span>
            </h6>
            <!-- Price items -->
            <div class="price-item">
              <div><strong style="font-size: 14px;">128GB 8GB RAM</strong></div>
              <div class="d-flex align-items-center mt-2 justify-content-between">
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg" alt="amazon"
                  class="amazon-logo" />
                <div class="price-amount">₹ 36,999</div>
              </div>
            </div>
            <div class="price-item">
              <div><strong style="font-size: 14px;">256GB 8GB RAM</strong></div>
              <div class="d-flex align-items-center mt-2 justify-content-between">
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg" alt="amazon"
                  class="amazon-logo" />
                <div class="price-amount">₹ 38,999</div>
              </div>
            </div>
            <div class="price-item">
              <div><strong style="font-size: 14px;">256GB 12GB RAM</strong></div>
              <div class="d-flex align-items-center mt-2  justify-content-between">
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg" alt="amazon"
                  class="amazon-logo" />
                <div class="price-amount">₹ 40,999</div>
              </div>
            </div>
            <button class="pad">SHOW ALL PRICES</button>
          </div>
          <h6
            style="border-left: solid 5px grey ; color: #555; text-transform: uppercase; font-weight: 900; margin-top: 12px;"
            class="px-3">VIVO V60 IN THE </h6>

          <div class="news d-flex mt-2">
            <img src="https://fdn.gsmarena.com/imgroot/news/25/08/vivo-v60-going-global/-184x111/gsmarena_000.jpg"
              alt="" style="width: 99px;">
            <h6 class="new-heading">
              vivo V60 launches internationally, starting with Malaysia, Taiwan and Vietnam26 <strong class="rs"> <i
                  class="fa-regular fa-clock fa-rotate-90 fa-sm" style="color: #444;"></i> Aug 2025</strong>
            </h6>
          </div>
          <div class="news d-flex mt-2">
            <img src="https://fdn.gsmarena.com/imgroot/news/25/08/vivo-t4-pro-official/-184x111/gsmarena_001.jpg" alt=""
              style="width: 99px;">
            <h6 class="new-heading">
              vivo V60 launches internationally, starting with Malaysia, Taiwan and Vietnam26 <strong class="rs"> <i
                  class="fa-regular fa-clock fa-rotate-90 fa-sm" style="color: #444;"></i>Aug 2025</strong>
            </h6>
          </div>
          <div class="news d-flex mt-2">
            <img src="https://fdn.gsmarena.com/imgroot/news/25/08/vivo-v60-lite-geekbench/-184x111/gsmarena_000.jpg"
              alt="" style="width: 99px;">
            <h6 class="new-heading">
              vivo V60 launches internationally, starting with Malaysia, Taiwan and Vietnam26 <strong class="rs"> <i
                  class="fa-regular fa-clock fa-rotate-90 fa-sm" style="color: #444;"></i> Aug 2025</strong>
            </h6>
          </div>
          <div class="news d-flex mt-2">
            <img
              src="https://fdn.gsmarena.com/imgroot/news/25/08/vivo-t4-pro-key-specs-launch-date/-184x111/gsmarena_001.jpg"
              alt="" style="width: 99px;">
            <h6 class="new-heading">
              vivo V60 launches internationally, starting with Malaysia, Taiwan and Vietnam26 <strong class="rs"> <i
                  class="fa-regular fa-clock fa-rotate-90 fa-sm" style="color: #444;"></i> Aug 2025</strong>
            </h6>
          </div>
          <h6 style="border-left: solid 5px grey ;text-transform: uppercase;" class=" fw-bold px-3 text-secondary mt-3">
            RELATED PHONES</h6>
          <div class="cent">

            <div class="d-flex">
              <div class="canel">
                <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/vivoiy300-gt.jpg" alt="">
                <p>Vivo y300 Gt</p>
              </div>
              <div class="canel mx-4">
                <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-m56-5g.jpg" alt="">
                <p>Sumsung Galaxy f56</p>
              </div>
              <div class="canel ">
                <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/vivo-x200-pro-mini.jpg" alt="">
                <p>Vivo x200 FE</p>
              </div>
            </div>
            <div class="d-flex">
              <div class="canel">
                <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/vivo-x-fold3.jpg" alt="">
                <p>Vivo X Fold5</p>
              </div>
              <div class="canel mx-4">
                <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/itel-a90.jpg" alt="">
                <p>Itel A90</p>
              </div>
              <div class="canel ">
                <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/oscal-pad-100.jpg" alt="">
                <p>OScal pad 100</p>
              </div>
            </div>
            <div class="d-flex">
              <div class="canel">
                <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/itel-city-100.jpg" alt="">
                <p>itel city 100</p>
              </div>
              <div class="canel mx-4">
                <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/motorola-edge-60-fusion.jpg" alt="">
                <p>Motorla Edge 60</p>
              </div>
              <div class="canel ">
                <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/sony-xperia-1-vi-red.jpg" alt="">
                <p>Song xperia -1 VII</p>
              </div>
            </div>
          </div>
          <h6 style="border-left: solid 5px grey ;text-transform: uppercase;" class=" fw-bold px-3 text-secondary mt-3">
            vivo v60 reviews</h6>
          <img src="https://fdn.gsmarena.com/imgroot/reviews/25/vivo-v60/-347x151/gsmarena_001.jpg" alt="">
          <div class="vivo-div">
            <strong>VIVO V60 REVIEW</strong>
          </div>

          <img style="width: 100%;"
            src="https://fdn.gsmarena.com/imgroot/static/banners/self/review-galaxy-s24-fe-300x250.jpg" alt="">

        </div>
      </div>

    </div>
  </div>
  <div id="bottom" class="container d-flex mt-3" style="max-width: 1034px;">
    <div class="row align-items-center">
      <div class="col-md-2 m-auto col-4 d-flex justify-content-center align-items-center "> <img
          src="https://fdn2.gsmarena.com/w/css/logo-gsmarena-com.png" alt="">
      </div>
      <div class="col-10 nav-wrap m-auto text-center ">
        <div class="nav-container">
          <a href="#">Home</a>
          <a href="#">News</a>
          <a href="#">Reviews</a>
          <a href="#">Compare</a>
          <a href="#">Coverage</a>
          <a href="#">Glossary</a>
          <a href="#">FAQ</a>
          <a href="#"> <i class="fa-solid fa-wifi fa-sm"></i> RSS</a>
          <a href="#"> <i class="fa-brands fa-youtube fa-sm"></i> YouTube</a>
          <a href="#"> <i class="fa-brands fa-instagram fa-sm"></i> Instagram</a>
          <a href="#"> <i class="fa-brands fa-tiktok fa-sm"></i>TikTok</a>
          <a href="#"> <i class="fa-brands fa-facebook-f fa-sm"></i> Facebook</a>
          <a href="#"> <i class="fa-brands fa-twitter fa-sm"></i>Twitter</a>
          <a href="#">© 2000-2025 GSMArena.com</a>
          <a href="#">Mobile version</a>
          <a href="#">Android app</a>
          <a href="#">Tools</a>
          <a href="contact.html">Contact us</a>
          <a href="#">Merch store</a>
          <a href="#">Privacy</a>
          <a href="#">Terms of use</a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>


<!-- Bootstrap JS Bundle (Popper + Bootstrap JS) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Enable tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
  })
</script>

<script src="script.js"></script>

</body>

</html>