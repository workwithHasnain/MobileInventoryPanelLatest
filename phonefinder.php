<?php
// Public home page - no authentication required
require_once 'config.php';
require_once 'database_functions.php';
require_once 'phone_data.php';

// Helper function to convert relative image paths to absolute
function getAbsoluteImagePath($imagePath, $base)
{
    if (empty($imagePath)) return '';
    if (filter_var($imagePath, FILTER_VALIDATE_URL)) return $imagePath;
    if (strpos($imagePath, '/') === 0) return $imagePath;
    return $base . ltrim($imagePath, '/');
}

$pdo = getConnection();
$brands_stmt = $pdo->prepare("
    SELECT b.*, COUNT(p.id) as device_count
    FROM brands b
    LEFT JOIN phones p ON b.id = p.brand_id
    GROUP BY b.id, b.name, b.description, b.logo_url, b.website, b.created_at, b.updated_at
    ORDER BY COUNT(p.id) DESC, b.name ASC
    LIMIT 36
");
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll();

// Get all brands alphabetically ordered - for modal
$all_brands_stmt = $pdo->prepare("
    SELECT * FROM brands
    ORDER BY name ASC
");
$all_brands_stmt->execute();
$allBrandsModal = $all_brands_stmt->fetchAll();


// Load filter configuration from JSON
$filterConfig = json_decode(file_get_contents('filter_config.json'), true);
if (!$filterConfig) {
    die('Error loading filter configuration');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="canonical" href="<?php echo $canonicalBase; ?>/phonefinder" />
    <title>DevicesArena</title>

    <!-- Favicon & Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>imges/icon-32.png">
    <link rel="icon" type="image/png" sizes="256x256" href="<?php echo $base; ?>imges/icon-256.png">
    <link rel="shortcut icon" href="<?php echo $base; ?>imges/icon-32.png">

    <!-- Apple Touch Icon (iOS Home Screen) -->
    <link rel="apple-touch-icon" href="<?php echo $base; ?>imges/icon-256.png">
    <link rel="apple-touch-icon" sizes="256x256" href="<?php echo $base; ?>imges/icon-256.png">

    <!-- Android Chrome Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo $base; ?>imges/icon-256.png">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo $base; ?>imges/icon-256.png">

    <!-- Theme Color (Browser Chrome & Address Bar) -->
    <meta name="theme-color" content="#8D6E63">

    <!-- Windows Tile Icon -->
    <meta name="msapplication-TileColor" content="#8D6E63">
    <meta name="msapplication-TileImage" content="<?php echo $base; ?>imges/icon-256.png">

    <!-- Open Graph Meta Tags (Social Media Sharing) -->
    <meta property="og:site_name" content="DevicesArena">
    <meta property="og:title" content="DevicesArena - Smartphone Reviews & Comparisons">
    <meta property="og:description" content="Explore the latest smartphones, detailed specifications, reviews, and comparisons on DevicesArena.">
    <meta property="og:image" content="<?php echo $base; ?>imges/icon-256.png">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="256">
    <meta property="og:image:height" content="256">
    <meta property="og:type" content="website">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="DevicesArena">
    <meta name="twitter:description" content="Explore the latest smartphones, detailed specifications, reviews, and comparisons.">
    <meta name="twitter:image" content="<?php echo $base; ?>imges/icon-256.png">

    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo $base; ?>manifest.json">

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


    <link rel="stylesheet" href="<?php echo $base; ?>style.css">
    <style>
        .filter-header {
            font-weight: bold;
        }

        /* Brand Modal Styling */
        .brand-cell-modal {
            background-color: #fff;
            border: 1px solid #c5b6b0;
            color: #5D4037;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
        }

        .brand-cell-modal:hover {
            background-color: #D7CCC8 !important;
            border-color: #8D6E63;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            color: #3E2723;
        }

        .brand-cell-modal:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .brand-cell-modal:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(141, 110, 99, 0.25);
        }

        #brandsModal .modal-dialog-scrollable {
            max-height: 80vh;
        }

        /* Device Cell Modal Styling */
        .device-cell-modal {
            background-color: #fff;
            border: 1px solid #c5b6b0;
            color: #5D4037;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue';
        }

        .device-cell-modal:hover {
            background-color: #D7CCC8 !important;
            border-color: #8D6E63;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            color: #3E2723;
        }

        .device-cell-modal:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .device-cell-modal:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(141, 110, 99, 0.25);
        }

        #devicesModal .modal-dialog-scrollable {
            max-height: 80vh;
        }
    </style>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4554952734894265"
        crossorigin="anonymous"></script>
</head>

