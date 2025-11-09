<?php
// Public home page - no authentication required
require_once 'database_functions.php';
require_once 'phone_data.php';

$pdo = getConnection();
$brands_stmt = $pdo->prepare("
    SELECT * FROM brands
    ORDER BY name ASC
");
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll();

?>

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






                    </div>
                </div>

                <form action="" class="central d-flex align-items-center">
                    <input type="text" class="no-focus-border" placeholder="Search">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" height="24" width="24" class="ms-2">
                        <path fill="#ffffff"
                            d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z" />
                    </svg>
                </form>


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
            <?php
            $brandChunks = array_chunk($brands, 1); // Create chunks of 1 brand per row
            foreach ($brandChunks as $brandRow):
                foreach ($brandRow as $brand): ?>
                    <a href="#" class="brand-cell" data-brand-id="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></a>
            <?php endforeach;
            endforeach; ?>
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

            <div class="col-md-8 col-5  d-md-inline  ">
                <div class="comfort-life position-absolute d-lg-block d-none ">
                    <img class="w-100 h-100" src="imges/magnifient sectton.jpeg"
                        style="background-repeat: no-repeat; background-size: cover;" alt="">
                </div>
            </div>
            <div class="col-md-4 col-5 d-none d-lg-block" style="position: relative; left: 25px;">
                <button class="solid w-100 py-2">
                    <i class="fa-solid fa-mobile fa-sm mx-2" style="color: white;"></i>
                    Phone Finder</button>
                <div class="devor">
                    <?php
                    if (empty($brands)): ?>
                        <button class="px-3 py-1" style="cursor: default;" disabled>No brands available.</button>
                        <?php else:
                        $brandChunks = array_chunk($brands, 1); // Create chunks of 1 brand per row
                        foreach ($brandChunks as $brandRow):
                            foreach ($brandRow as $brand):
                        ?>
                                <button class="px-3 py-1 brand-cell" style="cursor: pointer;" data-brand-id="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></button>
                    <?php
                            endforeach;
                        endforeach;
                    endif;
                    ?>
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

    <div class="container bg-white mt-3 vwr">
        <div class="row">
            <div class="col-lg-6 col-12">
                <div class="filter-header">General</div>
                <div class="filter-container container">
                    <button style="border-radius: 1px;" class=" btn btn-toggle w-100 text-start mb-3" type="button"
                        data-bs-toggle="collapse" data-bs-target="#brandCollapse" aria-expanded="false"
                        aria-controls="brandCollapse">
                        Brand
                    </button>
                    <div class="collapse" id="brandCollapse">
                        <div class="card card-body py-2 px-3">
                            <?php
                            if (!empty($brands)) {
                                foreach ($brands as $brand) {
                                    $brandCheckboxId = 'brand' . $brand['id'];
                                    $brandName = htmlspecialchars($brand['name']);
                            ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="<?php echo $brand['id']; ?>"
                                            id="<?php echo $brandCheckboxId; ?>" name="brand[]" />
                                        <label class="form-check-label" for="<?php echo $brandCheckboxId; ?>"><?php echo $brandName; ?></label>
                                    </div>
                            <?php
                                }
                            } else {
                                echo '<p class="text-muted mb-0">No brands available</p>';
                            }
                            ?>
                        </div>
                    </div>
                    <button class="btn btn-toggle w-100 text-start mb-3" type="button" data-bs-toggle="collapse"
                        style="border-radius: 1px;" data-bs-target="#networkCollapse" aria-expanded="false"
                        aria-controls="networkCollapse">
                        Availability
                    </button>
                    <div class="collapse" id="networkCollapse">
                        <div class="card card-body py-2 px-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Available" id="availabilityAvailable"
                                    name="availability" />
                                <label class="form-check-label" for="availabilityAvailable">Available</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Coming Soon" id="availabilityComingSoon"
                                    name="availability" />
                                <label class="form-check-label" for="availabilityComingSoon">Coming Soon</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Discontinued" id="availabilityDiscontinued"
                                    name="availability" />
                                <label class="form-check-label" for="availabilityDiscontinued">Discontinued</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Rumored" id="availabilityRumored"
                                    name="availability" />
                                <label class="form-check-label" for="availabilityRumored">Rumored</label>
                            </div>
                        </div>
                    </div>
                    <div class="filter-header mx-1 mb-2">Sim</div>
                    <div class="row g-2">
                        <div class="col-6">
                            <button class=" btn btn-toggle w-100 text-start" type="button" data-bs-toggle="collapse"
                                data-bs-target="#simCollapse" aria-expanded="false" aria-controls="simCollapse"
                                style="border-radius: 1px;">
                                2G
                            </button>
                            <div class="collapse" id="simCollapse">
                                <div class="card card-body px-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="GSM 850" id="gsm850"
                                            name="networkBand" />
                                        <label class="form-check-label" for="gsm850">GSM 850</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="GSM 900" id="gsm900"
                                            name="networkBand" />
                                        <label class="form-check-label" for="gsm900">GSM 900</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="GSM 1800" id="gsm1800"
                                            name="networkBand" />
                                        <label class="form-check-label" for="gsm1800">GSM 1800</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="GSM 1900" id="gsm1900"
                                            name="networkBand" />
                                        <label class="form-check-label" for="gsm1900">GSM 1900</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <button class="btn btn-toggle w-100 text-start mb-3" type="button" data-bs-toggle="collapse"
                                data-bs-target="#simwasCollapse" aria-expanded="false" aria-controls="simwasCollapse"
                                style="border-radius: 1px;">
                                3G
                            </button>
                            <div class="collapse" id="simwasCollapse">
                                <div class="card card-body py-2 px-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="HSPA 850" id="hspa850"
                                            name="networkBand" />
                                        <label class="form-check-label" for="hspa850">HSPA 850</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="HSPA 900" id="hspa900"
                                            name="networkBand" />
                                        <label class="form-check-label" for="hspa900">HSPA 900</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="HSPA 1700" id="hspa1700"
                                            name="networkBand" />
                                        <label class="form-check-label" for="hspa1700">HSPA 1700</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="HSPA 1900" id="hspa1900"
                                            name="networkBand" />
                                        <label class="form-check-label" for="hspa1900">HSPA 1900</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="HSPA 2100" id="hspa2100"
                                            name="networkBand" />
                                        <label class="form-check-label" for="hspa2100">HSPA 2100</label>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 d-flex align-items-center justify-content-center">
                            <label class="btn w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                                <input type="checkbox" class="form-check-input me-2 float-end" name="dual_sim" value="1"> DUAL SIM
                            </label>
                        </div>
                        <div class="col-lg-6 d-flex align-items-center justify-content-center">
                            <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                                <input type="checkbox" class="form-check-input me-2 float-end" name="esim" value="1"> ESIM
                            </label>
                        </div>
                    </div>
                    <div class="filter-header mx-1 mb-3">BODY</div>
                    <button style="border-radius: 1px;" class="btn btn-toggle w-100 text-start" type="button"
                        data-bs-toggle="collapse" data-bs-target="#factorCollapse" aria-expanded="false"
                        aria-controls="factorCollapse">
                        Form Factor
                    </button>
                    <div class="collapse" id="factorCollapse">
                        <div class="card card-body px-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Bar" id="formFactorBar"
                                    name="form_factor" />
                                <label class="form-check-label" for="formFactorBar">Bar</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Flip up" id="formFactorFlipUp"
                                    name="form_factor" />
                                <label class="form-check-label" for="formFactorFlipUp">Flip up</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Flip down" id="formFactorFlipDown"
                                    name="form_factor" />
                                <label class="form-check-label" for="formFactorFlipDown">Flip down</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Swivel" id="formFactorSwivel"
                                    name="form_factor" />
                                <label class="form-check-label" for="formFactorSwivel">Swivel</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Slide" id="formFactorSlide"
                                    name="form_factor" />
                                <label class="form-check-label" for="formFactorSlide">Slide</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex fw-bolder align-items-center gap-3 mt-2"
                        style="border: 1px solid; padding: 7px; margin-top: 14px;">
                        Height: <span id="yearValue">min</span>
                        <input type="range" class="form-range custom-range flex-grow-1" min="2000" max="2025"
                            id="rangeYear">
                        <span class="text-muted">max</span>
                    </div>
                    <div class="d-flex fw-bolder align-items-center gap-3 mt-2"
                        style="border: 1px solid; padding: 7px; margin-top: 14px;">
                        Thickness: <span id="yearValue">min</span>
                        <input type="range" class="form-range custom-range flex-grow-1" min="2000" max="2025"
                            id="rangeYear">
                        <span class="text-muted">max</span>
                    </div>
                    <button style="border-radius: 1px;" class=" btn btn-toggle w-100 mt-2 text-start" type="button"
                        data-bs-toggle="collapse" data-bs-target="#ipCollapse" aria-expanded="false"
                        aria-controls="ipCollapse">
                        IP CERTIFICATE
                    </button>
                    <div class="collapse" id="ipCollapse">
                        <div class="card card-body px-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="IP54" id="ip54"
                                    name="ip_certificate" />
                                <label class="form-check-label" for="ip54">IP54</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="IP67" id="ip67"
                                    name="ip_certificate" />
                                <label class="form-check-label" for="ip67">IP67</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="IP68" id="ip68"
                                    name="ip_certificate" />
                                <label class="form-check-label" for="ip68">IP68</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="IP69K" id="ip69k"
                                    name="ip_certificate" />
                                <label class="form-check-label" for="ip69k">IP69K</label>
                            </div>
                        </div>
                    </div>
                    <button style="border-radius: 1px;" class=" btn btn-toggle w-100 mt-2 text-start" type="button"
                        data-bs-toggle="collapse" data-bs-target="#backCollapse" aria-expanded="false"
                        aria-controls="backCollapse">
                        Back Material
                    </button>
                    <div class="collapse" id="backCollapse">
                        <div class="card card-body px-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Mini-SIM" id="miniSim"
                                    name="size" />
                                <label class="form-check-label" for="miniSim"> Plastic</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Mini-SIM" id="miniSim"
                                    name="size" />
                                <label class="form-check-label" for="miniSim">Aluminum</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Nano-SIM" id="nanoSim"
                                    name="size" />
                                <label class="form-check-label" for="nanoSim">Glass</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Nano-SIM" id="nanoSim"
                                    name="size" />
                                <label class="form-check-label" for="nanoSim">Ceramic</label>
                            </div>
                        </div>
                    </div>
                    <div class="filter-header mx-1 mb-2">Platforms</div>
                    <button style="border-radius: 1px;" class="btn btn-toggle w-100 mt-2 text-start" type="button"
                        data-bs-toggle="collapse" data-bs-target="#osCollapse" aria-expanded="false"
                        aria-controls="osCollapse">
                        OS: </button>
                    <div class="collapse" id="osCollapse">
                        <div class="card card-body px-3">
                            <div class="row g-2">
                                <div class="col-12">Select OS family</div>
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="android" id="osAndroid" name="os_family" />
                                        <label class="form-check-label" for="osAndroid">Android</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="ios" id="osIOS" name="os_family" />
                                        <label class="form-check-label" for="osIOS">iOS</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="other" id="osOther" name="os_family" />
                                        <label class="form-check-label" for="osOther">Other (not Android/iOS)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button style="border-radius: 1px;" class=" btn btn-toggle w-100 mt-2 text-start" type="button"
                        data-bs-toggle="collapse" data-bs-target="#chipsCollapse" aria-expanded="false"
                        aria-controls="chipsCollapse">
                        CHIPSET: </button>
                    <div class="collapse" id="chipsCollapse">
                        <div class="card card-body px-3">
                            <label for="chipsetQuery" class="form-label">Chipset contains</label>
                            <input type="text" id="chipsetQuery" class="form-control" placeholder="e.g. Snapdragon 8 Gen, A18, Dimensity 9300" />
                        </div>
                    </div>

                    <div class="filter-header mx-1 mb-4">SENSORS</div>
                    <div class="row">
                        <div class="col-lg-6 d-flex align-items-center justify-content-center">
                            <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                                <input type="checkbox" class="form-check-input me-2 float-end" name="accelerometer" value="1"> ACCELEROMETER
                            </label>
                        </div>
                        <div class="col-lg-6 d-flex align-items-center justify-content-center">
                            <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                                <input type="checkbox" class="form-check-input me-2 float-end" name="gyro" value="1"> GYRO
                            </label>
                        </div>
                    </div>
                    <div class="row ">
                        <div class="col-lg-6 d-flex align-items-center mt-2 justify-content-center">
                            <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                                <input type="checkbox" class="form-check-input me-2 float-end" name="barometer" value="1"> BAROMETER
                            </label>
                        </div>
                        <div class="col-lg-6 d-flex align-items-center mt-2 justify-content-center">
                            <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                                <input type="checkbox" class="form-check-input me-2 float-end" name="heart_rate" value="1"> HEART RATE
                            </label>
                        </div>
                    </div>
                    <div class="filter-header mx-1 mb-2">Memory</div>
                    <!-- RAM filter (Min GB) -->
                    <div class="d-flex fw-bolder align-items-center gap-3 mt-3"
                        style="border: 1px solid; padding: 7px; margin-top: 14px;">
                        Min RAM: <span id="ramMinValue">Any</span>
                        <input type="range" class="form-range custom-range flex-grow-1" min="0" max="32" step="1"
                            id="ramMin" value="0">
                        <span class="text-muted">GB</span>
                    </div>
                    <button style="border-radius: 1px;" class=" btn btn-toggle w-100 mt-2 text-start" type="button"
                        data-bs-toggle="collapse" data-bs-target="#cardCollapse" aria-expanded="false"
                        aria-controls="cardCollapse">
                        Card Slot: </button>
                    <div class="collapse" id="cardCollapse">
                        <div class="card card-body px-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="cardSlotRequired" />
                                <label class="form-check-label" for="cardSlotRequired">Require card slot</label>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-lg-6 col-12 pt-3">
                <!-- Year filter (Min-Max) -->
                <div class="d-flex fw-bolder align-items-center gap-3 mt-4"
                    style="border: 1px solid; padding: 7px; margin-top: 14px;">
                    Year: <span id="yearMinValue">2000</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="2000" max="2026"
                        id="yearMin" value="2000">
                    <span class="mx-2">-</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="2000" max="2026"
                        id="yearMax" value="2026">
                    <span id="yearMaxValue">2026</span>
                </div>
                <!-- Price filter (Max price) -->
                <div class="d-flex fw-bolder align-items-center gap-3 mt-4"
                    style="border: 1px solid; padding: 7px; margin-top: 14px;">
                    Max Price: <span id="priceMaxValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="0" max="3000" step="10"
                        id="priceMax">
                    <span class="text-muted">USD</span>
                </div>
                <div class="row g-2 mt-5">
                    <div class="col-6 mt-3">
                        <button class=" btn btn-toggle w-100 text-start" type="button" data-bs-toggle="collapse"
                            data-bs-target="#fourGCollapse" aria-expanded="false" aria-controls="fourGCollapse"
                            style="border-radius: 1px;">
                            4G
                        </button>
                        <div class="collapse" id="fourGCollapse">
                            <div class="card card-body px-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="LTE 700" id="lte700"
                                        name="networkBand" />
                                    <label class="form-check-label" for="lte700">LTE 700</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="LTE 850" id="lte850"
                                        name="networkBand" />
                                    <label class="form-check-label" for="lte850">LTE 850</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="LTE 900" id="lte900"
                                        name="networkBand" />
                                    <label class="form-check-label" for="lte900">LTE 900</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="LTE 1800" id="lte1800"
                                        name="networkBand" />
                                    <label class="form-check-label" for="lte1800">LTE 1800</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 mt-3">
                        <button class=" btn btn-toggle w-100 text-start" type="button" data-bs-toggle="collapse"
                            data-bs-target="#fiveGCollapse" aria-expanded="false" aria-controls="fiveGCollapse"
                            style="border-radius: 1px;">
                            5G
                        </button>
                        <div class="collapse" id="fiveGCollapse">
                            <div class="card card-body px-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="NR 3500" id="nr3500"
                                        name="networkBand" />
                                    <label class="form-check-label" for="nr3500">NR 3500</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="NR 3600" id="nr3600"
                                        name="networkBand" />
                                    <label class="form-check-label" for="nr3600">NR 3600</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="NR 3700" id="nr3700"
                                        name="networkBand" />
                                    <label class="form-check-label" for="nr3700">NR 3700</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="NR 3800" id="nr3800"
                                        name="networkBand" />
                                    <label class="form-check-label" for="nr3800">NR 3800</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button style="border-radius: 1px;" class=" btn btn-toggle mt-3 w-100 text-start" type="button"
                    data-bs-toggle="collapse" data-bs-target="#sizeCollapse" aria-expanded="false"
                    aria-controls="sizeCollapse">
                    SIZE
                </button>
                <div class="collapse" id="sizeCollapse">
                    <div class="card card-body px-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Mini-SIM" id="miniSim" name="sim_size" />
                            <label class="form-check-label" for="miniSim">Mini-SIM (regular size)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Nano-SIM" id="nanoSim" name="sim_size" />
                            <label class="form-check-label" for="nanoSim">Nano-SIM</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Micro-SIM" id="microSim"
                                name="sim_size" />
                            <label class="form-check-label" for="microSim">Micro-SIM</label>
                        </div>
                    </div>
                </div>
                <button style="border-radius: 1px;" class=" btn btn-toggle w-100 mt-5 text-start" type="button"
                    data-bs-toggle="collapse" data-bs-target="#KeyWordsCollapse" aria-expanded="false"
                    aria-controls="KeyWordsCollapse">
                    KeyWords
                </button>
                <div class="collapse" id="KeyWordsCollapse">
                    <div class="card card-body px-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Mini-SIM" id="miniSim" name="size" />
                            <label class="form-check-label" for="miniSim"> Without QWERTY</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Nano-SIM" id="nanoSim" name="size" />
                            <label class="form-check-label" for="nanoSim">With QWERTY</label>
                        </div>

                    </div>
                </div>
                <div class="d-flex align-items-center fw-bolder gap-3 mt-2"
                    style="border: 1px solid; padding: 7px; margin-top: 14px;">
                    Width: <span id="yearValue">min</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="2000" max="2025"
                        id="rangeYear">
                    <span class="text-muted">max</span>
                </div>
                <div class="d-flex align-items-center fw-bolder gap-3 mt-2"
                    style="border: 1px solid; padding: 7px; margin-top: 14px;">
                    Weight: <span id="yearValue">min</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="2000" max="2025"
                        id="rangeYear">
                    <span class="text-muted">max</span>
                </div>
                <button style="border-radius: 1px;" class="btn btn-toggle w-100 mt-2 text-start" type="button"
                    data-bs-toggle="collapse" data-bs-target="#colorCollapse" aria-expanded="false"
                    aria-controls="colorCollapse">
                    Color
                </button>
                <div class="collapse" id="colorCollapse">
                    <div class="card card-body px-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Mini-SIM" id="miniSim" name="size" />
                            <label class="form-check-label" for="miniSim"> RED</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Mini-SIM" id="miniSim" name="size" />
                            <label class="form-check-label" for="miniSim">WHITE</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Nano-SIM" id="nanoSim" name="size" />
                            <label class="form-check-label" for="nanoSim">BLACK</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Nano-SIM" id="nanoSim" name="size" />
                            <label class="form-check-label" for="nanoSim">BLUE</label>
                        </div>
                    </div>
                </div>
                <button style="border-radius: 1px;" class="btn btn-toggle w-100 mt-2 text-start" type="button"
                    data-bs-toggle="collapse" data-bs-target="#frontCollapse" aria-expanded="false"
                    aria-controls="frontCollapse">
                    Frame Material
                </button>
                <div class="collapse" id="frontCollapse">
                    <div class="card card-body px-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Mini-SIM" id="miniSim" name="size" />
                            <label class="form-check-label" for="miniSim"> Plastic</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Mini-SIM" id="miniSim" name="size" />
                            <label class="form-check-label" for="miniSim">Aluminum</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Nano-SIM" id="nanoSim" name="size" />
                            <label class="form-check-label" for="nanoSim">Stainless steel</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Nano-SIM" id="nanoSim" name="size" />
                            <label class="form-check-label" for="nanoSim">Ceramic Titanium</label>
                        </div>
                    </div>
                </div>
                <button style="border-radius: 1px;" class=" btn btn-toggle w-100 mt-5 text-start" type="button"
                    data-bs-toggle="collapse" data-bs-target="#iosCollapse" aria-expanded="false"
                    aria-controls="iosCollapse">
                    MIN OS VERSION: </button>
                <div class="collapse" id="iosCollapse">
                    <div class="card card-body px-3">
                        <div class="d-flex fw-bolder align-items-center gap-3">
                            Min OS Version: <span id="osVersionMinValue">Any</span>
                            <input type="range" class="form-range custom-range flex-grow-1" min="0" max="20" step="1"
                                id="osVersionMin" value="0">
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center fw-bolder gap-3 mt-2"
                    style="border: 1px solid; padding: 7px; margin-top: 14px;">
                    CPU: <span id="yearValue">min</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="2000" max="2025"
                        id="rangeYear">
                    <span class="text-muted">max</span>
                </div>

                <div class="row mt-5">
                    <div class="col-lg-6 d-flex align-items-center mt-3 justify-content-center">
                        <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="compass" value="1"> COMPASS
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center mt-3 justify-content-center">
                        <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="proximity" value="1"> PROXIMITY
                        </label>
                    </div>
                </div>
                <button style="border-radius: 1px;" class=" btn btn-toggle w-100 mt-2 mb-3 text-start" type="button"
                    data-bs-toggle="collapse" data-bs-target="#fingerCollapse" aria-expanded="false"
                    aria-controls="fingerCollapse">
                    Fingerprint
                </button>
                <div class="collapse" id="fingerCollapse">
                    <div class="card card-body px-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Mini-SIM" id="miniSim" name="size" />
                            <label class="form-check-label" for="miniSim"> Yes (any type) </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Mini-SIM" id="miniSim" name="size" />
                            <label class="form-check-label" for="miniSim">Rear-mounted</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Nano-SIM" id="nanoSim" name="size" />
                            <label class="form-check-label" for="nanoSim">Side-mounted</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Nano-SIM" id="nanoSim" name="size" />
                            <label class="form-check-label" for="nanoSim">Top-mounted Under display</label>
                        </div>
                    </div>
                </div>
                <!-- Storage filter (Min GB) -->
                <div class="d-flex fw-bolder align-items-center gap-3 mt-5"
                    style="border: 1px solid; padding: 7px; margin-top: 14px;">
                    Min Storage: <span id="storageMinValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="0" max="2048" step="16"
                        id="storageMin" value="0">
                    <span class="text-muted">GB</span>
                </div>
            </div>
            <div class="filter-header">Display</div>
            <div class="d-flex secondary fw-bolder align-items-center gap-3 mt-2">
                Resolution: <span id="yearValue">min</span>
                <input type="range" class="form-range custom-range flex-grow-1" min="2000" max="2025" id="rangeYear">
                <span class="text-muted">max</span>
            </div>
        </div>
        <div class="row gx-4 gy-3 crs">
            <div class="col-lg-6 mt-3 py-3">
                <!-- Display Size filter (Min-Max inches) -->
                <div class="filter-box ">
                    <span class="filter-label">Size:</span>
                    <span id="displaySizeMinValue">3.0</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="3.0" max="8.0" step="0.1"
                        id="displaySizeMin" value="3.0" />
                    <span class="mx-2">-</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="3.0" max="8.0" step="0.1"
                        id="displaySizeMax" value="8.0" />
                    <span id="displaySizeMaxValue">8.0</span>
                    <span class="text-muted">"</span>
                </div>
                <button class="btn  btn-toggle w-100  mb-3 mt-2" type="button" data-bs-toggle="collapse"
                    data-bs-target="#techCollapse" aria-expanded="false" aria-controls="techCollapse">
                    Technology
                </button>
                <div class="collapse" id="techCollapse">
                    <div class="card card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Apple" id="brandApple"
                                name="brand" />
                            <label class="form-check-label" for="brandApple">IPS</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Samsung" id="brandSamsung"
                                name="brand" />
                            <label class="form-check-label" for="brandSamsung">OLED</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Xiaomi" id="brandXiaomi"
                                name="brand" />
                            <label class="form-check-label" for="brandXiaomi">LTPO OLED</label>
                        </div>

                    </div>
                </div>
                <div class="filter-box">
                    <span class="filter-label">Refresh Rate:</span>
                    <span id="sizeValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="2000" max="2025"
                        id="rangeSize" />
                </div>
                <div class="filter-header mt-4 mb-3" style="margin-left: -1px;">Main Camera</div>
                <div class="filter-box  ">
                    <span class="filter-label ">Resolution</span>
                    <span id="mainCamMpMinValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="0" max="200" step="1"
                        id="mainCamMpMin" />
                    <span class="text-muted">MP</span>
                </div>
                <div class="filter-box mt-1 ">
                    <span class="filter-label ">F-NUMBER</span>
                    <span id="sizeValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="2000" max="2025"
                        id="rangeSize" />
                </div>
                <div class="filter-box mt-1">
                    <span class="filter-label ">VIDEO</span>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="video4k" />
                                <label class="form-check-label" for="video4k">4K</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="video8k" />
                                <label class="form-check-label" for="video8k">8K</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="filter-header my-3" style="margin-left: -1px;">Selfie Camera</div>
                <div class="filter-box ">
                    <span class="filter-label ">Resolution</span>
                    <span id="sizeValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="2000" max="2025"
                        id="rangeSize" />
                </div>
                <div class="row ">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2">
                        <label class="btn   w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="selfie_camera_flash" value="1"> FRONT FLASH
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2">
                        <label class="btn w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="popup_camera" value="1"> POP-UP CAMERA
                        </label>
                    </div>
                </div>
                <div class="filter-header mt-3 mb-3 fs-5 " style="margin-left: -1px;">CONNECTIVITY</div>
                <button class="btn  btn-toggle w-100" type="button" data-bs-toggle="collapse"
                    data-bs-target="#wlanCollapse" aria-expanded="false" aria-controls="wlanCollapse">
                    WLAN(WI-FI)
                </button>
                <div class="collapse" id="wlanCollapse">
                    <div class="card card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Apple" id="brandApple"
                                name="brand" />
                            <label class="form-check-label" for="brandApple">Wi-Fi 4 (802.11n)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Samsung" id="brandSamsung"
                                name="brand" />
                            <label class="form-check-label" for="brandSamsung">Wi-Fi 5 (802.11ac)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Xiaomi" id="brandXiaomi"
                                name="brand" />
                            <label class="form-check-label" for="brandXiaomi">Wi-Fi 6 (802.11ax)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Xiaomi" id="brandXiaomi"
                                name="brand" />
                            <label class="form-check-label" for="brandXiaomi">Wi-Fi 7 (802.11be)</label>
                        </div>

                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="gps" value="1"> GPS
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="nfc" value="1"> NFC
                        </label>
                    </div>
                </div>
                <button class="btn  btn-toggle w-100  mb-3 mt-1" type="button" data-bs-toggle="collapse"
                    data-bs-target="#usbCollapse" aria-expanded="false" aria-controls="usbCollapse">
                    USB
                </button>
                <div class="collapse " id="usbCollapse">
                    <div class="card card-body">

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Xiaomi" id="brandXiaomi"
                                name="brand" />
                            <label class="form-check-label" for="brandXiaomi">Any USB-C </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Xiaomi" id="brandXiaomi"
                                name="brand" />
                            <label class="form-check-label" for="brandXiaomi">USB-C 3.0 and higher</label>
                        </div>

                    </div>
                </div>
                <div class="filter-header mt-1 mb-2" style="margin-left: -1px;">Battery</div>
                <div class="filter-box ">
                    <span class="filter-label ">Capacity</span>
                    <span id="batteryCapacityMinValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="0" max="10000" step="100"
                        id="batteryCapacityMin" />
                    <span class="text-muted">mAh</span>
                </div>
                <div class="filter-box mt-1">
                    <span class="filter-label">Wired Charging:</span>
                    <span id="wiredChargeMinValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="0" max="300" step="5"
                        id="wiredChargeMin" />
                    <span class="text-muted">W</span>
                </div>

                <div class="filter-header mt-3 mb-3" style="margin-left: -1px;">Misc</div>
                <div class="filter-box">
                    <span class="filter-label">Free TExt</span>

                    <input type="text" class=" flex-grow-1 life" />

                </div>

                <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2 mx-1 ">
                    <label class="btn w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                        <input type="checkbox" class="form-check-input me-2 float-end"> Rewiew Only
                    </label>
                </div>

                <div class="filter-header mT-3 mb-2" style="margin-left: -1px;">Audio</div>
                <div class="row ">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2">
                        <label class="btn   w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="headphone_jack" value="1"> 3.5MM JACK
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2">
                        <label class="btn w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="dual_speakers" value="1"> DUAL SPEAKER
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mt-3 py-3">
                <div class="filter-box ">
                    <span class="filter-label">Size:</span>
                    <span id="sizeValue">min</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="2000" max="2025"
                        id="rangeSize" />
                    <span class="text-muted">max</span>
                </div>
                <button class="btn  w-100 btn-toggle  mb-3 mt-2" type="button" data-bs-toggle="collapse"
                    data-bs-target="#notchCollapse" aria-expanded="false" aria-controls="notchCollapse">
                    Notch
                </button>
                <div class="collapse" id="notchCollapse">
                    <div class="card card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Apple" id="brandApple"
                                name="brand" />
                            <label class="form-check-label" for="brandApple">No</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Samsung" id="brandSamsung"
                                name="brand" />
                            <label class="form-check-label" for="brandSamsung">Yes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Xiaomi" id="brandXiaomi"
                                name="brand" />
                            <label class="form-check-label" for="brandXiaomi">Punch hole</label>
                        </div>

                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="hdr" value="1"> HDR
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="billion_colors" value="1"> 1B+COLORS
                        </label>
                    </div>
                </div>
                <div class="row mt-5">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2">
                        <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end"> Camreas
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2">
                        <label class="btn w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end"> IOS
                        </label>
                    </div>
                </div>
                <div class="row ">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2">
                        <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="main_camera_telephoto" value="1"> TELEPHOTO
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2">
                        <label class="btn w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="main_camera_ultrawide" value="1"> ULTRAWIDE
                        </label>
                    </div>
                </div>
                <button class="btn  w-100 btn-toggle  mb-3 mt-1" type="button" data-bs-toggle="collapse"
                    data-bs-target="#flashCollapse" aria-expanded="false" aria-controls="flashCollapse">
                    FLASH
                </button>
                <div class="collapse" id="flashCollapse">
                    <div class="card card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Apple" id="brandApple"
                                name="brand" />
                            <label class="form-check-label" for="brandApple">LED</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Samsung" id="brandSamsung"
                                name="brand" />
                            <label class="form-check-label" for="brandSamsung">Dual-LED</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Xiaomi" id="brandXiaomi"
                                name="brand" />
                            <label class="form-check-label" for="brandXiaomi">Xenon</label>
                        </div>

                    </div>
                </div>
                <div class="row mt-1">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-3">
                        <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end"> DUAL CAMERA
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-3">
                        <label class="btn w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="main_camera_ois" value="1"> OIS
                        </label>
                    </div>
                </div>
                <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2 ">
                    <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                        <input type="checkbox" class="form-check-input me-2 float-end" name="under_display_camera" value="1"> UNDER DISPLAY CAMERA
                    </label>
                </div>
                <button class="btn  btn-toggle w-100  mb-3 mt-5" type="button" data-bs-toggle="collapse"
                    data-bs-target="#bluetoothCollapse" aria-expanded="false" aria-controls="bluetoothCollapse">
                    BLUETOOTH
                </button>
                <div class="collapse " id="bluetoothCollapse">
                    <div class="card card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Apple" id="brandApple"
                                name="brand" />
                            <label class="form-check-label" for="brandApple">Any Bluetooth</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Samsung" id="brandSamsung"
                                name="brand" />
                            <label class="form-check-label" for="brandSamsung">Bluetooth 4.0</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Xiaomi" id="brandXiaomi"
                                name="brand" />
                            <label class="form-check-label" for="brandXiaomi">Bluetooth 4.1</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Xiaomi" id="brandXiaomi"
                                name="brand" />
                            <label class="form-check-label" for="brandXiaomi">Bluetooth 4.2</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Xiaomi" id="brandXiaomi"
                                name="brand" />
                            <label class="form-check-label" for="brandXiaomi">Bluetooth 5.1</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Xiaomi" id="brandXiaomi"
                                name="brand" />
                            <label class="form-check-label" for="brandXiaomi">Bluetooth 5.2</label>
                        </div>

                    </div>
                </div>
                <div class="row " style="margin-top: -7px;">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="infrared" value="1">INFRARED
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="fm_radio" value="1"> FM-RADIO
                        </label>
                    </div>
                </div>
                <div class="row styled">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center ">
                        <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="battery_sic" value="1"> SI/C
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center ">
                        <label class="btn w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="battery_removable" value="1"> REMOVABLE
                        </label>
                    </div>
                </div>
                <div class="filter-box mt-1">
                    <span class="filter-label">Wireless Charging:</span>
                    <div class="row g-2 align-items-center">
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="wirelessRequired" />
                                <label class="form-check-label" for="wirelessRequired">Require wireless charging</label>
                            </div>
                        </div>
                        <div class="col-12 d-flex fw-bolder align-items-center gap-3">
                            Min: <span id="wirelessChargeMinValue">Any</span>
                            <input type="range" class="form-range custom-range flex-grow-1" min="0" max="100" step="5"
                                id="wirelessChargeMin" />
                            <span class="text-muted">W</span>
                        </div>
                    </div>
                </div>
                <button class="btn  btn-toggle w-100  mb-3 mt-5" type="button" data-bs-toggle="collapse"
                    data-bs-target="#popularCollapse" aria-expanded="false" aria-controls="popularCollapse">
                    ORDERS
                </button>
                <div class="collapse " id="popularCollapse">
                    <div class="card card-body">

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Xiaomi" id="brandXiaomi"
                                name="brand" />
                            <label class="form-check-label" for="brandXiaomi">Popularity </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Xiaomi" id="brandXiaomi"
                                name="brand" />
                            <label class="form-check-label" for="brandXiaomi">Price</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Xiaomi" id="brandXiaomi"
                                name="brand" />
                            <label class="form-check-label" for="brandXiaomi">Camera resolution Battery capacity</label>
                        </div>

                    </div>
                </div>


            </div>
        </div>
        <div class="row mt-4">
            <p class="celecon">*Price based on the lowest online SIM-free price, excluding taxes, subsidies and
                shipment. Only phones with known prices
                will appear in the results.</p>
            <p class="celecon mt-4">*In Free text field you can search for other features, not mentioned above. For
                example - "120Hz", "macro", "periscope",
                "reverse wireless", "Gorilla Glass 5", "GALILEO", "aptX" and so on. In some cases it can be very useful,
                but the results
                are less reliable.</p>
            <img class="volunteer text-center m-auto d-flex align-items-center justify-content-between "
                src="https://fdn.gsmarena.com/imgroot/static/banners/self/nordvpn-728x90-25.gif" alt="">
        </div>
        <!-- Find button row inserted -->
        <div class="row mt-4">
            <div class="col-12">
                <button type="button" id="findDevicesBtn" class="btn btn-danger w-100 py-3 fw-bold">
                    <i class="fa fa-search me-2"></i> Find Devices
                </button>
            </div>
        </div>

        <!-- Results section -->
        <div class="row mt-4" id="resultsSection" style="display: none;">
            <div class="col-12">
                <div class="alert alert-info">
                    <strong id="resultsCount">0</strong> devices found
                </div>
            </div>
        </div>

        <div class="row" id="resultsContainer">
            <!-- Results will be dynamically inserted here -->
        </div>
    </div>

    <!-- Phonefinder AJAX Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const findBtn = document.getElementById('findDevicesBtn');
            const resultsSection = document.getElementById('resultsSection');
            const resultsContainer = document.getElementById('resultsContainer');
            const resultsCount = document.getElementById('resultsCount');

            // Price slider elements
            const priceMaxInput = document.getElementById('priceMax');
            const priceMaxValue = document.getElementById('priceMaxValue');

            // Year slider elements
            const yearMinInput = document.getElementById('yearMin');
            const yearMinValue = document.getElementById('yearMinValue');
            const yearMaxInput = document.getElementById('yearMax');
            const yearMaxValue = document.getElementById('yearMaxValue');

            // RAM slider elements
            const ramMinInput = document.getElementById('ramMin');
            const ramMinValue = document.getElementById('ramMinValue');

            // Storage slider elements
            const storageMinInput = document.getElementById('storageMin');
            const storageMinValue = document.getElementById('storageMinValue');

            // Display size slider elements
            const displaySizeMinInput = document.getElementById('displaySizeMin');
            const displaySizeMinValue = document.getElementById('displaySizeMinValue');
            const displaySizeMaxInput = document.getElementById('displaySizeMax');
            const displaySizeMaxValue = document.getElementById('displaySizeMaxValue');

            // B1 new controls
            const osVersionMinInput = document.getElementById('osVersionMin');
            const osVersionMinValue = document.getElementById('osVersionMinValue');
            const chipsetQueryInput = document.getElementById('chipsetQuery');
            const cardSlotRequiredInput = document.getElementById('cardSlotRequired');
            const mainCamMpMinInput = document.getElementById('mainCamMpMin');
            const mainCamMpMinValue = document.getElementById('mainCamMpMinValue');
            const video4kInput = document.getElementById('video4k');
            const video8kInput = document.getElementById('video8k');
            const batteryCapacityMinInput = document.getElementById('batteryCapacityMin');
            const batteryCapacityMinValue = document.getElementById('batteryCapacityMinValue');
            const wiredChargeMinInput = document.getElementById('wiredChargeMin');
            const wiredChargeMinValue = document.getElementById('wiredChargeMinValue');
            const wirelessRequiredInput = document.getElementById('wirelessRequired');
            const wirelessChargeMinInput = document.getElementById('wirelessChargeMin');
            const wirelessChargeMinValue = document.getElementById('wirelessChargeMinValue');
            const osFamilyInputs = document.querySelectorAll('input[name="os_family"]');

            // Price slider handler
            if (priceMaxInput && priceMaxValue) {
                const maxPrice = parseInt(priceMaxInput.max, 10);
                const formatUsd = (v) => {
                    const num = parseInt(v, 10);
                    if (isNaN(num) || num <= 0 || num >= maxPrice) return 'Any';
                    return '$' + num.toLocaleString();
                };
                priceMaxValue.textContent = formatUsd(priceMaxInput.value);
                priceMaxInput.addEventListener('input', function() {
                    priceMaxValue.textContent = formatUsd(this.value);
                });
            }

            // Year slider handlers
            if (yearMinInput && yearMinValue) {
                yearMinValue.textContent = yearMinInput.value;
                yearMinInput.addEventListener('input', function() {
                    yearMinValue.textContent = this.value;
                    // Ensure min <= max
                    if (parseInt(this.value) > parseInt(yearMaxInput.value)) {
                        yearMaxInput.value = this.value;
                        yearMaxValue.textContent = this.value;
                    }
                });
            }
            if (yearMaxInput && yearMaxValue) {
                yearMaxValue.textContent = yearMaxInput.value;
                yearMaxInput.addEventListener('input', function() {
                    yearMaxValue.textContent = this.value;
                    // Ensure max >= min
                    if (parseInt(this.value) < parseInt(yearMinInput.value)) {
                        yearMinInput.value = this.value;
                        yearMinValue.textContent = this.value;
                    }
                });
            }

            // RAM slider handler
            if (ramMinInput && ramMinValue) {
                ramMinValue.textContent = ramMinInput.value == 0 ? 'Any' : ramMinInput.value + ' GB';
                ramMinInput.addEventListener('input', function() {
                    ramMinValue.textContent = this.value == 0 ? 'Any' : this.value + ' GB';
                });
            }

            // Storage slider handler
            if (storageMinInput && storageMinValue) {
                storageMinValue.textContent = storageMinInput.value == 0 ? 'Any' : storageMinInput.value + ' GB';
                storageMinInput.addEventListener('input', function() {
                    const val = parseInt(this.value);
                    if (val == 0) {
                        storageMinValue.textContent = 'Any';
                    } else if (val >= 1024) {
                        storageMinValue.textContent = (val / 1024) + ' TB';
                    } else {
                        storageMinValue.textContent = val + ' GB';
                    }
                });
            }

            // Display size slider handlers
            if (displaySizeMinInput && displaySizeMinValue) {
                displaySizeMinValue.textContent = parseFloat(displaySizeMinInput.value).toFixed(1);
                displaySizeMinInput.addEventListener('input', function() {
                    displaySizeMinValue.textContent = parseFloat(this.value).toFixed(1);
                    // Ensure min <= max
                    if (parseFloat(this.value) > parseFloat(displaySizeMaxInput.value)) {
                        displaySizeMaxInput.value = this.value;
                        displaySizeMaxValue.textContent = parseFloat(this.value).toFixed(1);
                    }
                });
            }
            if (displaySizeMaxInput && displaySizeMaxValue) {
                displaySizeMaxValue.textContent = parseFloat(displaySizeMaxInput.value).toFixed(1);
                displaySizeMaxInput.addEventListener('input', function() {
                    displaySizeMaxValue.textContent = parseFloat(this.value).toFixed(1);
                    // Ensure max >= min
                    if (parseFloat(this.value) < parseFloat(displaySizeMinInput.value)) {
                        displaySizeMinInput.value = this.value;
                        displaySizeMinValue.textContent = parseFloat(this.value).toFixed(1);
                    }
                });
            }

            // OS Version slider handler
            if (osVersionMinInput && osVersionMinValue) {
                const updateOsVer = (v) => v == 0 ? 'Any' : '≥ ' + v;
                osVersionMinValue.textContent = updateOsVer(osVersionMinInput.value);
                osVersionMinInput.addEventListener('input', function() {
                    osVersionMinValue.textContent = updateOsVer(this.value);
                });
            }

            // Main camera MP handler
            if (mainCamMpMinInput && mainCamMpMinValue) {
                const upd = (v) => v == 0 ? 'Any' : '≥ ' + v + 'MP';
                mainCamMpMinValue.textContent = upd(mainCamMpMinInput.value);
                mainCamMpMinInput.addEventListener('input', function() {
                    mainCamMpMinValue.textContent = upd(this.value);
                });
            }

            // Battery capacity handler
            if (batteryCapacityMinInput && batteryCapacityMinValue) {
                const upd = (v) => v == 0 ? 'Any' : '≥ ' + v + ' mAh';
                batteryCapacityMinValue.textContent = upd(batteryCapacityMinInput.value);
                batteryCapacityMinInput.addEventListener('input', function() {
                    batteryCapacityMinValue.textContent = upd(this.value);
                });
            }

            // Wired charging handler
            if (wiredChargeMinInput && wiredChargeMinValue) {
                const upd = (v) => v == 0 ? 'Any' : '≥ ' + v + ' W';
                wiredChargeMinValue.textContent = upd(wiredChargeMinInput.value);
                wiredChargeMinInput.addEventListener('input', function() {
                    wiredChargeMinValue.textContent = upd(this.value);
                });
            }

            // Wireless charging handler
            if (wirelessChargeMinInput && wirelessChargeMinValue) {
                const upd = (v) => v == 0 ? 'Any' : '≥ ' + v + ' W';
                wirelessChargeMinValue.textContent = upd(wirelessChargeMinInput.value);
                wirelessChargeMinInput.addEventListener('input', function() {
                    wirelessChargeMinValue.textContent = upd(this.value);
                });
            }

            // Utility: reset all filters to default (no filter)
            function resetAllFilters() {
                // Uncheck all checkboxes
                document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.checked = false;
                });
                // Reset all sliders to min/default
                if (priceMaxInput) priceMaxInput.value = priceMaxInput.min;
                if (yearMinInput) yearMinInput.value = yearMinInput.min;
                if (yearMaxInput) yearMaxInput.value = yearMaxInput.max;
                if (ramMinInput) ramMinInput.value = ramMinInput.min;
                if (storageMinInput) storageMinInput.value = storageMinInput.min;
                if (displaySizeMinInput) displaySizeMinInput.value = displaySizeMinInput.min;
                if (displaySizeMaxInput) displaySizeMaxInput.value = displaySizeMaxInput.max;
                if (osVersionMinInput) osVersionMinInput.value = osVersionMinInput.min;
                if (mainCamMpMinInput) mainCamMpMinInput.value = mainCamMpMinInput.min;
                if (batteryCapacityMinInput) batteryCapacityMinInput.value = batteryCapacityMinInput.min;
                if (wiredChargeMinInput) wiredChargeMinInput.value = wiredChargeMinInput.min;
                if (wirelessChargeMinInput) wirelessChargeMinInput.value = wirelessChargeMinInput.min;
                if (chipsetQueryInput) chipsetQueryInput.value = '';
                // Trigger UI updates for labels
                if (typeof Event === 'function') {
                    [priceMaxInput, yearMinInput, yearMaxInput, ramMinInput, storageMinInput, displaySizeMinInput, displaySizeMaxInput, osVersionMinInput, mainCamMpMinInput, batteryCapacityMinInput, wiredChargeMinInput, wirelessChargeMinInput].forEach(inp => {
                        if (inp) inp.dispatchEvent(new Event('input'));
                    });
                }
            }

            // On page load, reset all filters
            resetAllFilters();

            // Optionally, add a Reset Filters button
            if (!document.getElementById('resetFiltersBtn')) {
                const btn = document.createElement('button');
                btn.id = 'resetFiltersBtn';
                btn.className = 'btn btn-secondary w-100 my-2';
                btn.innerHTML = '<i class="fa fa-undo me-2"></i>Reset Filters';
                btn.onclick = function(e) {
                    e.preventDefault();
                    resetAllFilters();
                };
                findBtn.parentNode.insertBefore(btn, findBtn);
            }

            findBtn.addEventListener('click', function() {
                // Show loading state
                findBtn.disabled = true;
                findBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i> Searching...';

                // Gather selected brands
                const selectedBrands = [];
                document.querySelectorAll('input[name="brand[]"]:checked').forEach(function(checkbox) {
                    selectedBrands.push(checkbox.value);
                });

                // Gather selected availability statuses
                const selectedAvailability = [];
                document.querySelectorAll('input[name="availability"]:checked').forEach(function(checkbox) {
                    selectedAvailability.push(checkbox.value);
                });

                // Prepare form data
                const formData = new FormData();
                selectedBrands.forEach(function(brandId) {
                    formData.append('brands[]', brandId);
                });
                selectedAvailability.forEach(function(status) {
                    formData.append('availability[]', status);
                });

                // Append price max if provided (> 0 and < max)
                if (priceMaxInput) {
                    const val = parseInt(priceMaxInput.value, 10);
                    const maxPrice = parseInt(priceMaxInput.max, 10);
                    if (!isNaN(val) && val > 0 && val < maxPrice) {
                        formData.append('price_max', val);
                    }
                }

                // Append year range if not default
                if (yearMinInput && yearMaxInput) {
                    const minYear = parseInt(yearMinInput.value);
                    const maxYear = parseInt(yearMaxInput.value);
                    if (minYear > 2000) formData.append('year_min', minYear);
                    if (maxYear < 2026) formData.append('year_max', maxYear);
                }

                // Append RAM min if > 0
                if (ramMinInput) {
                    const ramMin = parseInt(ramMinInput.value);
                    if (ramMin > 0) formData.append('ram_min', ramMin);
                }

                // Append Storage min if > 0
                if (storageMinInput) {
                    const storageMin = parseInt(storageMinInput.value);
                    if (storageMin > 0) formData.append('storage_min', storageMin);
                }

                // Append Display Size range if not default
                if (displaySizeMinInput && displaySizeMaxInput) {
                    const minSize = parseFloat(displaySizeMinInput.value);
                    const maxSize = parseFloat(displaySizeMaxInput.value);
                    if (minSize > 3.0) formData.append('display_size_min', minSize);
                    if (maxSize < 8.0) formData.append('display_size_max', maxSize);
                }

                // OS families (multi)
                if (osFamilyInputs) {
                    osFamilyInputs.forEach(cb => {
                        if (cb.checked) formData.append('os_family[]', cb.value);
                    });
                }
                // Min OS version
                if (osVersionMinInput) {
                    const v = parseInt(osVersionMinInput.value);
                    if (v > 0) formData.append('os_version_min', v);
                }
                // Chipset contains
                if (chipsetQueryInput && chipsetQueryInput.value.trim() !== '') {
                    formData.append('chipset_query', chipsetQueryInput.value.trim());
                }
                // Require card slot
                if (cardSlotRequiredInput && cardSlotRequiredInput.checked) {
                    formData.append('card_slot_required', '1');
                }
                // Main camera min MP
                if (mainCamMpMinInput) {
                    const mp = parseInt(mainCamMpMinInput.value);
                    if (mp > 0) formData.append('main_camera_mp_min', mp);
                }
                // Video capabilities
                if (video4kInput && video4kInput.checked) formData.append('video_4k', '1');
                if (video8kInput && video8kInput.checked) formData.append('video_8k', '1');
                // Battery capacity min
                if (batteryCapacityMinInput) {
                    const bc = parseInt(batteryCapacityMinInput.value);
                    if (bc > 0) formData.append('battery_capacity_min', bc);
                }
                // Wired charging min
                if (wiredChargeMinInput) {
                    const wc = parseInt(wiredChargeMinInput.value);
                    if (wc > 0) formData.append('wired_charge_min', wc);
                }
                // Wireless required + min
                if (wirelessRequiredInput && wirelessRequiredInput.checked) formData.append('wireless_required', '1');
                if (wirelessChargeMinInput) {
                    const wlc = parseInt(wirelessChargeMinInput.value);
                    if (wlc > 0) formData.append('wireless_charge_min', wlc);
                }

                // Send AJAX request
                fetch('phonefinder_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update results count
                            resultsCount.textContent = data.count;
                            resultsSection.style.display = data.count > 0 ? 'block' : 'none';

                            // Clear previous results
                            resultsContainer.innerHTML = '';

                            // Display devices
                            if (data.devices.length > 0) {
                                data.devices.forEach(function(device) {
                                    const deviceCard = createDeviceCard(device);
                                    resultsContainer.innerHTML += deviceCard;
                                });

                                // Scroll to results
                                resultsSection.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'start'
                                });
                            } else {
                                resultsContainer.innerHTML = '<div class="col-12"><div class="alert alert-warning">No devices found matching your criteria.</div></div>';
                            }
                        } else {
                            alert('Error: ' + (data.error || 'Failed to fetch results'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while searching. Please try again.');
                    })
                    .finally(() => {
                        // Reset button state
                        findBtn.disabled = false;
                        findBtn.innerHTML = '<i class="fa fa-search me-2"></i> Find Devices';
                    });
            });

            function createDeviceCard(device) {
                return `
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <a href="device.php?id=${device.id}" class="text-decoration-none">
                            <img src="${device.thumbnail}" class="card-img-top" alt="${device.name}" style="height: 200px; object-fit: contain; padding: 10px;">
                            <div class="card-body">
                                <h6 class="card-title text-dark fw-bold">${device.name}</h6>
                                <p class="text-muted small mb-1"><i class="fa fa-building me-1"></i>${device.brand || 'Unknown'}</p>
                                ${device.announced ? `<p class="text-muted small mb-1"><i class="fa fa-calendar me-1"></i>${device.announced}</p>` : ''}
                                ${device.display_size ? `<p class="text-muted small mb-1"><i class="fa fa-mobile me-1"></i>${device.display_size}</p>` : ''}
                                ${device.ram ? `<p class="text-muted small mb-1"><i class="fa fa-microchip me-1"></i>${device.ram}</p>` : ''}
                                ${device.battery ? `<p class="text-muted small mb-0"><i class="fa fa-battery-full me-1"></i>${device.battery}</p>` : ''}
                            </div>
                        </a>
                    </div>
                </div>
            `;
            }
        });
    </script>

    <div id="bottom" class="container d-flex" style="max-width: 1034px;">
        <div class="row align-items-center">
            <div class="col-md-2 m-auto col-4 d-flex justify-content-center align-items-center "> <img
                    src="https://fdn2.gsmarena.com/w/css/logo-gsmarena-com.png" alt="">
            </div>
            <div class="col-10 nav-wrap m-auto text-center ">
                <div class="nav-container">
                    <a href="#">Home</a>

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