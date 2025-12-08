<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GSMArena Rewies Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>
    <!-- Optional Bootstrap Icons (for the chat icon) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">


    <script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />


    <link rel="stylesheet" href="style.css">
</head>

<body style="background-color: #EFEBE9;">
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

                        <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left"
                            title="Instagram">
                            <img src="iccons/instagram-color-svgrepo-com.svg" alt="Instagram" width="22px">
                        </button>

                        <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left"
                            title="WiFi">
                            <i class="fa-solid fa-wifi fa-lg" style="color: #ffffff;"></i>
                        </button>

                        <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left" title="Car">
                            <i class="fa-solid fa-car fa-lg" style="color: #ffffff;"></i>
                        </button>

                        <button type="button" class="btn" data-bs-toggle="tooltip" data-bs-placement="left"
                            title="Cart">
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
                    <button type="button" class="btn mb-2" data-bs-toggle="tooltip" data-bs-placement="left"
                        title="Login">
                        <i class="fa-solid fa-right-to-bracket fa-lg" style="color: #ffffff;"></i>
                    </button>

                    <button type="button" class="btn mb-2" data-bs-toggle="tooltip" data-bs-placement="left"
                        title="Register">
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
    <div class="container support content-wrapper" id="Top">
        <div class="row">

            <div class="col-md-8 col-5  d-md-inline col-12 ">
                <div class="comfort-life-zone d-none d-lg-block position-absolute">
                    <img src="imges/Screenshot (160).png" alt="">
                    <div class="position-absolute d-flex mt-1" style="top: 0;">
                        <label class="text-white whitening ">Popular Tags</label>
                        <button class="mobiles-button">Featured</button>
                        <button class="mobiles-button">Android</button>
                        <button class="mobiles-button">Samsung</button>
                        <button class="mobiles-button">Nokia</button>
                        <button class="mobiles-button">Sony</button>
                        <button class="mobiles-button">Rumors</button>
                        <button class="mobiles-button">Apple</button>
                        <button class="mobiles-button">Motorola</button>
                    </div>

                    <div class="comon">
                        <label for="" class="text-white whitening ">Search For</label>
                        <input type="text" class="bg-white">
                        <button class="mobiles-button bg-white">Android</button>
                    </div>
                </div>

            </div>

           <div class="col-md-4 col-5 d-none d-lg-block" style="position: relative; left: 25px;">
                <button class="solid w-100 py-2">
                    <i class="fa-solid fa-mobile fa-sm mx-2" style="color: white;"></i>
                    Phone Finder</button>
                <div class="devor">
                    <button class="px-3 py-1 ">Samsung</button>
                    <button class="px-3 py-1 ">Xiaomi</button>
                    <button class="px-3 py-1 ">Asus</button>
                    <button class="px-3 py-1 ">Infinix</button>
                    <button class="px-3 py-1 ">Apple</button>
                    <button class="px-3 py-1 ">Google</button>
                    <button class="px-3 py-1 ">AlCatel</button>
                    <button class="px-3 py-1 ">Ulefone</button>
                    <button class="px-3 py-1 ">Huawei</button>
                    <button class="px-3 py-1 ">Honor</button>
                    <button class="px-3 py-1 ">Zte</button>
                    <button class="px-3 py-1 ">Tecno</button>
                    <button class="px-3 py-1 ">Nokia</button>
                    <button class="px-3 py-1 ">Oppo</button>
                    <button class="px-3 py-1 ">Microsoft </button>
                    <button class="px-3 py-1 ">Dooge</button>
                    <button class="px-3 py-1 ">Sony</button>
                    <button class="px-3 py-1 ">Realme</button>
                    <button class="px-3 py-1 ">Unidegi</button>
                    <button class="px-3 py-1 ">Blackview</button>
                    <button class="px-3 py-1 ">Lg </button>
                    <button class="px-3 py-1 ">OnePlus</button>
                    <button class="px-3 py-1 ">Coolpad</button>
                    <button class="px-3 py-1 ">Cubot</button>
                    <button class="px-3 py-1 ">HTc</button>
                    <button class="px-3 py-1 ">Nothing</button>
                    <button class="px-3 py-1 ">Oscal</button>
                    <button class="px-3 py-1 ">oukitel</button>
                    <button class="px-3 py-1 ">Motrola</button>
                    <button class="px-3 py-1 ">Vivo</button>
                    <button class="px-3 py-1 ">Shrap</button>
                    <button class="px-3 py-1 ">Itel</button>
                    <button class="px-3 py-1 ">Lenovo</button>
                    <button class="px-3 py-1 ">meizu</button>
                    <button class="px-3 py-1 ">Micromax</button>
                    <button class="px-3 py-1 ">Tcl</button>
                </div>
                <button class="solid w-50 py-2">
                    <i class="fa-solid fa-bars fa-sm mx-2"></i>
                    All Brands</button>
                <button class="solid py-2" style="    width: 177px;">
                    <i class="fa-solid fa-volume-high fa-sm mx-2"></i>
                    RUMORS MILL</button>
            </div>
        </div>
    </div>
    <div class="container mt-0 varasat">
        <div class="row">


            <div class="col-lg-4 col-md-6  mt-2">
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/motorola-moto-g-stylus-2025/-347x151/gsmarena_001.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">Moto G Stylus 5G (2025) review</div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/google-pixel-9a/-347x151/gsmarena_001.jpg"
                        alt="Google Pixel 9a">
                    <div class="review-card-body">
                        <div class="review-card-title">Google Pixel 9a review</div>
                        <div class="review-card-meta">
                            <span>04 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>28 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/oppo-find-x8-ultra/-347x151/gsmarena_001.jpg"
                        alt="Google Pixel 9a">
                    <div class="review-card-body">
                        <div class="review-card-title">Oppo Find X8 Ultra review</div>
                        <div class="review-card-meta">
                            <span>04 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>28 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/motorola-razr-60-ultra/-347x151/gsmarena_002.jpg"
                        alt="Google Pixel 9a">
                    <div class="review-card-body">
                        <div class="review-card-title">Motorola Razr 60 Ultra review</div>
                        <div class="review-card-meta">
                            <span>04 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>28 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/pixel-9a-handson/-347x151/gsmarena_001.jpg"
                        alt="Google Pixel 9a">
                    <div class="review-card-body">
                        <div class="review-card-title">Google Pixel 9a Hands-on </div>
                        <div class="review-card-meta">
                            <span>04 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>28 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/ai-erasers-compared/-347x151/gsmarena_000.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">AI Object - which phone is best?

                        </div>
                        <div class="review-card-meta">
                            <span>11 April 2025 </span>
                            <span><i class="bi bi-chat-dots-fill"></i>24 Comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/nothing-phone-3a/-347x151/gsmarena_001.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Nothing Phone (3a) review</div>
                        <div class="review-card-meta">
                            <span>03 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>33 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/nothing-phone-3a-pro/-347x151/gsmarena_001.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Nothing Phone (3a) Pro review</div>
                        <div class="review-card-meta">
                            <span>03 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>33 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/oppo-reno13/-347x151/gsmarena_000.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title ">Oppo Reno 13 Review</div>
                        <div class="review-card-meta">
                            <span>23 April 2025 </span>
                            <span><i class="bi bi-chat-dots-fill"></i>13 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/apple-iphone-16e/-347x151/gsmarena_002.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Apple 16a review </div>
                        <div class="review-card-meta">
                            <span>15 April 2025 </span>
                            <span><i class="bi bi-chat-dots-fill"></i>103 comments</span>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/24/xiaomi-pad-6s-pro-12-4/-347x151/gsmarena_001.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Xiaomi Pad 6S Pro 12.4 review</div>
                        <div class="review-card-meta">
                            <span>15 April 2025 </span>
                            <span><i class="bi bi-chat-dots-fill"></i>103 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/24/tecno-camon-30-pro/-347x151/gsmarena_002.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Tecno Spark 30 Pro review </div>
                        <div class="review-card-meta">
                            <span>11 April 2025 </span>
                            <span><i class="bi bi-chat-dots-fill"></i>24 Comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/23/iphone-15/-347x151/gsmarena_001.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Apple 15 review </div>
                        <div class="review-card-meta">
                            <span>15 April 2025 </span>
                            <span><i class="bi bi-chat-dots-fill"></i>103 comments</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mt-2 ">
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/nothing-cmf-phone-2-pro/-347x151/gsmarena_001.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Nothing CMF Phone 2 Pro review</div>
                        <div class="review-card-meta">
                            <span>03 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>33 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/sony-xperia-1-vii/rev13/-347x151/gsmarena_001.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Sony Xperia 1 VII review</div>
                        <div class="review-card-meta">
                            <span>03 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>33 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/redmagic-10-air/-347x151/gsmarena_011.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">nubia RedMagic 10 Air review

                        </div>
                        <div class="review-card-meta">
                            <span>23 April 2025 </span>
                            <span><i class="bi bi-chat-dots-fill"></i>13 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/infinix-note-50-pro-4g/-347x151/gsmarena_003.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Infinix Note 50 Pro 4G review

                        </div>
                        <div class="review-card-meta">
                            <span>15 April 2025 </span>
                            <span><i class="bi bi-chat-dots-fill"></i>103 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/motorola-edge-60-fusion/-347x151/gsmarena_000.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Motorola Edge 60 Fusion review
                        </div>
                        <div class="review-card-meta">
                            <span>11 April 2025 </span>
                            <span><i class="bi bi-chat-dots-fill"></i>24 Comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/vivo-v50/-347x151/gsmarena_001.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Vivo V50 review</div>
                        <div class="review-card-meta">
                            <span>03 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>33 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/infinix-note-50-pro-plus/-347x151/gsmarena_001.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Infinix Note 50 Pro+ Preview</div>
                        <div class="review-card-meta">
                            <span>03 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>33 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/poco-f7-pro/-347x151/gsmarena_001.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Poco F7 Pro review
                        </div>
                        <div class="review-card-meta">
                            <span>23 April 2025 </span>
                            <span><i class="bi bi-chat-dots-fill"></i>13 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/poco-f7-ultra/re/-347x151/gsmarena_002.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Poco F7 Ultra Review
                        </div>
                        <div class="review-card-meta">
                            <span>15 April 2025 </span>
                            <span><i class="bi bi-chat-dots-fill"></i>103 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/25/samsung-galaxy-a36/-347x151/gsmarena_003.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Samsung Galaxy A36 review </div>
                        <div class="review-card-meta">
                            <span>11 April 2025 </span>
                            <span><i class="bi bi-chat-dots-fill"></i>24 Comments</span>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/24/honor-200-pro/-347x151/gsmarena_001.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Honor 200 Pro review </div>
                        <div class="review-card-meta">
                            <span>15 April 2025 </span>
                            <span><i class="bi bi-chat-dots-fill"></i>103 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/24/motorola-edge-50-fusion/-347x151/gsmarena_001.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Motorola Edge 50 Fusion review </div>
                        <div class="review-card-meta">
                            <span>11 April 2025 </span>
                            <span><i class="bi bi-chat-dots-fill"></i>24 Comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/reviews/23/apple-iphone-15-pro-max/-347x151/gsmarena_003.jpg"
                        alt="Nothing CMF Phone 2 Pro">
                    <div class="review-card-body">
                        <div class="review-card-title">Apple 15 pro max review </div>
                        <div class="review-card-meta">
                            <span>15 April 2025 </span>
                            <span><i class="bi bi-chat-dots-fill"></i>103 comments</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4  bg-white ">
                <img src="https://fdn.gsmarena.com/imgroot/static/banners/self/review-iphone-16-pro-300x250.jpg"
                    class="w-100 mt-2" alt="">
                <h6 class="text-secondary mt-2 fw-bold" style="text-transform: uppercase;">Latest Devices</h6>
                <div class="cent">

                    <div class="d-flex">
                        <div class="canel">
                            <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/vivoiy300-gt.jpg" alt="">
                            <p>Vivo y300 Gt</p>
                        </div>
                        <div class="canel mx-4">
                            <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-m56-5g.jpg"
                                alt="">
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
                            <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/motorola-edge-60-fusion.jpg"
                                alt="">
                            <p>Motorla Edge 60</p>
                        </div>
                        <div class="canel ">
                            <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/sony-xperia-1-vi-red.jpg"
                                alt="">
                            <p>Song xperia -1 VII</p>
                        </div>
                    </div>
                </div>
                <h6 style="border-left: solid 5px grey ;text-transform: uppercase;" class=" fw-bold px-3 text-secondary mt-3" >Popular comparisons</h6>
                 <div class="sentizer bg-white mt-2 p-3 rounded shadow-sm" style="    text-transform: Uppercase;
                                            font-size: 13px;
                                            font-weight: 700;">
                    <div class="row">
                        <div class="col-12">
                            <p class="mb-2"
                                style="background-color: #ffe6f0; color: #090E21; text-transform: capitalize;">Samsung
                                Galaxy A55 vs.
                                Galaxy A56</p>
                            <p class="mb-2" style=" text-transform: capitalize;">Apple iPhone 16 Pro Max vs. Galaxy S25
                                Ultra</p>
                            <p class="mb-2" style="background-color: #ffe6f0; text-transform: capitalize; ">Samsung
                                Galaxy S24 Ultra vs. Galaxy S25
                                Ultra</p>
                            <p class="mb-2" style=" text-transform: capitalize;">Samsung Galaxy A36 vs. Galaxy A56</p>
                            <p class="mb-2" style="background-color: #ffe6f0; text-transform: capitalize;">Samsung
                                Galaxy S24 FE vs. Galaxy A56</p>
                            <p class="mb-2" style=" text-transform: capitalize;">Apple iPhone 13 vs. Apple iPhone 14</p>
                            <p class="mb-2" style="background-color: #ffe6f0;text-transform: capitalize;">Samsung Galaxy
                                S23 Ultra vs. Galaxy S24
                                Ultra</p>
                            <p class="mb-2" style=" text-transform: capitalize;">Samsung Galaxy S24 vs. Galaxy S24 FE
                            </p>
                            <p class="mb-2" style="background-color: #ffe6f0;text-transform: capitalize;">Xiaomi Redmi
                                Note 14 Pro+ vs.
                                Redmi 14 Pro </p>
                            <p class="mb-2" style=" text-transform: capitalize;">Apple iPhone 16 Pro Max vs. iPhone 16
                                Pro</p>
                            <p class="mb-0" style="background-color: #ffe6f0;text-transform: capitalize;">Samsung Galaxy
                                S24 vs. Galaxy S25</p>
                        </div>
                    </div>
                </div>

                <div class="d-flex">
                    <h6 class="text-secondary mt-2 d-inline fw-bold" style="text-transform: uppercase;">top 10 by Daily Interest </h6>
                </div>
                <div class="center w-100">
                    <table class="table table-sm custom-table">
                        <thead>
                            <tr class="text-white " style="background-color: #4C7273; color: white;">
                                <th style="color: white;">#</th>
                                <th style="color: white;">Devices</th>
                                <th style="color: white;">Daily Hits</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row">1</th>
                                <td class="text-start">Samsung Galaxy A56</td>
                                <td class="text-end">29,819</td>
                            </tr>
                            <tr class="highlight">
                                <th scope="row">2</th>
                                <td class="text-start">Xiaomi Redmi Turbo 4 Pro</td>
                                <td class="text-end">27,589</td>
                            </tr>
                            <tr>
                                <th scope="row">3</th>
                                <td class="text-start">Samsung Galaxy S25 Ultra</td>
                                <td class="text-end">23,387</td>
                            </tr>
                            <tr class="highlight">
                                <th scope="row">4</th>
                                <td class="text-start">Sony Xperia 1 VII 5G</td>
                                <td class="text-end">20,008</td>
                            </tr>
                            <tr>
                                <th scope="row">5</th>
                                <td class="text-start">Xiaomi Poco X7 Pro</td>
                                <td class="text-end">19,249</td>
                            </tr>
                            <tr class="highlight">
                                <th scope="row">6</th>
                                <td class="text-start">OnePlus 13T 5G</td>
                                <td class="text-end">18,523</td>
                            </tr>
                            <tr>
                                <th scope="row">7</th>
                                <td class="text-start">Apple iPhone 16 Pro Max</td>
                                <td class="text-end">17,800</td>
                            </tr>
                            <tr class="highlight">
                                <th scope="row">8</th>
                                <td class="text-start">Nothing CMF Phone 2 Pro 5G</td>
                                <td class="text-end">17,330</td>
                            </tr>
                            <tr>
                                <th scope="row">9</th>
                                <td class="text-start">Samsung Galaxy A36</td>
                                <td class="text-end">16,592</td>
                            </tr>
                            <tr class="highlight">
                                <th scope="row">10</th>
                                <td class="text-start">Motorola Edge 60 Pro</td>
                                <td class="text-end">16,433</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="center w-100 " style="margin-top: 12px;">
                    <h6 class="text-secondary mt-2 d-inline" style="text-transform: uppercase;">top 10 by Fans </h6>

                    <table class="table table-sm custom-table">
                        <thead>
                            
                               <tr class="text-white" style="background-color: #14222D;">
                                <th style="color: white;  font-size: 15px;  ">#</th>
                                <th style="color: white;  font-size: 15px;">Device</th>
                                <th style="color: white;  font-size: 15px;">Favorites</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row">1</th>
                                <td class="text-start">Samsung Galaxy S24 Ultra</td>
                                <td class="text-end">1,722</td>
                            </tr>
                            <tr class="highlight-12">
                                <th scope="row">2</th>
                                <td class="text-start">Samsung Galaxy S25 Ultra</td>
                                <td class="text-end">926</td>
                            </tr>
                            <tr>
                                <th scope="row">3</th>
                                <td class="text-start">Samsung Galaxy A55</td>
                                <td class="text-end">911</td>
                            </tr>
                            <tr class="highlight-12">
                                <th scope="row">4</th>
                                <td class="text-start">OnePlus 12</td>
                                <td class="text-end">746</td>
                            </tr>
                            <tr>
                                <th scope="row">5</th>
                                <td class="text-start">Xiaomi Poco X6 Pro</td>
                                <td class="text-end">634</td>
                            </tr>
                            <tr class="highlight-12">
                                <th scope="row">6</th>
                                <td class="text-start">Xiaomi 14 Ultra</td>
                                <td class="text-end">597</td>
                            </tr>
                            <tr>
                                <th scope="row">7</th>
                                <td class="text-start">OnePlus 13</td>
                                <td class="text-end">564</td>
                            </tr>
                            <tr class="highlight-12">
                                <th scope="row">8</th>
                                <td class="text-start">Sony Xperia 1 VI</td>
                                <td class="text-end">554</td>
                            </tr>
                            <tr>
                                <th scope="row">9</th>
                                <td class="text-start">Samsung Galaxy S24</td>
                                <td class="text-end">546</td>
                            </tr>
                            <tr class="highlight-12">
                                <th scope="row">10</th>
                                <td class="text-start">Apple iPhone 16 Pro Max</td>
                                <td class="text-end">538</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <h6 style="border-left: 7px solid #EFEBE9 ; text-transform: uppercase;" class=" fw-bold px-2 text-secondary mt-2 d-inline mt-4">In Storeies
                    Now</h6>
                
                <div class="cent mb-4">
                
                    <div class="d-flex">
                        <div class="canel">
                            <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/nothing-cmf-phone-2-pro.jpg" alt="">
                            <p>Nothing Cmf 2 </p>
                        </div>
                        <div class="canel mx-4">
                            <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/motorola-edge-60-pro.jpg" alt="">
                            <p>Motrola edge 60 </p>
                        </div>
                        <div class="canel ">
                            <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/vivo-x200-ultra.jpg" alt="">
                            <p>Vivo x200 Ultra</p>
                        </div>
                    </div>
                    <div class="d-flex">
                        <div class="canel">
                            <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/vivo-t4.jpg" alt="">
                            <p>Vivo t4</p>
                        </div>
                        <div class="canel mx-4">
                            <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/motorola-edge-60.jpg" alt="">
                            <p>Motrola Edge 16</p>
                        </div>
                        <div class="canel ">
                            <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/motorola-razr-60-ultra-5g.jpg" alt="">
                            <p>motorola razr 60 ultra</p>
                        </div>
                    </div>
                    <div class="d-flex">
                        <div class="canel">
                            <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/vivo-iqoo-z10x.jpg" alt="">
                            <p>Vivo iQ00 Z10 x</p>
                        </div>
                        <div class="canel mx-4">
                            <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/motorola-moto-g-stylus-5g-2025.jpg" alt="">
                            <p>Motrola Moto G </p>
                        </div>
                        <div class="canel ">
                            <img class="shrink" src="https://fdn2.gsmarena.com/vv/bigpic/google-pixel-9a.jpg" alt="">
                            <p>Google Pixel 9a </p>
                        </div>
                    </div>
                </div>
  
                <div style="position: sticky; top: 10px;">
                    <img src="https://fdn.gsmarena.com/imgroot/static/banners/self/review-pixel-9-pro-300x250.jpg"
                        class=" d-block mx-auto" style="width: 300px;">
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
    <script src="script.js"></script>
</body>

</html>