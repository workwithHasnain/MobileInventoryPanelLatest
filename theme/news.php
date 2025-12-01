<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GSMArena New Page</title>
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

            <div class="col-md-8 col-5  d-md-inline d-none col-12 ">
                <div class="comfort-life d-none d-lg-flex align-items-baseline position-absolute ">
                    <img src="/images/ChatGPT Image May 14, 2025, 01_11_08 PM.png" alt="">
                    <div class="position-absolute d-flex mt-1">
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
                    <h1 class="fs-1 font-bolder" style="position: absolute;
                     bottom: 32%; ">News</h1>
                    <div class="comon">
                        <label for="" class="text-white whitening">Search For</label>
                        <input type="text" class="bg-white">
                        <button class="mobiles-button bg-white">Search</button>
                    </div>
                </div>

            </div>
             <div class="col-md-4 col-5 d-none d-lg-block" style="position: relative; left: 25px;">
                <button class="solid w-100 py-2">
                    <i class="fa-solid fa-mobile fa-sm mx-2" style="color: white;"></i>
                    Phone Finder</button>
                <div class="devor">
                    <button class="px-3 py-1 ">Sumsung</button>
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
    <div class="container mt-0  ">
        <div class="row">
            <div class="col-lg-4 col-md-6  mt-3" style="background-color: #EEEEEe;">
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/xiaomi-15s-pro-xring-o1-antutu-weibo/-344x215/gsmarena_000.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">Xiaomi 15S Pro chip match the Xiaomi 15 Pro test</div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/alcatel-v3-classic-pro-flipkart/-344x215/gsmarena_001.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">Alcatel V3 Classic and V3 Pro are also coming</div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/samsung-galaxy-s26-new-camera-rumor/-344x215/gsmarena_000.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">Samsung Galaxy S26 surprise in the camera department </div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/cmf-buds-ifr/-344x215/gsmarena_000.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">CMF Buds 2 and Buds 2 Plus in for review</div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/honor-400-6-years/-344x215/gsmarena_000.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">
                            Honor 400 series Android 16 in 2025</div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/google-io-gemini-news/-344x215/gsmarena_000.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">
                            Google launches new AI plan, announces Gemini features
                        </div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/amazon-foldable-screen-laptop-rumor/-344x215/gsmarena_000.jpg"
                        alt="">
                    <div class="review-card-body">
                        <div class="review-card-title">Amazon to be working on a foldable-screen laptop of its own</div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/realme-gt-7t-box/-344x215/gsmarena_000.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">More Realme GT 7T specs revealed in a photo of its box</div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>


                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/xiaomi-pad-7-ultra-geekbench/-344x215/gsmarena_001.jpg"
                        alt="">
                    <div class="review-card-body">
                        <div class="review-card-title">Xiaomi Pad 7 Ultra also appears on Geekbench with Xring O1</div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/realme-gt-7-dream-edition-aston-martin-partnership/-344x215/gsmarena_001.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">Realme teases the GT 7 Dream Edition </div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>

            </div>
            <div class="col-lg-4 mt-3 col-md-6  col-12  sentizer-er" style="background-color: #EEEEEE; ">
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/samsung-project-moohan-availability/-344x215/gsmarena_001.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">Google reveals when Samsung's headset will be available</div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/amazon-drone-delivery-iphones-galaxy-smartphones/-344x215/gsmarena_000.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">Amazon now delivers iPhones with drones
                        </div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/poco-f7-new-certification/-344x215/gsmarena_000.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">Poco F7 now rumored to arrive in June after new certification
                        </div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/samsung-galaxy-z-flip7-exynos-2500-confirmed/-344x215/gsmarena_000.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">Samsung Galaxy Z Flip7 now 'confirmed'
                        </div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/alcatel-v3-ultra-5g-flipkart/-344x215/gsmarena_001.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">Alcatel V3 Ultra key features revealed</div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/redmi-k80-ultra-battery/-344x215/gsmarena_000.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title"> Redmi K80 Ultra massive battery capacity</div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/oneplus-ace5-ultra-ace5-racing-teaser-trailer/-344x215/gsmarena_000.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">
                            OnePlus Ace 5 Ultra will be announced next week
                        </div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/redmagic-10s-pro-plus-antutu/-344x215/gsmarena_000.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title"> RedMagic 10S Pro+ surfaces with Snapdragon 8</div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/nothing-phone-3-launch-time-frame-confirmed/-344x215/gsmarena_001.jpg"
                        alt="">
                    <div class="review-card-body">
                        <div class="review-card-title">This is when the Nothing Phone (3) is coming</div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
                <div class="review-card">
                    <img src="https://fdn.gsmarena.com/imgroot/news/25/05/xiaomi-15s-pro-ofic-date/-344x215/gsmarena_001.jpg"
                        alt="Moto G Stylus 5G">
                    <div class="review-card-body">
                        <div class="review-card-title">Xiaomi 15S Pro officially confirmed to arrive with Xring O1</div>
                        <div class="review-card-meta">
                            <span>02 May 2025</span>
                            <span><i class="bi bi-chat-dots-fill"></i>40 comments</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4  col-12  bg-white p-3">

                <img src="https://fdn.gsmarena.com/imgroot/static/banners/self/arenaev-300x250.jpg" class="w-100 "
                    style="width: 400px;" alt="">
                <div class="review-column-list-item review-column-list-item-secondary mt-3 w-100">
                    <img class="review-list-item-image w-100"
                        src="https://fdn.gsmarena.com/imgroot/reviews/25/motorola-moto-g-stylus-2025/-347x151/gsmarena_001.jpg"
                        alt="Moto G Stylus 5G (2025) review" style="margin-left: -10px;">

                    <h1 class="common"> Mooto G Stylus 5G (2025) review</h1>
                    <img class="review-list-item-image w-100"
                        src="https://fdn.gsmarena.com/imgroot/reviews/25/google-pixel-9a/-347x151/gsmarena_001.jpg"
                        alt="Google Pixel 9a review" style="margin-left: -10px;">
                    <h1 class="common">Google pIxel 9a Review</h1>
                </div>
                <div class="d-flex">
                    <h5 class="text-secondary mt-2 d-inline fw-bold " style="text-transform: uppercase;">top 10 by Daily Interest </h5>
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
                    <h5 class="text-secondary mt-2 d-inline fw-bold " style="text-transform: uppercase;">top 10 by Fans </h5>

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
                <h6 style="border-left: solid 5px grey ; text-transform: uppercase;" class=" fw-bold px-3 text-secondary py-1">Electric Vehicles</h6>
                <div style="background-color: #f2f2f2; height: 260px">
                    <div class="col-md-12">
                        <img src="https://st.arenaev.com/news/25/05/xiaomi-su7-faces-quality-concerns/-344x215/arenaev_001.jpg"
                            class=" rounded" alt="News Image">
                        <p class="fw-bold mb-1 wanted-12 mt-2 mx-auto text-center">
                            Xiaomi SU7 faces quality concerns, some owners sue the company
                    </div>

                </div>

                <div class="d-flex my-3" style="background-color: #f2f2f2;">
                    <div class="col-md-12 py-2">
                        <p class="fw-bold mb-1 wanted-12 text text-center">
                            Xiomo Sign Partnership agreement with nurbugging
                        </p>
                    </div>
                </div>
                <div class="d-flex" style="background-color: #f2f2f2;">

                    <div class="col-md-12 py-2">
                        <p class="fw-bold mb-1 wanted-12 text-center ">
                            Li auto refreshes electric suv lineup with tech boost keep prices steady news
                        </p>
                    </div>
                </div>

                <div style="position: sticky;margin-top: 12px; ">
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