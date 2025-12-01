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
            
            <div class="col-8 comfort-life-zone">
                <img src="imges/Screenshot (162).png" alt="">
            </div>
            <div class="col-md-4 col-5 d-none d-lg-block" style="position: relative; left: 25px;">
                <button class="solid w-100 py-2">
                    <i class="fa-solid fa-mobile fa-sm" style="color: white;"></i>
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
                    <button class="px-3 py-1 ">Motorola</button>
                    <button class="px-3 py-1 ">Vivo</button>
                    <button class="px-3 py-1 ">Shrap</button>
                    <button class="px-3 py-1 ">Itel</button>
                    <button class="px-3 py-1 ">Lenovo</button>
                    <button class="px-3 py-1 ">meizu</button>
                    <button class="px-3 py-1 ">Micromax</button>
                    <button class="px-3 py-1 ">Tcl</button>
                </div>
                <button class="solid w-50 py-2">
                    <i class="fa-solid fa-bars fa-sm"></i>
                    All Brands</button>
                <button class="solid  py-2" style="    width: 177px;">
                    <i class="fa-solid fa-volume-high fa-sm"></i>
                    Remor Mill</button>
            </div>
        </div>
    </div>
    <div class="container mt-0 varasat">
        <div class="row">

           
            <div class="col-md-8 col-12 wrapper sentizer-er " style="background-color: #EEEEEE; ">
                <iframe width="710" style="margin-top: 22px;" height="375"
                    src="https://www.youtube.com/embed/i3ZU9-TGJlE?si=9t_Ue5Yad0x4W7u_" title="YouTube video player"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                <iframe width="710" height="375" src="https://www.youtube.com/embed/5_NQUTJLELk?si=V3tyvYlVRatehh8U"
                    title="YouTube video player" frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                <iframe width="710" height="375" src="https://www.youtube.com/embed/r2fbevSvSm4?si=CQ0bhm8VvLK1ZZAm"
                    title="YouTube video player" frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                <iframe width="710" height="375" src="https://www.youtube.com/embed/8jgxffmOMIA?si=d7oOc7I7mcVHZpEa"
                    title="YouTube video player" frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                <iframe width="710" height="375" src="https://www.youtube.com/embed/zQmKS--Cn_w?si=0epv5k6Ga81K1AT4"
                    title="YouTube video player" frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                <iframe width="710" height="375" src="https://www.youtube.com/embed/xpfV28AX9PI?si=cu570KUSze0boJdb"
                    title="YouTube video player" frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                <iframe width="710" height="375" src="https://www.youtube.com/embed/1AgUxSwUBRw?si=iAljRRfl4iMxLZf2"
                    title="YouTube video player" frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
                </iframe>
                <iframe width="710" height="375" src="https://www.youtube.com/embed/ACoJnY-WHy0?si=ZK3lNAr2LHbDWFg6"
                    title="YouTube video player" frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
            <div class="col-md-4  bg-white ">
                <h6 class="text-secondary mt-2 fw-bold" style="text-transform: uppercase;">Latest Devices</h6>
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
                <h6 style="border-left: 7px solid #EFEBE9 ; text-transform: uppercase;" class="mt-3 fw-bold my-4 px-2 text-secondary mt-2 d-inline mt-4">In Storeies
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
                <img class="mt-3 w-100" style="position: sticky; top: 0;"
                    src="https://fdn.gsmarena.com/imgroot/static/banners/self/review-iphone-16e-300x250.jpg" alt="">
            
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