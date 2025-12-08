<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GSMArena</title>
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
            <div class="col-md-8 col-5  d-lg-inline d-none ">
                <div class="comfort-life position-absolute">
                    <img class="w-100 h-100" src="imges/magnifient sectton.jpeg"
                        style="background-repeat: no-repeat; background-size: cover;" alt="">
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
    <div class="comparison-container container bg-white">
        <div class="row">
            <div class="phone-card col-lg-4">
                <div class="compare-checkbox">
                    <label>
                        Compare
                        <input type="text" name="compare" class="bg-white text-center-auto border"
                            placeholder="Search" />
                    </label>
                </div>
                <div class="phone-name">Samsung Galaxy S25 Edge</div>
                <div class="d-flex">
                    <img src="https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-s25-edge.jpg"
                        alt="Samsung Galaxy S25 Edge">
                    <div class="buttons">
                        <button>REVIEW</button>
                        <button>SPECIFICATIONS</button>
                        <button>READ OPINIONS</button>
                        <button>PICTURES</button>
                    </div>
                </div>
                <div class="price text-center ">256GB 12GB RAM<br>$1,099.99</div>
                <img src="imges/wanted.png" style="height: 40px; display: block; margin: auto;" alt="">
            </div>
            <div class="phone-card col-lg-4">
                <div class="compare-checkbox">
                    <label>
                        Compare
                        <input type="text" name="compare" class="bg-white  text-center-auto border"
                            placeholder="Campare" />
                    </label>
                </div>
                <div class="phone-name">Apple iPhone 16</div>
                <div class="d-flex">
                    <img src="https://fdn2.gsmarena.com/vv/bigpic/apple-iphone-16.jpg" alt="Apple iPhone 16" />
                    <div class="buttons">
                        <button>REVIEW</button>
                        <button>SPECIFICATIONS</button>
                        <button>READ OPINIONS</button>
                        <button>PICTURES</button>
                    </div>

                </div>
                <div class=" align-items-center m-auto">
                    <div class=" price text-center ">128GB 8GB RAM<br>$899.99</div>
                    <img src="imges/wanted.png" style="height: 40px;" alt="">
                </div>
            </div>
            <div class="phone-card col-lg-4">
                <div class="compare-checkbox">
                    <label>
                        Compare
                        <input type="text" name="compare" class="bg-white text-center-auto border"
                            placeholder="Campare" />
                    </label>
                </div>
                <div class="phone-name">Google Pixel 9</div>
                <div class="d-flex">

                    <img src="imges/download-123.jpeg" alt="Google Pixel 9" />

                    <div class="buttons">
                        <button>REVIEW</button>
                        <button>SPECIFICATIONS</button>
                        <button>READ OPINIONS</button>
                        <button>PICTURES</button>
                    </div>

                </div>
                <div class=" align-items-center m-auto">
                    <div class=" price text-center ">128GB 8GB RAM<br>$899.99</div>
                    <img src="imges/wanted.png" style="height: 40px;" alt="">
                </div>

            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Samsung Galaxy S25 Edge</th>
                    <th>Apple iPhone 16</th>
                    <th>Google Pixel 9</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="3" style="    color: #f14d4d;
    font-size: 16px; background: #f9f9f9; font-weight: 600;">Network Technology
                    </td>
                </tr>
                <tr>
                    <td>GSM / HSPA / LTE / 5G</td>
                    <td>GSM / CDMA / HSPA / EVDO / LTE / 5G</td>
                    <td>GSM / HSPA / LTE / 5G</td>
                </tr>

                <tr>
                    <td colspan="3" style="     color: #f14d4d;
    font-size: 16px; background: #f9f9f9; font-weight: 600;">Announcement Date
                    </td>
                </tr>
                <tr>
                    <td>Announced: 2025, May 13</td>
                    <td>Announced: 2025, April 25</td>
                    <td>Announced: 2025, June 10</td>
                </tr>

                <tr>
                    <td colspan="3" style=" font-weight: 600;   color: #f14d4d;
    font-size: 16px; background: #f9f9f9;">Availability /
                        Status</td>
                </tr>
                <tr>
                    <td>Status: Coming soon (Release May 29, 2025)</td>
                    <td>Available (Released May 5, 2025)</td>
                    <td>Coming soon</td>
                </tr>

                <tr>
                    <td colspan="3" style="  font-weight: 600;  color: #f14d4d;
    font-size: 16px; background: #f9f9f9;">Dimensions</td>
                </tr>
                <tr>
                    <td>158.2 x 75.6 x 5.8 mm</td>
                    <td>146.7 x 71.5 x 7.8 mm</td>
                    <td>150.9 x 74.7 x 8.5 mm</td>
                </tr>

                <tr>
                    <td colspan="3" style=" font-weight: 600;   color: #f14d4d;
    font-size: 16px; background: #f9f9f9;">Weight</td>
                </tr>
                <tr>
                    <td>163 g</td>
                    <td>174 g</td>
                    <td>180 g</td>
                </tr>

                <tr>
                    <td colspan="3" style="  font-weight: 600;   color: #f14d4d;
    font-size: 16px;background: #f9f9f9;">Operating System
                        (OS)</td>
                </tr>
                <tr>
                    <td>Android 15, One UI 7</td>
                    <td>iOS 18</td>
                    <td>Android 15</td>
                </tr>

                <tr>
                    <td colspan="3" style=" font-weight: 600;   color: #f14d4d;
    font-size: 16px; background: #f9f9f9;">Chipset</td>
                </tr>
                <tr>
                    <td>Snapdragon 8 Elite (3 nm)</td>
                    <td>Apple A18 Pro</td>
                    <td>Tensor G4</td>
                </tr>

                <tr>
                    <td colspan="3" style=" font-weight: 600;    color: #f14d4d;
    font-size: 16px; background: #f9f9f9;">Main Camera</td>
                </tr>
                <tr>
                    <td>200 MP + 12 MP (ultrawide)</td>
                    <td>48 MP + 12 MP (ultrawide)</td>
                    <td>50 MP + 12 MP (ultrawide)</td>
                </tr>

                <tr>
                    <td colspan="3" style=" font-weight: 600;   color: #f14d4d;
    font-size: 16px; background: #f9f9f9;">Selfie Camera</td>
                </tr>
                <tr>
                    <td>12 MP (wide)</td>
                    <td>12 MP (wide)</td>
                    <td>10.8 MP (wide)</td>
                </tr>

                <tr>
                    <td colspan="3" style="  font-weight: 600;  color: #f14d4d;
    font-size: 16px; background: #f9f9f9;">Battery</td>
                </tr>
                <tr>
                    <td>3900 mAh, 25W wired & 15W wireless</td>
                    <td>3279 mAh, 20W wired & 15W MagSafe wireless</td>
                    <td>4400 mAh, 30W wired & wireless</td>
                </tr>

                <tr>
                    <td colspan="3" style=" font-weight: 600;   color: #f14d4d;
    font-size: 16px; background: #f9f9f9;">Price</td>
                </tr>
                <tr>
                    <td>$1099.99 / ₹109,999</td>
                    <td>$1199.00 / ₹109,900</td>
                    <td>$899.00 / ₹75,000</td>
                </tr>
            </tbody>
        </table>



    </div>

    <div id="bottom" class="container d-flex mt-3" style="max-width: 1034px;">
        <div class="row align-items-center">
            <div class="col-md-2 m-auto col-4 d-flex justify-content-center align-items-center "> <img
                    src="https://fdn2.gsmarena.com/w/css/logo-gsmarena-com.png" alt="">
            </div>
            <div class="col-10 nav-wrap m-auto text-center ">
                <div class="nav-container">
                    <a href="/index.html">Home</a>
                    <a href="/news.html">News</a>
                    <a href="/rewies.html">Reviews</a>
                    <a href="/videos.html">Videos</a>
                    <a href="/featured.html">Featured</a>
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
                    <a href="#">Contact us</a>
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