<body style="background-color: #EFEBE9;">
    <!-- Desktop Navbar of Gsmarecn -->
    <?php include 'includes/gsmheader.php'; ?>
    <div class="container support content-wrapper" id="Top">
        <div class="row">

            <div class="col-md-8 col-5  d-md-inline  " style="padding:0px;">
                <div class="comfort-life position-absolute d-lg-block d-none ">
                    <img class="w-100 h-100" src="<?php echo $base; ?>hero-images/phonefinder-hero.png"
                        style="background-repeat: no-repeat; background-size: cover;" alt="">
                </div>
            </div>
            <div class="col-md-4 col-5 d-none d-lg-block" style="position: relative; left: 0; padding:0px">
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
                                <button class="brand-cell brand-item-bold" style="cursor: pointer;" data-brand-id="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></button>
                    <?php
                            endforeach;
                        endforeach;
                    endif;
                    ?>
                </div>
                <div class="d-flex">
                    <button class="solid w-50 py-2" onclick="showBrandsModal()">
                        <i class="fa-solid fa-bars fa-sm mx-2"></i>
                        All Brands</button>
                    <button class="solid w-50 py-2">
                        <i class="fa-solid fa-volume-high fa-sm mx-2"></i>
                        RUMORS MILL</button>
                </div>
            </div>
        </div>

    </div>

    <div class="container bg-white margin-top-4rem vwr">
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
                    <div class="filter-header mx-1 mb-2">Connectivity Slot</div>
                    <div class="row g-2">
                        <div class="col-6">
                            <button class=" btn btn-toggle w-100 text-start" type="button" data-bs-toggle="collapse"
                                data-bs-target="#simCollapse" aria-expanded="false" aria-controls="simCollapse"
                                style="border-radius: 1px;">
                                2G
                            </button>
                            <div class="collapse" id="simCollapse">
                                <div class="card card-body px-3">
                                    <?php if (isset($filterConfig['network_2g_bands']) && is_array($filterConfig['network_2g_bands'])): ?>
                                        <?php foreach ($filterConfig['network_2g_bands'] as $index => $band): ?>
                                            <div class="form-check">
                                                <input class="form-check-input network-2g-band" type="checkbox" value="<?php echo htmlspecialchars($band['value']); ?>" id="gsm<?php echo $index; ?>"
                                                    name="network_2g_bands[]" />
                                                <label class="form-check-label" for="gsm<?php echo $index; ?>"><?php echo htmlspecialchars($band['label']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
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
                                    <?php if (isset($filterConfig['network_3g_bands']) && is_array($filterConfig['network_3g_bands'])): ?>
                                        <?php foreach ($filterConfig['network_3g_bands'] as $index => $band): ?>
                                            <div class="form-check">
                                                <input class="form-check-input network-3g-band" type="checkbox" value="<?php echo htmlspecialchars($band['value']); ?>" id="hspa<?php echo $index; ?>"
                                                    name="network_3g_bands[]" />
                                                <label class="form-check-label" for="hspa<?php echo $index; ?>"><?php echo htmlspecialchars($band['label']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 d-flex align-items-center justify-content-center">
                            <label class="btn w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                                <input type="checkbox" class="form-check-input me-2 float-end" id="dualSim" name="dual_sim" value="1"> DUAL SIM
                            </label>
                        </div>
                        <div class="col-lg-6 d-flex align-items-center justify-content-center">
                            <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                                <input type="checkbox" class="form-check-input me-2 float-end" id="esimSupport" name="esim" value="1"> ESIM
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
                            <?php if (isset($filterConfig['form_factors']) && is_array($filterConfig['form_factors'])): ?>
                                <?php foreach ($filterConfig['form_factors'] as $index => $factor): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($factor); ?>" id="formFactor<?php echo $index; ?>" name="form_factor[]" />
                                        <label class="form-check-label" for="formFactor<?php echo $index; ?>"><?php echo htmlspecialchars($factor); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex fw-bolder align-items-center gap-3 mt-2"
                        style="border: 1px solid; padding: 7px; margin-top: 14px;">
                        Height: <span id="heightMinValue">Any</span>
                        <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['dimensions']['height_min']; ?>" max="<?php echo $filterConfig['dimensions']['height_max']; ?>" step="<?php echo $filterConfig['dimensions']['height_step']; ?>"
                            id="heightMin" value="<?php echo $filterConfig['dimensions']['height_min']; ?>">
                        <span class="text-muted">mm</span>
                    </div>
                    <div class="d-flex fw-bolder align-items-center gap-3 mt-2"
                        style="border: 1px solid; padding: 7px; margin-top: 14px;">
                        Thickness: <span id="thicknessMaxValue">Any</span>
                        <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['dimensions']['thickness_min']; ?>" max="<?php echo $filterConfig['dimensions']['thickness_max']; ?>" step="<?php echo $filterConfig['dimensions']['thickness_step']; ?>"
                            id="thicknessMax" value="<?php echo $filterConfig['dimensions']['thickness_min']; ?>">
                        <span class="text-muted">mm</span>
                    </div>
                    <button style="border-radius: 1px;" class=" btn btn-toggle w-100 mt-2 text-start" type="button"
                        data-bs-toggle="collapse" data-bs-target="#ipCollapse" aria-expanded="false"
                        aria-controls="ipCollapse">
                        IP CERTIFICATE
                    </button>
                    <div class="collapse" id="ipCollapse">
                        <div class="card card-body px-3">
                            <?php if (isset($filterConfig['ip_certificates']) && is_array($filterConfig['ip_certificates'])): ?>
                                <?php foreach ($filterConfig['ip_certificates'] as $index => $ipCert): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($ipCert); ?>" id="ip<?php echo $index; ?>" name="ip_certificate[]" />
                                        <label class="form-check-label" for="ip<?php echo $index; ?>"><?php echo htmlspecialchars($ipCert); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button style="border-radius: 1px;" class=" btn btn-toggle w-100 mt-2 text-start" type="button"
                        data-bs-toggle="collapse" data-bs-target="#backCollapse" aria-expanded="false"
                        aria-controls="backCollapse">
                        Back Material
                    </button>
                    <div class="collapse" id="backCollapse">
                        <div class="card card-body px-3">
                            <?php if (isset($filterConfig['back_materials']) && is_array($filterConfig['back_materials'])): ?>
                                <?php foreach ($filterConfig['back_materials'] as $index => $material): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($material); ?>" id="backMaterial<?php echo $index; ?>" name="back_material[]" />
                                        <label class="form-check-label" for="backMaterial<?php echo $index; ?>"> <?php echo htmlspecialchars($material); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="filter-header mx-1 mb-2">Hardware</div>
                    <button style="border-radius: 1px;" class="btn btn-toggle w-100 mt-2 text-start" type="button"
                        data-bs-toggle="collapse" data-bs-target="#osCollapse" aria-expanded="false"
                        aria-controls="osCollapse">
                        OS: </button>
                    <div class="collapse" id="osCollapse">
                        <div class="card card-body px-3">
                            <div class="row g-2">
                                <div class="col-12">Select OS family</div>
                                <?php if (isset($filterConfig['os_families']) && is_array($filterConfig['os_families'])): ?>
                                    <?php foreach ($filterConfig['os_families'] as $index => $os): ?>
                                        <div class="col-<?php echo count($filterConfig['os_families']) > 3 ? '12' : '6'; ?>">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="<?php echo strtolower(htmlspecialchars($os)); ?>" id="os<?php echo $index; ?>" name="os_family" />
                                                <label class="form-check-label" for="os<?php echo $index; ?>"><?php echo htmlspecialchars($os); ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                    <div class="filter-header mx-1 mb-2">System Memory</div>
                    <!-- RAM filter (Min GB) -->
                    <div class="d-flex fw-bolder align-items-center gap-3 mt-3"
                        style="border: 1px solid; padding: 7px; margin-top: 14px;">
                        Min RAM: <span id="ramMinValue">Any</span>
                        <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['ram']['min']; ?>" max="<?php echo $filterConfig['ram']['max']; ?>" step="<?php echo $filterConfig['ram']['step']; ?>"
                            id="ramMin" value="<?php echo $filterConfig['ram']['default']; ?>">
                        <span class="text-muted">GB</span>
                    </div>
                    <button style="border-radius: 1px;" class=" btn btn-toggle w-100 mt-2 text-start" type="button"
                        data-bs-toggle="collapse" data-bs-target="#cardCollapse" aria-expanded="false"
                        aria-controls="cardCollapse">
                        Expansion Slot: </button>
                    <div class="collapse" id="cardCollapse">
                        <div class="card card-body px-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="cardSlotRequired" name="card_slot_required" />
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
                    Year: <span id="yearMinValue"><?php echo $filterConfig['year']['default_min']; ?></span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['year']['min']; ?>" max="<?php echo $filterConfig['year']['max']; ?>"
                        id="yearMin" value="<?php echo $filterConfig['year']['default_min']; ?>">
                    <span class="mx-2">-</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['year']['min']; ?>" max="<?php echo $filterConfig['year']['max']; ?>"
                        id="yearMax" value="<?php echo $filterConfig['year']['default_max']; ?>">
                    <span id="yearMaxValue"><?php echo $filterConfig['year']['default_max']; ?></span>
                </div>
                <!-- Price filter (Max price) -->
                <div class="d-flex fw-bolder align-items-center gap-3 mt-4"
                    style="border: 1px solid; padding: 7px; margin-top: 14px;">
                    Max Price: <span id="priceMaxValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['price']['min']; ?>" max="<?php echo $filterConfig['price']['max']; ?>" step="<?php echo $filterConfig['price']['step']; ?>"
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
                                <?php if (isset($filterConfig['network_4g_bands']) && is_array($filterConfig['network_4g_bands'])): ?>
                                    <?php foreach ($filterConfig['network_4g_bands'] as $index => $band): ?>
                                        <div class="form-check">
                                            <input class="form-check-input network-4g-band" type="checkbox" value="<?php echo htmlspecialchars($band['value']); ?>" id="lte<?php echo $index; ?>"
                                                name="network_4g_bands[]" />
                                            <label class="form-check-label" for="lte<?php echo $index; ?>"><?php echo htmlspecialchars($band['label']); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                                <?php if (isset($filterConfig['network_5g_bands']) && is_array($filterConfig['network_5g_bands'])): ?>
                                    <?php foreach ($filterConfig['network_5g_bands'] as $index => $band): ?>
                                        <div class="form-check">
                                            <input class="form-check-input network-5g-band" type="checkbox" value="<?php echo htmlspecialchars($band['value']); ?>" id="nr5g<?php echo $index; ?>"
                                                name="network_5g_bands[]" />
                                            <label class="form-check-label" for="nr5g<?php echo $index; ?>"><?php echo htmlspecialchars($band['label']); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <button style="border-radius: 1px;" class=" btn btn-toggle mt-3 w-100 text-start" type="button"
                    data-bs-toggle="collapse" data-bs-target="#sizeCollapse" aria-expanded="false"
                    aria-controls="sizeCollapse">
                    SIM SIZE
                </button>
                <div class="collapse" id="sizeCollapse">
                    <div class="card card-body px-3">
                        <?php if (isset($filterConfig['sim_types']) && is_array($filterConfig['sim_types'])): ?>
                            <?php foreach ($filterConfig['sim_types'] as $index => $simType): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($simType); ?>" id="simType<?php echo $index; ?>" name="sim_sizes[]" />
                                    <label class="form-check-label" for="simType<?php echo $index; ?>"><?php echo htmlspecialchars($simType); ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex align-items-center fw-bolder gap-3 mt-2"
                    style="border: 1px solid; padding: 7px; margin-top: 14px;">
                    Width: <span id="widthMinValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['dimensions']['width_min']; ?>" max="<?php echo $filterConfig['dimensions']['width_max']; ?>" step="<?php echo $filterConfig['dimensions']['width_step']; ?>"
                        id="widthMin" value="<?php echo $filterConfig['dimensions']['width_min']; ?>">
                    <span class="text-muted">mm</span>
                </div>
                <div class="d-flex align-items-center fw-bolder gap-3 mt-2"
                    style="border: 1px solid; padding: 7px; margin-top: 14px;">
                    Weight: <span id="weightMaxValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['dimensions']['weight_min']; ?>" max="<?php echo $filterConfig['dimensions']['weight_max']; ?>" step="<?php echo $filterConfig['dimensions']['weight_step']; ?>"
                        id="weightMax" value="<?php echo $filterConfig['dimensions']['weight_min']; ?>">
                    <span class="text-muted">g</span>
                </div>
                <button style="border-radius: 1px;" class="btn btn-toggle w-100 mt-2 text-start" type="button"
                    data-bs-toggle="collapse" data-bs-target="#colorCollapse" aria-expanded="false"
                    aria-controls="colorCollapse">
                    Color
                </button>
                <div class="collapse" id="colorCollapse">
                    <div class="card card-body px-3">
                        <?php if (isset($filterConfig['colors']) && is_array($filterConfig['colors'])): ?>
                            <?php foreach ($filterConfig['colors'] as $index => $color): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($color); ?>" id="color<?php echo $index; ?>" name="color[]" />
                                    <label class="form-check-label" for="color<?php echo $index; ?>"> <?php echo strtoupper(htmlspecialchars($color)); ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <button style="border-radius: 1px;" class="btn btn-toggle w-100 mt-2 text-start" type="button"
                    data-bs-toggle="collapse" data-bs-target="#frontCollapse" aria-expanded="false"
                    aria-controls="frontCollapse">
                    Frame Material
                </button>
                <div class="collapse" id="frontCollapse">
                    <div class="card card-body px-3">
                        <?php if (isset($filterConfig['frame_materials']) && is_array($filterConfig['frame_materials'])): ?>
                            <?php foreach ($filterConfig['frame_materials'] as $index => $material): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($material); ?>" id="frameMaterial<?php echo $index; ?>" name="frame_material[]" />
                                    <label class="form-check-label" for="frameMaterial<?php echo $index; ?>"> <?php echo htmlspecialchars($material); ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                            <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['os_version']['min']; ?>" max="<?php echo $filterConfig['os_version']['max']; ?>" step="<?php echo $filterConfig['os_version']['step']; ?>"
                                id="osVersionMin" value="<?php echo $filterConfig['os_version']['default']; ?>">
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center fw-bolder gap-3 mt-2"
                    style="border: 1px solid; padding: 7px; margin-top: 14px;">
                    Processor: <span id="cpuClockValue"><?php echo $filterConfig['cpu_clock']['min']; ?></span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['cpu_clock']['min']; ?>" max="<?php echo $filterConfig['cpu_clock']['max']; ?>" step="<?php echo $filterConfig['cpu_clock']['step']; ?>"
                        id="cpuClock" value="<?php echo $filterConfig['cpu_clock']['default']; ?>">
                    <span class="text-muted">GHz</span>
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
                        <?php if (isset($filterConfig['fingerprint_types']) && is_array($filterConfig['fingerprint_types'])): ?>
                            <?php foreach ($filterConfig['fingerprint_types'] as $index => $fpType): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($fpType); ?>" id="fp<?php echo $index; ?>" name="fingerprint[]" />
                                    <label class="form-check-label" for="fp<?php echo $index; ?>"><?php echo htmlspecialchars($fpType); ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Storage filter (Min GB) -->
                <div class="d-flex fw-bolder align-items-center gap-3 mt-5"
                    style="border: 1px solid; padding: 7px; margin-top: 14px;">
                    Min Storage: <span id="storageMinValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['storage']['min']; ?>" max="<?php echo $filterConfig['storage']['max']; ?>" step="<?php echo $filterConfig['storage']['step']; ?>"
                        id="storageMin" value="<?php echo $filterConfig['storage']['default']; ?>">
                    <span class="text-muted">GB</span>
                </div>
            </div>
            <div class="filter-header">Display</div>
            <div class="d-flex secondary fw-bolder align-items-center gap-3 mt-2">
                Resolution: <span id="displayResMinValue">min</span>
                <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['display_resolution']['min']; ?>" max="<?php echo $filterConfig['display_resolution']['max']; ?>" step="<?php echo $filterConfig['display_resolution']['step']; ?>" id="displayResMin" value="<?php echo $filterConfig['display_resolution']['default_min']; ?>">
                <span class="mx-2">-</span>
                <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['display_resolution']['min']; ?>" max="<?php echo $filterConfig['display_resolution']['max']; ?>" step="<?php echo $filterConfig['display_resolution']['step']; ?>" id="displayResMax" value="<?php echo $filterConfig['display_resolution']['default_max']; ?>">
                <span id="displayResMaxValue">max</span>
            </div>
        </div>
        <div class="row gx-4 gy-3 crs">
            <div class="col-lg-6 mt-3 py-3">
                <!-- Display Size filter (Min-Max inches) -->
                <div class="filter-box ">
                    <span class="filter-label">Size:</span>
                    <span id="displaySizeMinValue"><?php echo $filterConfig['display_size']['default_min']; ?></span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['display_size']['min']; ?>" max="<?php echo $filterConfig['display_size']['max']; ?>" step="<?php echo $filterConfig['display_size']['step']; ?>"
                        id="displaySizeMin" value="<?php echo $filterConfig['display_size']['default_min']; ?>" />
                    <span class="mx-2">-</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['display_size']['min']; ?>" max="<?php echo $filterConfig['display_size']['max']; ?>" step="<?php echo $filterConfig['display_size']['step']; ?>"
                        id="displaySizeMax" value="<?php echo $filterConfig['display_size']['default_max']; ?>" />
                    <span id="displaySizeMaxValue"><?php echo $filterConfig['display_size']['default_max']; ?></span>
                    <span class="text-muted">"</span>
                </div>
                <button class="btn  btn-toggle w-100  mb-3 mt-2" type="button" data-bs-toggle="collapse"
                    data-bs-target="#techCollapse" aria-expanded="false" aria-controls="techCollapse">
                    Technology
                </button>
                <div class="collapse" id="techCollapse">
                    <div class="card card-body">
                        <?php if (isset($filterConfig['display_technologies']) && is_array($filterConfig['display_technologies'])): ?>
                            <?php foreach ($filterConfig['display_technologies'] as $index => $tech): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($tech); ?>" id="tech<?php echo $index; ?>"
                                        name="display_tech[]" />
                                    <label class="form-check-label" for="tech<?php echo $index; ?>"><?php echo htmlspecialchars($tech); ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="filter-box">
                    <span class="filter-label">Refresh Rate:</span>
                    <span id="refreshRateMinValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['refresh_rate']['min']; ?>" max="<?php echo $filterConfig['refresh_rate']['max']; ?>" step="<?php echo $filterConfig['refresh_rate']['step']; ?>"
                        id="refreshRateMin" value="<?php echo $filterConfig['refresh_rate']['default']; ?>" />
                    <span class="text-muted">Hz</span>
                </div>
                <div class="filter-header mt-4 mb-3" style="margin-left: -1px;">Main Camera</div>
                <div class="filter-box  ">
                    <span class="filter-label ">Resolution</span>
                    <span id="fNumberMaxValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['f_number']['min']; ?>" max="<?php echo $filterConfig['f_number']['max']; ?>" step="<?php echo $filterConfig['f_number']['step']; ?>"
                        id="fNumberMax" value="<?php echo $filterConfig['f_number']['default']; ?>" />
                    <span class="text-muted">MP</span>
                </div>
                <div class="filter-box mt-1 ">
                    Processor: <span id="cpuClockMinValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['cpu_clock']['min']; ?>" max="<?php echo $filterConfig['cpu_clock']['max']; ?>" step="<?php echo $filterConfig['cpu_clock']['step']; ?>"
                        id="cpuClockMin" value="0">
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
                            <input class="form-check-input wifi-version" type="checkbox" value="802.11n" id="wifi4"
                                name="wifi_versions[]" />
                            <label class="form-check-label" for="wifi4">Wi-Fi 4 (802.11n)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input wifi-version" type="checkbox" value="802.11ac" id="wifi5"
                                name="wifi_versions[]" />
                            <label class="form-check-label" for="wifi5">Wi-Fi 5 (802.11ac)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input wifi-version" type="checkbox" value="802.11ax" id="wifi6"
                                name="wifi_versions[]" />
                            <label class="form-check-label" for="wifi6">Wi-Fi 6 (802.11ax)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input wifi-version" type="checkbox" value="802.11be" id="wifi7"
                                name="wifi_versions[]" />
                            <label class="form-check-label" for="wifi7">Wi-Fi 7 (802.11be)</label>
                        </div>

                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" id="gpsRequired" name="gps" value="1"> GPS
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" id="nfcRequired" name="nfc" value="1"> NFC
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
                            <input class="form-check-input usb-type" type="checkbox" value="USB-C" id="usbTypeC"
                                name="usb_types[]" />
                            <label class="form-check-label" for="usbTypeC">Any USB-C</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input usb-type" type="checkbox" value="USB 3" id="usb3"
                                name="usb_types[]" />
                            <label class="form-check-label" for="usb3">USB-C 3.0 and higher</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input usb-type" type="checkbox" value="micro USB" id="microUsb"
                                name="usb_types[]" />
                            <label class="form-check-label" for="microUsb">Micro USB</label>
                        </div>

                    </div>
                </div>
                <div class="filter-header mt-1 mb-2" style="margin-left: -1px;">Battery</div>
                <div class="filter-box ">
                    <span class="filter-label ">Capacity</span>
                    <span id="batteryCapacityMinValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['battery_capacity']['min']; ?>" max="<?php echo $filterConfig['battery_capacity']['max']; ?>" step="<?php echo $filterConfig['battery_capacity']['step']; ?>"
                        id="batteryCapacityMin" />
                    <span class="text-muted">mAh</span>
                </div>
                <div class="filter-box mt-1">
                    <span class="filter-label">Wired Charging:</span>
                    <span id="wiredChargeMinValue">Any</span>
                    <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['wired_charging']['min']; ?>" max="<?php echo $filterConfig['wired_charging']['max']; ?>" step="<?php echo $filterConfig['wired_charging']['step']; ?>"
                        id="wiredChargeMin" />
                    <span class="text-muted">W</span>
                </div>

                <div class="filter-header mt-3 mb-3" style="margin-left: -1px;">General Info</div>
                <div class="filter-box">
                    <span class="filter-label">Free TExt</span>

                    <input type="text" class=" flex-grow-1 life" />

                </div>
            </div>
            <div class="col-lg-6 mt-3 py-3">
                <button class="btn  w-100 btn-toggle  mb-3 mt-2" type="button" data-bs-toggle="collapse"
                    data-bs-target="#notchCollapse" aria-expanded="false" aria-controls="notchCollapse">
                    Notch
                </button>
                <div class="collapse" id="notchCollapse">
                    <div class="card card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="No notch" id="notchNo"
                                name="display_notch[]" />
                            <label class="form-check-label" for="notchNo">No</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Notch" id="notchYes"
                                name="display_notch[]" />
                            <label class="form-check-label" for="notchYes">Yes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Punch hole" id="notchPunch"
                                name="display_notch[]" />
                            <label class="form-check-label" for="notchPunch">Punch hole</label>
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
                            <input class="form-check-input bluetooth-version" type="checkbox" value="4.0" id="bt40"
                                name="bluetooth_versions[]" />
                            <label class="form-check-label" for="bt40">Bluetooth 4.0</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input bluetooth-version" type="checkbox" value="4.1" id="bt41"
                                name="bluetooth_versions[]" />
                            <label class="form-check-label" for="bt41">Bluetooth 4.1</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input bluetooth-version" type="checkbox" value="4.2" id="bt42"
                                name="bluetooth_versions[]" />
                            <label class="form-check-label" for="bt42">Bluetooth 4.2</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input bluetooth-version" type="checkbox" value="5.0" id="bt50"
                                name="bluetooth_versions[]" />
                            <label class="form-check-label" for="bt50">Bluetooth 5.0</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input bluetooth-version" type="checkbox" value="5.1" id="bt51"
                                name="bluetooth_versions[]" />
                            <label class="form-check-label" for="bt51">Bluetooth 5.1</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input bluetooth-version" type="checkbox" value="5.2" id="bt52"
                                name="bluetooth_versions[]" />
                            <label class="form-check-label" for="bt52">Bluetooth 5.2</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input bluetooth-version" type="checkbox" value="5.3" id="bt53"
                                name="bluetooth_versions[]" />
                            <label class="form-check-label" for="bt53">Bluetooth 5.3</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input bluetooth-version" type="checkbox" value="5.4" id="bt54"
                                name="bluetooth_versions[]" />
                            <label class="form-check-label" for="bt54">Bluetooth 5.4</label>
                        </div>

                    </div>
                </div>
                <div class="row " style="margin-top: -7px;">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" id="infraredRequired" name="infrared" value="1">INFRARED
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn  w-100 text-start mb-0 fw-bolder" style="border-radius: 1px;">
                            <input type="checkbox" class="form-check-input me-2 float-end" id="fmRadioRequired" name="fm_radio" value="1"> FM-RADIO
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
                            <input type="range" class="form-range custom-range flex-grow-1" min="<?php echo $filterConfig['wireless_charging']['min']; ?>" max="<?php echo $filterConfig['wireless_charging']['max']; ?>" step="<?php echo $filterConfig['wireless_charging']['step']; ?>"
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

        <!-- Action buttons -->
        <div class="row mt-4 mb-3">
            <div class="col-md-6 mb-2">
                <button type="button" id="resetFiltersBtn" class="w-100 py-3 fw-bold" style="background-color: #6c757d; color: white; border: none; cursor: pointer; border-radius: 4px;">
                    <i class="fa fa-undo me-2" style="pointer-events: none;"></i><span style="pointer-events: none;">Reset Filters</span>
                </button>
            </div>
            <div class="col-md-6 mb-2">
                <button type="button" id="findDevicesBtn" class="w-100 py-3 fw-bold" style="background-color: #d50000; color: white; border: none; cursor: pointer; border-radius: 4px;">
                    <i class="fa fa-search me-2" style="pointer-events: none;"></i><span style="pointer-events: none;">Find Devices</span>
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
        // Make filter configuration available globally
        const filterConfigData = <?php echo json_encode($filterConfig); ?>;
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle brand cell clicks (from sidebar and mobile menu - open devices modal directly)
            document.querySelectorAll('.brand-cell').forEach(function(cell) {
                cell.addEventListener('click', function(e) {
                    e.preventDefault();
                    const brandId = this.getAttribute('data-brand-id');
                    if (brandId) {
                        // Directly open devices modal for this brand
                        selectBrandFromModal(brandId);
                    }
                });
            });

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

            // Display resolution slider elements
            const displayResMinInput = document.getElementById('displayResMin');
            const displayResMinValue = document.getElementById('displayResMinValue');
            const displayResMaxInput = document.getElementById('displayResMax');
            const displayResMaxValue = document.getElementById('displayResMaxValue');

            // Refresh rate slider
            const refreshRateMinInput = document.getElementById('refreshRateMin');
            const refreshRateMinValue = document.getElementById('refreshRateMinValue');

            // F-number (aperture) max slider
            const fNumberMaxInput = document.getElementById('fNumberMax');
            const fNumberMaxValue = document.getElementById('fNumberMaxValue');

            // CPU clock min slider (GHz)
            const cpuClockMinInput = document.getElementById('cpuClockMin');
            const cpuClockMinValue = document.getElementById('cpuClockMinValue');

            // Body sliders
            const heightMinInput = document.getElementById('heightMin');
            const heightMinValue = document.getElementById('heightMinValue');
            const thicknessMaxInput = document.getElementById('thicknessMax');
            const thicknessMaxValue = document.getElementById('thicknessMaxValue');
            const widthMinInput = document.getElementById('widthMin');
            const widthMinValue = document.getElementById('widthMinValue');
            const weightMaxInput = document.getElementById('weightMax');
            const weightMaxValue = document.getElementById('weightMaxValue');

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

            // Display resolution slider handlers
            if (displayResMinInput && displayResMinValue) {
                const formatRes = (v) => v == 480 ? 'min' : v + 'p';
                displayResMinValue.textContent = formatRes(displayResMinInput.value);
                displayResMinInput.addEventListener('input', function() {
                    displayResMinValue.textContent = formatRes(this.value);
                    if (parseInt(this.value) > parseInt(displayResMaxInput.value)) {
                        displayResMaxInput.value = this.value;
                        displayResMaxValue.textContent = formatRes(this.value);
                    }
                });
            }
            if (displayResMaxInput && displayResMaxValue) {
                const formatRes = (v) => v == 4320 ? 'max' : v + 'p';
                displayResMaxValue.textContent = formatRes(displayResMaxInput.value);
                displayResMaxInput.addEventListener('input', function() {
                    displayResMaxValue.textContent = formatRes(this.value);
                    if (parseInt(this.value) < parseInt(displayResMinInput.value)) {
                        displayResMinInput.value = this.value;
                        displayResMinValue.textContent = formatRes(this.value);
                    }
                });
            }

            // Refresh rate handler
            if (refreshRateMinInput && refreshRateMinValue) {
                const formatHz = (v) => v == 0 ? 'Any' : '≥ ' + v + 'Hz';
                refreshRateMinValue.textContent = formatHz(refreshRateMinInput.value);
                refreshRateMinInput.addEventListener('input', function() {
                    refreshRateMinValue.textContent = formatHz(this.value);
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

            // F-number handler (max threshold, smaller is better)
            if (fNumberMaxInput && fNumberMaxValue) {
                const fmt = (v) => parseFloat(v) == 0 ? 'Any' : '≤ f/' + parseFloat(v).toFixed(1);
                fNumberMaxValue.textContent = fmt(fNumberMaxInput.value);
                fNumberMaxInput.addEventListener('input', function() {
                    fNumberMaxValue.textContent = fmt(this.value);
                });
            }

            // CPU clock handler (min GHz)
            if (cpuClockMinInput && cpuClockMinValue) {
                const fmt = (v) => parseFloat(v) == 0 ? 'Any' : '≥ ' + parseFloat(v).toFixed(1) + ' GHz';
                cpuClockMinValue.textContent = fmt(cpuClockMinInput.value);
                cpuClockMinInput.addEventListener('input', function() {
                    cpuClockMinValue.textContent = fmt(this.value);
                });
            }

            // Height (min)
            if (heightMinInput && heightMinValue) {
                const fmt = (v) => parseInt(v) == 0 ? 'Any' : '≥ ' + parseInt(v) + ' mm';
                heightMinValue.textContent = fmt(heightMinInput.value);
                heightMinInput.addEventListener('input', function() {
                    heightMinValue.textContent = fmt(this.value);
                });
            }
            // Thickness (max)
            if (thicknessMaxInput && thicknessMaxValue) {
                const fmt = (v) => parseFloat(v) == 0 ? 'Any' : '≤ ' + parseFloat(v).toFixed(1) + ' mm';
                thicknessMaxValue.textContent = fmt(thicknessMaxInput.value);
                thicknessMaxInput.addEventListener('input', function() {
                    thicknessMaxValue.textContent = fmt(this.value);
                });
            }
            // Width (min)
            if (widthMinInput && widthMinValue) {
                const fmt = (v) => parseInt(v) == 0 ? 'Any' : '≥ ' + parseInt(v) + ' mm';
                widthMinValue.textContent = fmt(widthMinInput.value);
                widthMinInput.addEventListener('input', function() {
                    widthMinValue.textContent = fmt(this.value);
                });
            }
            // Weight (max)
            if (weightMaxInput && weightMaxValue) {
                const fmt = (v) => parseInt(v) == 0 ? 'Any' : '≤ ' + parseInt(v) + ' g';
                weightMaxValue.textContent = fmt(weightMaxInput.value);
                weightMaxInput.addEventListener('input', function() {
                    weightMaxValue.textContent = fmt(this.value);
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
                if (displayResMinInput) displayResMinInput.value = displayResMinInput.min;
                if (displayResMaxInput) displayResMaxInput.value = displayResMaxInput.max;
                if (refreshRateMinInput) refreshRateMinInput.value = refreshRateMinInput.min;
                if (osVersionMinInput) osVersionMinInput.value = osVersionMinInput.min;
                if (mainCamMpMinInput) mainCamMpMinInput.value = mainCamMpMinInput.min;
                if (batteryCapacityMinInput) batteryCapacityMinInput.value = batteryCapacityMinInput.min;
                if (wiredChargeMinInput) wiredChargeMinInput.value = wiredChargeMinInput.min;
                if (wirelessChargeMinInput) wirelessChargeMinInput.value = wirelessChargeMinInput.min;
                if (fNumberMaxInput) fNumberMaxInput.value = fNumberMaxInput.min;
                if (cpuClockMinInput) cpuClockMinInput.value = cpuClockMinInput.min;
                if (heightMinInput) heightMinInput.value = heightMinInput.min;
                if (thicknessMaxInput) thicknessMaxInput.value = thicknessMaxInput.min;
                if (widthMinInput) widthMinInput.value = widthMinInput.min;
                if (weightMaxInput) weightMaxInput.value = weightMaxInput.min;
                if (chipsetQueryInput) chipsetQueryInput.value = '';
                // Trigger UI updates for labels
                if (typeof Event === 'function') {
                    [priceMaxInput, yearMinInput, yearMaxInput, ramMinInput, storageMinInput, displaySizeMinInput, displaySizeMaxInput, displayResMinInput, displayResMaxInput, refreshRateMinInput, osVersionMinInput, mainCamMpMinInput, batteryCapacityMinInput, wiredChargeMinInput, wirelessChargeMinInput, fNumberMaxInput, cpuClockMinInput, heightMinInput, thicknessMaxInput, widthMinInput, weightMaxInput].forEach(inp => {
                        if (inp) inp.dispatchEvent(new Event('input'));
                    });
                }
            }

            // On page load, reset all filters
            resetAllFilters();

            // Reset button handler
            const resetBtn = document.getElementById('resetFiltersBtn');
            if (resetBtn) {
                resetBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    resetAllFilters();
                });
            }

            findBtn.addEventListener('click', function() {
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

                // Check if at least one filter is applied
                let hasFilter = false;

                // Check brands and availability
                if (selectedBrands.length > 0 || selectedAvailability.length > 0) {
                    hasFilter = true;
                }

                // Check price max
                if (priceMaxInput) {
                    const val = parseInt(priceMaxInput.value, 10);
                    const maxPrice = parseInt(priceMaxInput.max, 10);
                    if (!isNaN(val) && val > 0 && val < maxPrice) {
                        hasFilter = true;
                    }
                }

                // Check year range
                if (yearMinInput && yearMaxInput) {
                    const minYear = parseInt(yearMinInput.value);
                    const maxYear = parseInt(yearMaxInput.value);
                    if (minYear > 2000 || maxYear < 2026) {
                        hasFilter = true;
                    }
                }

                // Check RAM
                if (ramMinInput && parseInt(ramMinInput.value) > 0) {
                    hasFilter = true;
                }

                // Check Storage
                if (storageMinInput && parseInt(storageMinInput.value) > 0) {
                    hasFilter = true;
                }

                // Check Display Size
                if (displaySizeMinInput && displaySizeMaxInput) {
                    const minSize = parseFloat(displaySizeMinInput.value);
                    const maxSize = parseFloat(displaySizeMaxInput.value);
                    if (minSize > 3.0 || maxSize < 8.0) {
                        hasFilter = true;
                    }
                }

                // Check Display Resolution
                if (displayResMinInput && displayResMaxInput) {
                    const minRes = parseInt(displayResMinInput.value);
                    const maxRes = parseInt(displayResMaxInput.value);
                    if (minRes > 480 || maxRes < 4320) {
                        hasFilter = true;
                    }
                }

                // Check various checkboxes and inputs
                if (document.querySelectorAll('input[name="display_tech[]"]:checked').length > 0 ||
                    document.querySelectorAll('input[name="display_notch[]"]:checked').length > 0 ||
                    (refreshRateMinInput && parseInt(refreshRateMinInput.value) > 0) ||
                    document.querySelector('input[name="hdr"]:checked') ||
                    document.querySelector('input[name="billion_colors"]:checked') ||
                    document.querySelectorAll('input[name="os_family"]:checked').length > 0 ||
                    (osVersionMinInput && parseInt(osVersionMinInput.value) > 0) ||
                    (chipsetQueryInput && chipsetQueryInput.value.trim() !== '') ||
                    document.querySelector('input[name="card_slot_required"]:checked') ||
                    (mainCamMpMinInput && parseInt(mainCamMpMinInput.value) > 0) ||
                    (fNumberMaxInput && parseFloat(fNumberMaxInput.value) > 0) ||
                    document.querySelector('input[name="video_4k"]:checked') ||
                    document.querySelector('input[name="video_8k"]:checked') ||
                    (batteryCapacityMinInput && parseInt(batteryCapacityMinInput.value) > 0) ||
                    (wiredChargeMinInput && parseInt(wiredChargeMinInput.value) > 0) ||
                    document.querySelector('input[name="wireless_required"]:checked') ||
                    (wirelessChargeMinInput && parseInt(wirelessChargeMinInput.value) > 0) ||
                    (cpuClockMinInput && parseFloat(cpuClockMinInput.value) > 0) ||
                    (heightMinInput && parseInt(heightMinInput.value) > 0) ||
                    (thicknessMaxInput && parseFloat(thicknessMaxInput.value) > 0) ||
                    (widthMinInput && parseInt(widthMinInput.value) > 0) ||
                    (weightMaxInput && parseInt(weightMaxInput.value) > 0) ||
                    document.querySelectorAll('input[name="color[]"]:checked').length > 0 ||
                    document.querySelectorAll('input[name="frame_material[]"]:checked').length > 0 ||
                    document.querySelectorAll('input[name="back_material[]"]:checked').length > 0 ||
                    document.querySelectorAll('input.network-2g-band:checked').length > 0 ||
                    document.querySelectorAll('input.network-3g-band:checked').length > 0 ||
                    document.querySelectorAll('input.network-4g-band:checked').length > 0 ||
                    document.querySelectorAll('input.network-5g-band:checked').length > 0 ||
                    document.getElementById('dualSim')?.checked ||
                    document.getElementById('esimSupport')?.checked ||
                    document.querySelectorAll('input[name="sim_sizes[]"]:checked').length > 0 ||
                    document.querySelectorAll('input.wifi-version:checked').length > 0 ||
                    document.querySelectorAll('input.bluetooth-version:checked').length > 0 ||
                    document.querySelectorAll('input.usb-type:checked').length > 0 ||
                    document.getElementById('gpsRequired')?.checked ||
                    document.getElementById('nfcRequired')?.checked ||
                    document.getElementById('infraredRequired')?.checked ||
                    document.getElementById('fmRadioRequired')?.checked ||
                    document.querySelector('input[name="accelerometer"]:checked') ||
                    document.querySelector('input[name="gyro"]:checked') ||
                    document.querySelector('input[name="barometer"]:checked') ||
                    document.querySelector('input[name="heart_rate"]:checked') ||
                    document.querySelector('input[name="compass"]:checked') ||
                    document.querySelector('input[name="proximity"]:checked') ||
                    document.querySelector('input[name="headphone_jack"]:checked') ||
                    document.querySelector('input[name="dual_speakers"]:checked') ||
                    document.querySelector('input[name="main_camera_telephoto"]:checked') ||
                    document.querySelector('input[name="main_camera_ultrawide"]:checked') ||
                    document.querySelector('input[name="main_camera_ois"]:checked') ||
                    document.querySelector('input[name="selfie_camera_flash"]:checked') ||
                    document.querySelector('input[name="popup_camera"]:checked') ||
                    document.querySelector('input[name="under_display_camera"]:checked') ||
                    document.querySelector('input[name="battery_sic"]:checked') ||
                    document.querySelector('input[name="battery_removable"]:checked') ||
                    document.querySelectorAll('input[name="ip_certificate[]"]:checked').length > 0 ||
                    document.querySelectorAll('input[name="form_factor[]"]:checked').length > 0 ||
                    (document.querySelector('input.life')?.value.trim() !== '')) {
                    hasFilter = true;
                }

                // If no filters selected, show alert and stop
                if (!hasFilter) {
                    alert('Please select at least one filter to search for devices. This helps improve performance and provides more relevant results.');
                    return;
                }

                // Show loading state
                findBtn.disabled = true;
                findBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-2" style="pointer-events: none;"></i><span style="pointer-events: none;">Searching...</span>';

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

                // Display Resolution range if not default
                if (displayResMinInput && displayResMaxInput) {
                    const minRes = parseInt(displayResMinInput.value);
                    const maxRes = parseInt(displayResMaxInput.value);
                    if (minRes > 480) formData.append('display_res_min', minRes);
                    if (maxRes < 4320) formData.append('display_res_max', maxRes);
                }

                // Display Technology checkboxes
                document.querySelectorAll('input[name="display_tech[]"]:checked').forEach(cb => {
                    formData.append('display_tech[]', cb.value);
                });

                // Display Notch checkboxes
                document.querySelectorAll('input[name="display_notch[]"]:checked').forEach(cb => {
                    formData.append('display_notch[]', cb.value);
                });

                // Refresh Rate min if > 0
                if (refreshRateMinInput) {
                    const rr = parseInt(refreshRateMinInput.value);
                    if (rr > 0) formData.append('refresh_rate_min', rr);
                }

                // HDR
                const hdrCb = document.querySelector('input[name="hdr"]');
                if (hdrCb && hdrCb.checked) formData.append('hdr', '1');

                // Billion colors
                const billionColorsCb = document.querySelector('input[name="billion_colors"]');
                if (billionColorsCb && billionColorsCb.checked) formData.append('billion_colors', '1');

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
                // F-number max (aperture)
                if (fNumberMaxInput) {
                    const fmax = parseFloat(fNumberMaxInput.value);
                    if (!isNaN(fmax) && fmax > 0) formData.append('f_number_max', fmax.toFixed(1));
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

                // CPU min GHz
                if (cpuClockMinInput) {
                    const cpuMin = parseFloat(cpuClockMinInput.value);
                    if (!isNaN(cpuMin) && cpuMin > 0) formData.append('cpu_clock_min', cpuMin.toFixed(1));
                }

                // Body dimensions/weight
                if (heightMinInput) {
                    const h = parseInt(heightMinInput.value);
                    if (!isNaN(h) && h > 0) formData.append('height_min', h);
                }
                if (thicknessMaxInput) {
                    const t = parseFloat(thicknessMaxInput.value);
                    if (!isNaN(t) && t > 0) formData.append('thickness_max', t.toFixed(1));
                }
                if (widthMinInput) {
                    const w = parseInt(widthMinInput.value);
                    if (!isNaN(w) && w > 0) formData.append('width_min', w);
                }
                if (weightMaxInput) {
                    const wg = parseInt(weightMaxInput.value);
                    if (!isNaN(wg) && wg > 0) formData.append('weight_max', wg);
                }

                // Materials & colors
                document.querySelectorAll('input[name="color[]"]:checked').forEach(cb => {
                    formData.append('color[]', cb.value);
                });
                document.querySelectorAll('input[name="frame_material[]"]:checked').forEach(cb => {
                    formData.append('frame_material[]', cb.value);
                });
                document.querySelectorAll('input[name="back_material[]"]:checked').forEach(cb => {
                    formData.append('back_material[]', cb.value);
                });

                // Network filters (SIM/Network bands)
                // 2G bands
                document.querySelectorAll('input.network-2g-band:checked').forEach(cb => {
                    formData.append('network_2g_bands[]', cb.value);
                });
                // 3G bands
                document.querySelectorAll('input.network-3g-band:checked').forEach(cb => {
                    formData.append('network_3g_bands[]', cb.value);
                });
                // 4G bands
                document.querySelectorAll('input.network-4g-band:checked').forEach(cb => {
                    formData.append('network_4g_bands[]', cb.value);
                });
                // 5G bands
                document.querySelectorAll('input.network-5g-band:checked').forEach(cb => {
                    formData.append('network_5g_bands[]', cb.value);
                });
                // Dual SIM
                const dualSimInput = document.getElementById('dualSim');
                if (dualSimInput && dualSimInput.checked) {
                    formData.append('dual_sim', '1');
                }
                // eSIM
                const esimSupportInput = document.getElementById('esimSupport');
                if (esimSupportInput && esimSupportInput.checked) {
                    formData.append('esim', '1');
                }
                // SIM sizes
                document.querySelectorAll('input[name="sim_sizes[]"]:checked').forEach(cb => {
                    formData.append('sim_sizes[]', cb.value);
                });

                // WiFi/WLAN versions
                document.querySelectorAll('input.wifi-version:checked').forEach(cb => {
                    formData.append('wifi_versions[]', cb.value);
                });
                // Bluetooth versions
                document.querySelectorAll('input.bluetooth-version:checked').forEach(cb => {
                    formData.append('bluetooth_versions[]', cb.value);
                });
                // USB types
                document.querySelectorAll('input.usb-type:checked').forEach(cb => {
                    formData.append('usb_types[]', cb.value);
                });
                // GPS required
                const gpsInput = document.getElementById('gpsRequired');
                if (gpsInput && gpsInput.checked) {
                    formData.append('gps_required', '1');
                }
                // NFC required
                const nfcInput = document.getElementById('nfcRequired');
                if (nfcInput && nfcInput.checked) {
                    formData.append('nfc_required', '1');
                }
                // Infrared required
                const infraredInput = document.getElementById('infraredRequired');
                if (infraredInput && infraredInput.checked) {
                    formData.append('infrared_required', '1');
                }
                // FM Radio required
                const fmRadioInput = document.getElementById('fmRadioRequired');
                if (fmRadioInput && fmRadioInput.checked) {
                    formData.append('fm_radio_required', '1');
                }

                // Sensors (existing UI checkboxes)
                const accelCb = document.querySelector('input[name="accelerometer"]');
                if (accelCb && accelCb.checked) formData.append('accelerometer', '1');
                const gyroCb = document.querySelector('input[name="gyro"]');
                if (gyroCb && gyroCb.checked) formData.append('gyro', '1');
                const barometerCb = document.querySelector('input[name="barometer"]');
                if (barometerCb && barometerCb.checked) formData.append('barometer', '1');
                const heartRateCb = document.querySelector('input[name="heart_rate"]');
                if (heartRateCb && heartRateCb.checked) formData.append('heart_rate', '1');
                const compassCb = document.querySelector('input[name="compass"]');
                if (compassCb && compassCb.checked) formData.append('compass', '1');
                const proximityCb = document.querySelector('input[name="proximity"]');
                if (proximityCb && proximityCb.checked) formData.append('proximity', '1');

                // Audio (existing UI checkboxes)
                const hpJackCb = document.querySelector('input[name="headphone_jack"]');
                if (hpJackCb && hpJackCb.checked) formData.append('headphone_jack', '1');
                const dualSpkCb = document.querySelector('input[name="dual_speakers"]');
                if (dualSpkCb && dualSpkCb.checked) formData.append('dual_speakers', '1');

                // Main Camera features (existing UI checkboxes)
                const telephotoCheckbox = document.querySelector('input[name="main_camera_telephoto"]');
                if (telephotoCheckbox && telephotoCheckbox.checked) formData.append('main_camera_telephoto', '1');
                const ultrawideCheckbox = document.querySelector('input[name="main_camera_ultrawide"]');
                if (ultrawideCheckbox && ultrawideCheckbox.checked) formData.append('main_camera_ultrawide', '1');
                const oisCheckbox = document.querySelector('input[name="main_camera_ois"]');
                if (oisCheckbox && oisCheckbox.checked) formData.append('main_camera_ois', '1');

                // Selfie Camera features (existing UI checkboxes)
                const selfieFlashCheckbox = document.querySelector('input[name="selfie_camera_flash"]');
                if (selfieFlashCheckbox && selfieFlashCheckbox.checked) formData.append('selfie_camera_flash', '1');
                const popupCameraCheckbox = document.querySelector('input[name="popup_camera"]');
                if (popupCameraCheckbox && popupCameraCheckbox.checked) formData.append('popup_camera', '1');
                const underDisplayCameraCheckbox = document.querySelector('input[name="under_display_camera"]');
                if (underDisplayCameraCheckbox && underDisplayCameraCheckbox.checked) formData.append('under_display_camera', '1');

                // Battery extras (existing UI checkboxes)
                const batterySiCCheckbox = document.querySelector('input[name="battery_sic"]');
                if (batterySiCCheckbox && batterySiCCheckbox.checked) formData.append('battery_sic', '1');
                const batteryRemovableCheckbox = document.querySelector('input[name="battery_removable"]');
                if (batteryRemovableCheckbox && batteryRemovableCheckbox.checked) formData.append('battery_removable', '1');

                // IP certificate (multi-select)
                document.querySelectorAll('input[name="ip_certificate[]"]:checked').forEach(cb => {
                    formData.append('ip_certificate[]', cb.value);
                });

                // Form factor (multi-select)
                document.querySelectorAll('input[name="form_factor[]"]:checked').forEach(cb => {
                    formData.append('form_factor[]', cb.value);
                });

                // Free Text (Misc)
                const freeTextInput = document.querySelector('input.life');
                if (freeTextInput && freeTextInput.value.trim() !== '') {
                    formData.append('free_text', freeTextInput.value.trim());
                }

                // Orders (sorting options) - pick the first checked option
                let orderValue = null;
                document.querySelectorAll('#popularCollapse .form-check').forEach(fc => {
                    if (orderValue) return; // already chosen one
                    const inp = fc.querySelector('input');
                    const lbl = fc.querySelector('label');
                    if (inp && inp.checked && lbl) {
                        const t = lbl.textContent.trim().toLowerCase();
                        if (t.startsWith('price')) orderValue = 'price';
                        else if (t.startsWith('camera')) orderValue = 'camera_battery';
                        else if (t.startsWith('popularity')) orderValue = 'popularity';
                    }
                });
                if (orderValue) formData.append('order', orderValue);

                // Fingerprint (from Fingerprint collapse by label text)
                const fpConds = [];
                document.querySelectorAll('#fingerCollapse .form-check').forEach(fc => {
                    const inp = fc.querySelector('input');
                    const lbl = fc.querySelector('label');
                    if (inp && inp.checked && lbl) {
                        const t = lbl.textContent.trim().toLowerCase();
                        if (t.includes('any')) fpConds.push('any');
                        else if (t.includes('rear')) fpConds.push('rear');
                        else if (t.includes('side')) fpConds.push('side');
                        else if (t.includes('under display') || t.includes('under-display') || t.includes('in-display')) fpConds.push('under_display');
                    }
                });
                // Deduplicate and append
                [...new Set(fpConds)].forEach(v => formData.append('fingerprint[]', v));

                // Send AJAX request
                fetch('phonefinder_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('HTTP error! status: ' + response.status);
                        }
                        return response.text();
                    })
                    .then(text => {
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            console.error('Response text:', text);
                            throw new Error('Invalid JSON response from server');
                        }

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
                        console.error('Full error details:', error);
                        alert('An error occurred while searching: ' + error.message + '\n\nPlease check the browser console for details.');
                    })
                    .finally(() => {
                        // Reset button state
                        findBtn.disabled = false;
                        findBtn.innerHTML = '<i class="fa fa-search me-2" style="pointer-events: none;"></i><span style="pointer-events: none;">Find Devices</span>';
                    });
            });

            function createDeviceCard(device) {
                return `
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <a href="<?php echo $base; ?>device/${encodeURIComponent(device.slug || device.id)}" class="text-decoration-none">
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
        // Show brands modal
        function showBrandsModal() {
            const modal = new bootstrap.Modal(document.getElementById('brandsModal'));
            modal.show();
        }

        // Handle brand selection from modal
        function selectBrandFromModal(brandId) {
            // Close the brands modal
            const brandsModal = bootstrap.Modal.getInstance(document.getElementById('brandsModal'));
            if (brandsModal) {
                brandsModal.hide();
            }

            // Fetch phones for this brand
            fetch(`get_phones_by_brand.php?brand_id=${brandId}`)
                .then(response => response.json())
                .then(data => {
                    // Populate the devices modal with phones
                    displayPhonesModal(data, brandId);
                })
                .catch(error => {
                    console.error('Error fetching phones:', error);
                    alert('Failed to load phones');
                });
        }

        // Display phones in modal
        function displayPhonesModal(phones, brandId) {
            const container = document.getElementById('deviceModalBody');
            const titleElement = document.getElementById('deviceModalTitle');

            // Update title with brand name
            const brandButton = document.querySelector(`[data-brand-id="${brandId}"]`);
            const brandName = brandButton ? brandButton.textContent.trim() : 'Brand';
            titleElement.innerHTML = `<i class="fas fa-mobile-alt me-2"></i>${brandName} - Devices`;

            if (phones && phones.length > 0) {
                let html = '<div class="row">';
                phones.forEach(phone => {
                    const phoneImage = phone.image ? `<img src="${phone.image}" alt="${phone.name}" style="width: 100%; height: 120px; object-fit: contain; margin-bottom: 8px;" onerror="this.style.display='none';">` : '';
                    html += `
          <div class="col-lg-4 col-md-6 col-sm-6 mb-3">
            <button class="device-cell-modal btn w-100 p-0" style="background-color: #fff; border: 1px solid #c5b6b0; color: #5D4037; font-weight: 500; transition: all 0.3s ease; cursor: pointer; display: flex; flex-direction: column; align-items: center; overflow: hidden;" onclick="goToDevice(${phone.id})">
              ${phoneImage}
              <span style="padding: 8px 10px; width: 100%; text-align: center; font-size: 0.95rem;">${phone.name}</span>
            </button>
          </div>
        `;
                });
                html += '</div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = `
        <div class="text-center py-5">
          <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
          <h6 class="text-muted">No devices available for this brand</h6>
        </div>
      `;
            }

            // Show devices modal
            const devicesModal = new bootstrap.Modal(document.getElementById('devicesModal'));
            devicesModal.show();
        }

        // Navigate to device page
        function goToDevice(deviceSlugOrId) {
            if (typeof deviceSlugOrId === 'string' && /[a-z-]/.test(deviceSlugOrId)) {
                window.location.href = `<?php echo $base; ?>device/${encodeURIComponent(deviceSlugOrId)}`;
            } else {
                window.location.href = `<?php echo $base; ?>device/${deviceSlugOrId}`;
            }
        }
    </script>
    <?php
    include 'includes/gsmfooter.php';
    ?>
    <!-- Brands Modal -->
    <div class="modal fade" id="brandsModal" tabindex="-1" aria-labelledby="brandsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="background-color: #EFEBE9; border: 2px solid #8D6E63;">
                <div class="modal-header" style="border-bottom: 1px solid #8D6E63; background-color: #D7CCC8;">
                    <h5 class="modal-title" id="brandsModalLabel" style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue'; color: #5D4037;">
                        <i class="fas fa-industry me-2"></i>All Brands
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <?php if (!empty($allBrandsModal)): ?>
                            <?php foreach ($allBrandsModal as $brand): ?>
                                <div class="col-lg-4 col-md-6 col-sm-6 mb-3">
                                    <button class="brand-cell-modal btn w-100 py-2 px-3" style="background-color: #fff; border: 1px solid #c5b6b0; color: #5D4037; font-weight: 500; transition: all 0.3s ease; cursor: pointer;" data-brand-id="<?php echo $brand['id']; ?>" onclick="selectBrandFromModal(<?php echo $brand['id']; ?>)">
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="fas fa-industry fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">No brands available</h6>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Devices Modal (Phones by Brand) -->
    <div class="modal fade" id="devicesModal" tabindex="-1" aria-labelledby="deviceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="background-color: #EFEBE9; border: 2px solid #8D6E63;">
                <div class="modal-header" style="border-bottom: 1px solid #8D6E63; background-color: #D7CCC8;">
                    <h5 class="modal-title" id="deviceModalTitle" style="font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue'; color: #5D4037;">
                        Devices
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="deviceModalBody">
                    <div class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Newsletter form AJAX handler
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('newsletter_form');
            const messageContainer = document.getElementById('newsletter_message_container');
            const emailInput = document.getElementById('newsletter_email');
            const submitBtn = document.getElementById('newsletter_btn');

            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const email = emailInput.value.trim();
                    const originalBtnText = submitBtn.textContent;

                    if (!email) {
                        showMessage('Please enter an email address.', 'error');
                        return;
                    }

                    // Disable button and show loading state
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Subscribing...';

                    // Send AJAX request
                    fetch('handle_newsletter.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'newsletter_email=' + encodeURIComponent(email)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showMessage(data.message, 'success');
                                emailInput.value = '';
                                // Auto-clear message after 5 seconds
                                setTimeout(() => {
                                    messageContainer.innerHTML = '';
                                }, 5000);
                            } else {
                                showMessage(data.message, 'error');
                            }
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalBtnText;
                        })
                        .catch(error => {
                            showMessage('An error occurred. Please try again.', 'error');
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalBtnText;
                        });
                });

                function showMessage(message, type) {
                    const bgColor = type === 'success' ? '#4CAF50' : '#f44336';
                    messageContainer.innerHTML = '<div style="background-color: ' + bgColor + '; color: white; padding: 12px; border-radius: 4px; margin-bottom: 12px; text-align: center; animation: slideIn 0.3s ease-in-out;">' + message + '</div>';

                    // Add animation style
                    if (!document.querySelector('style[data-newsletter]')) {
                        const style = document.createElement('style');
                        style.setAttribute('data-newsletter', 'true');
                        style.textContent = '@keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }';
                        document.head.appendChild(style);
                    }
                }
            }
        });
    </script>
    <script src="<?php echo $base; ?>script.js"></script>
</body>

</html>