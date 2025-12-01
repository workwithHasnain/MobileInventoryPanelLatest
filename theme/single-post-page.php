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

    <div class=" mt-4 d-lg-none d-block bg-white">
        <h3 style="font-size: 23px;
        font-weight: 600; font-family: 'oswald';" class="mx-3 my-5">LG is pushing Apple to use the iPad Pro's Tandem OLED tech in
            future iPhones</h3>
        <img style="    height: 100%;
    width: -webkit-fill-available;" src="/imges/ever1.jpg" alt="">

    </div>
    <div class="container support content-wrapper" id="Top">
        <div class="row">

            <div class="col-md-8 col-5 d-md-inline  " style="border: 1px solid #e0e0e0;">
                <div class="comfort-life-23 position-absolute d-flex justify-content-between  ">
                    <div class="article-info">
                        <div class="bg-blur">
                            <svg class="float-end mx-3 mt-1" xmlns="http://www.w3.org/2000/svg" height="34" width="34"
                                viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
                                <path fill="#ffffff"
                                    d="M448 256C501 256 544 213 544 160C544 107 501 64 448 64C395 64 352 107 352 160C352 165.4 352.5 170.8 353.3 176L223.6 248.1C206.7 233.1 184.4 224 160 224C107 224 64 267 64 320C64 373 107 416 160 416C184.4 416 206.6 406.9 223.6 391.9L353.3 464C352.4 469.2 352 474.5 352 480C352 533 395 576 448 576C501 576 544 533 544 480C544 427 501 384 448 384C423.6 384 401.4 393.1 384.4 408.1L254.7 336C255.6 330.8 256 325.5 256 320C256 314.5 255.5 309.2 254.7 304L384.4 231.9C401.3 246.9 423.6 256 448 256z" />
                            </svg>
                        </div>
                    </div>
                    <div style="    display: flex;  flex-direction: column;">
                        <h1 class="article-info-name">LG is pushing Apple to use the iPad Pro's Tandem OLED tech in
                            future iPhones</h1>
                        <div class="article-info">
                            <div class="bg-blur">
                                <div class="d-flex justify-content-end">
                                    <div class="d-flex flexiable ">
                                        <img src="/imges/download-removebg-preview.png" alt="">
                                        <h5 style="font-family:'oswald' ; font-size: 17px" class="mt-2">COMMENTS (17)
                                        </h5>
                                    </div>
                                    <div class="d-flex flexiable ">
                                        <img src="/imges/download-removebg-preview.png" alt="">
                                        <h5 style="font-family:'oswald' ; font-size: 17px;" class="mt-2">POST YOUR
                                            COMMENT </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
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
    <div class="container bg-white" style="border: 1px solid #e0e0e0;">
        <div class="row">
            <div class="col-lg-8 py-3" style=" padding-left: 0; padding-right: 0; border: 1px solid #e0e0e0;">
                <div>
                    <div class="d-flex align-items-center justify-content-between  gap-portion">
                        <div class="d-flex">

                            <button class="section-button">Vlad</button>
                            <p class="my-2 portion-headline mx-1">27 Augest 2025</p>
                        </div>
                        <div>
                            <button class="section-button">Apple</button>
                            <button class="section-button">IOs</button>
                            <button class="section-button">Rumors</button>
                        </div>
                    </div>
                </div>
                <div class="document-section">
                    <p class="classy gap-portion">Apple's newest iPad Pros are using Tandem OLED display panels provided
                        primarily by LG Display, and the Korean company has been trying to convince Apple to take the
                        same tech and put it into future iPhones.
                    <p>

                    <p class="classy gap-portion">It hasn't succeeded thus far, but according to a new report, Apple
                        might incorporate Tandem OLED panels in the 2028 iPhones (so presumably the iPhone 20 family -
                        or will that be XX?</p>
                    <img class="center-img" src="imges/gsmarena_001 (1)--12.jpg" alt="">

                    <p class="classy gap-portion">No decision has been made by Apple so far, but of course LG would like
                        the Cupertino company to choose this tech, since that would mean it would either be the sole
                        supplier for displays for the 2028 iPhones, or its share would grow substantially vs. Samsung
                        Display, Apple's other main supplier of displays. LG holds 348 patents in the US in the Tandem
                        OLED field.
                    </p>
                    <p class="classy gap-portion">Tandem OLEDs stack two OLED layers on top of each other, thus
                        improving brightness and extending the lifespan of the display, while somehow also improving
                        power efficiency in the process.
                    </p>
                    <p class="classy gap-portion">According to a previous report, Apple is open to the idea, but for
                        iPhones it only wants to stack two layers for the blue subpixels, not the entire matrix - so the
                        red and green subpixels would remain on a single layer. The industry refers to this as
                        "simplified tandem" and it's a more cost-effective solution to blue subpixel degradation.

                        Source (in Korean)
                    </p>

                </div>
                <div class="blogs-div mt-4">
                    <h4 class="related-articles-heading">Related Articles</h4>
                    <div class="blog-post">
                        <div class="box-es">
                            <img src="imges/fms-.jpg" alt="">
                            <h6>
                                Gurman: Apple working on three major iPhone redesigns
                            </h6>
                        </div>
                        <div class="box-es">
                            <img src="imges/gsmarena_00012.jpg" alt="">
                            <h6>
                                Apple wants to bring Touch ID to its watches starting next year
                            </h6>
                        </div>
                        <div class="box-es">
                            <img src="imges/gsmarena_000-5666.jpg" alt="">
                            <h6>
                                No one is using the iPhone Camera Control and Apple will drop it, sketchy rumor claims
                            </h6>
                        </div>
                        <div class="box-es">
                            <img src="imges/gsmarena_000-80-088.jpg" alt="">
                            <h6> Apple's entire iPhone 17 family to be made in India for the US market from the
                                beginning

                            </h6>
                        </div>
                    </div>
                </div>
                <div class="comments">
                    <h5 class="border-bottom reader  py-3 mx-2">READER COMMENTS</h5>
                    <div class="first-user" style="background-color: #EDEEEE;">
                        <div class="user-thread">
                            <div class="uavatar">
                                <img src="https://www.gravatar.com/avatar/e029eb57250a4461ec444c00df28c33e?r=g&amp;s=50"
                                    alt="">
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
                                <img src="https://www.gravatar.com/avatar/e029eb57250a4461ec444c00df28c33e?r=g&amp;s=50"
                                    alt="">
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
                                <button class="button-links">read all comments</button>
                                <button class="button-links">post your comment</button>
                            </div>
                            <p class="div-last">Total reader comments: <b>34</b> </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4  col-12  bg-white p-3">
                <img src="https://fdn.gsmarena.com/imgroot/static/banners/self/review-pixel-9-pro-300x250.jpg"
                    class=" d-block mx-auto" style="width: 300px;">
                <div class="d-flex">
                    <h5 class="text-secondary mt-2 d-inline fw-bold " style="text-transform: uppercase;">top 10 by Daily
                        Interest </h5>
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

                <h6 style="border-left: solid 5px grey ; text-transform: uppercase;"
                    class=" fw-bold px-3 text-secondary py-1">Electric Vehicles</h6>
                <div style="background-color: #f2f2f2; height: 260px">
                    <div class="col-md-12">
                        <img src="https://st.arenaev.com/news/25/08/2026-porsche-macan-electric/-344x215/arenaev_000.jpg"
                            class=" rounded" alt="News Image">
                        <p class="fw-bold mb-1 wanted-12 mt-2 mx-auto text-center">
                            Xiaomi SU7 faces quality concerns, some owners sue the company
                    </div>

                </div>

                <div class="d-flex my-3" style="background-color: #f2f2f2;">
                    <div class="col-md-12 py-2">
                        <p class="fw-bold mb-1 wanted-12 text-center">
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

                <div style="position: sticky;margin-top: 12px; top: 0;">
                    <img src="https://fdn.gsmarena.com/imgroot/static/banners/self/review-galaxy-s25-300x250.jpg"
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