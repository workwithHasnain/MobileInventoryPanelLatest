<?php
// Phone Finder - Public page for advanced device filtering
// No authentication required
require_once 'includes/database_functions.php';

// Get all devices from database
$devices = getAllPhonesDB();

// Get unique values for dynamic filter options from database
$brands = getAllBrandsDB();
$chipsets = getAllChipsetsDB();
$fallback_used = false;

// Extract device data for unique values
$device_brands = array_unique(array_column($devices, 'brand'));
$device_chipsets = array_unique(array_filter(array_column($devices, 'chipset')));
$device_os = array_unique(array_filter(array_column($devices, 'os')));

sort($device_brands);
sort($device_chipsets);
sort($device_os);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Finder - Mobile Device Catalog</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .filter-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 10px 15px;
            margin: 15px 0 10px 0;
            border-radius: 5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .filter-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .btn-toggle {
            background: #e9ecef;
            border: 1px solid #dee2e6;
            color: #495057;
            transition: all 0.3s ease;
            border-radius: 1px !important;
        }

        .btn-toggle:hover {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }

        .filter-box {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background: white;
            margin: 10px 0;
        }

        .filter-label {
            font-weight: bold;
            min-width: 120px;
        }

        .custom-range {
            flex-grow: 1;
        }

        .device-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }

        .device-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .device-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .results-container {
            max-height: 80vh;
            overflow-y: auto;
        }

        .no-results {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }

        .apply-filters-btn {
            position: sticky;
            bottom: 20px;
            z-index: 100;
        }

        .life {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px;
        }

        .styled {
            margin-top: 8px;
        }

        .mT-3 {
            margin-top: 1rem;
        }

        .vwr {
            min-height: 100vh;
        }
    </style>
</head>

<body>
    <!-- Public Navigation bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mobile-alt me-2"></i>
                Mobile Device Catalog
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="compare_phones.php">Compare Devices</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="phone_finder.php">Phone Finder</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="featured_posts.php">Featured Posts</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Admin Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main content container -->
    <div class="container bg-white mt-3 vwr">
        <div class="row mb-4">
            <div class="col">
                <h1><i class="fas fa-search me-2"></i>Phone Finder</h1>
                <p class="text-muted">Find your perfect device with advanced filtering options</p>
            </div>
        </div>

        <div class="row">
            <!-- Filter Panel -->
            <div class="col-lg-6 col-12">
                <div class="filter-header">General</div>
                <div class="filter-container container">
                    <form id="filterForm">
                        <!-- Device Type Filter -->
                        <button class="btn btn-toggle w-100 text-start mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#typeCollapse" aria-expanded="true">
                            Type
                        </button>
                        <div class="collapse show" id="typeCollapse">
                            <div class="card card-body py-2 px-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Phone" id="typePhone" name="device_type">
                                    <label class="form-check-label" for="typePhone">Phone</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Tablet" id="typeTablet" name="device_type">
                                    <label class="form-check-label" for="typeTablet">Tablet</label>
                                </div>
                            </div>
                        </div>

                        <!-- Brand Filter -->
                        <button class="btn btn-toggle w-100 text-start mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#brandCollapse" aria-expanded="false">
                            Brand
                        </button>
                        <div class="collapse" id="brandCollapse">
                            <div class="card card-body py-2 px-3">
                                <?php foreach ($brands as $brand): ?>
                                    <?php
                                    $brandName = $brand['name'];
                                    if (!empty($brandName)): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($brandName); ?>" id="brand<?php echo htmlspecialchars($brandName); ?>" name="brand">
                                            <label class="form-check-label" for="brand<?php echo htmlspecialchars($brandName); ?>"><?php echo htmlspecialchars($brandName); ?></label>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Availability Filter -->
                        <button class="btn btn-toggle w-100 text-start mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#networkCollapse" aria-expanded="false">
                            Availability
                        </button>
                        <div class="collapse" id="networkCollapse">
                            <div class="card card-body py-2 px-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Available" id="networkWifi" name="availability">
                                    <label class="form-check-label" for="networkWifi">Available</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Coming Soon" id="network4G" name="availability">
                                    <label class="form-check-label" for="network4G">Coming Soon</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Discontinued" id="network5G" name="availability">
                                    <label class="form-check-label" for="network5G">Discontinued</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Rumored" id="network5G2" name="availability">
                                    <label class="form-check-label" for="network5G2">Rumored</label>
                                </div>
                            </div>
                        </div>

                        <!-- SIM Section -->
                        <div class="filter-header mx-1 mb-2">Sim</div>
                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-toggle w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#simCollapse" aria-expanded="false">
                                    2G
                                </button>
                                <div class="collapse" id="simCollapse">
                                    <div class="card card-body px-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="GSM 850" id="gsm850" name="network_2g">
                                            <label class="form-check-label" for="gsm850">GSM 850</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="GSM 900" id="gsm900" name="network_2g">
                                            <label class="form-check-label" for="gsm900">GSM 900</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="GSM 1800" id="gsm1800" name="network_2g">
                                            <label class="form-check-label" for="gsm1800">GSM 1800</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="GSM 1900" id="gsm1900" name="network_2g">
                                            <label class="form-check-label" for="gsm1900">GSM 1900</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <button class="btn btn-toggle w-100 text-start mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#simwasCollapse" aria-expanded="false">
                                    3G
                                </button>
                                <div class="collapse" id="simwasCollapse">
                                    <div class="card card-body py-2 px-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="HSPA 850" id="hspa850" name="network_3g">
                                            <label class="form-check-label" for="hspa850">HSPA 850</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="HSPA 900" id="hspa900" name="network_3g">
                                            <label class="form-check-label" for="hspa900">HSPA 900</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="HSPA 1700" id="hspa1700" name="network_3g">
                                            <label class="form-check-label" for="hspa1700">HSPA 1700</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="HSPA 1900" id="hspa1900" name="network_3g">
                                            <label class="form-check-label" for="hspa1900">HSPA 1900</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="HSPA 2100" id="hspa2100" name="network_3g">
                                            <label class="form-check-label" for="hspa2100">HSPA 2100</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dual SIM and eSIM -->
                        <div class="row">
                            <div class="col-lg-6 d-flex align-items-center justify-content-center">
                                <label class="btn w-100 text-start mb-0 fw-bolder">
                                    <input type="checkbox" class="form-check-input me-2 float-end" name="dual_sim" value="1"> DUAL SIM
                                </label>
                            </div>
                            <div class="col-lg-6 d-flex align-items-center justify-content-center">
                                <label class="btn w-100 text-start mb-0 fw-bolder">
                                    <input type="checkbox" class="form-check-input me-2 float-end" name="esim" value="1"> ESIM
                                </label>
                            </div>
                        </div>

                        <!-- BODY Section -->
                        <div class="filter-header mx-1 mb-3">BODY</div>
                        <!-- Form Factor -->
                        <button class="btn btn-toggle w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#factorCollapse" aria-expanded="false">
                            Form Factor
                        </button>
                        <div class="collapse" id="factorCollapse">
                            <div class="card card-body px-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Bar" id="miniSim1" name="form_factor">
                                    <label class="form-check-label" for="miniSim1">Bar</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Flip up" id="nanoSim1" name="form_factor">
                                    <label class="form-check-label" for="nanoSim1">Flip up</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Flip down" id="microSim1" name="form_factor">
                                    <label class="form-check-label" for="microSim1">Flip down</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Swivel" id="microSim2" name="form_factor">
                                    <label class="form-check-label" for="microSim2">Swivel</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Slide" id="microSim3" name="form_factor">
                                    <label class="form-check-label" for="microSim3">Slide</label>
                                </div>
                            </div>
                        </div>

                        <!-- Height Range -->
                        <div class="filter-box">
                            <span class="filter-label">Height:</span>
                            <span id="heightValue">min</span>
                            <input type="range" class="form-range custom-range" min="0" max="200" value="0" id="heightRange" name="height_min">
                            <span class="text-muted">max</span>
                        </div>

                        <!-- Thickness Range -->
                        <div class="filter-box">
                            <span class="filter-label">Thickness:</span>
                            <span id="thicknessValue">min</span>
                            <input type="range" class="form-range custom-range" min="0" max="25" value="0" id="thicknessRange" name="thickness_min">
                            <span class="text-muted">max</span>
                        </div>

                        <!-- IP Certificate -->
                        <button class="btn btn-toggle w-100 mt-2 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#ipCollapse" aria-expanded="false">
                            IP CERTIFICATE
                        </button>
                        <div class="collapse" id="ipCollapse">
                            <div class="card card-body px-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="MIL-STD-810F" id="milStdF" name="ip_certificate">
                                    <label class="form-check-label" for="milStdF">MIL-STD-810F</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="MIL-STD-810D" id="milStdD" name="ip_certificate">
                                    <label class="form-check-label" for="milStdD">MIL-STD-810D</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="MIL-STD-810H" id="milStdH" name="ip_certificate">
                                    <label class="form-check-label" for="milStdH">MIL-STD-810H</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="MIL-STD-810G" id="milStdG" name="ip_certificate">
                                    <label class="form-check-label" for="milStdG">MIL-STD-810G</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="IP68" id="ip68" name="ip_certificate">
                                    <label class="form-check-label" for="ip68">IP68</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="IP67" id="ip67" name="ip_certificate">
                                    <label class="form-check-label" for="ip67">IP67</label>
                                </div>
                            </div>
                        </div>

                        <!-- Back Material -->
                        <button class="btn btn-toggle w-100 mt-2 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#backCollapse" aria-expanded="false">
                            Back Material
                        </button>
                        <div class="collapse" id="backCollapse">
                            <div class="card card-body px-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Plastic" id="backPlastic" name="back_material">
                                    <label class="form-check-label" for="backPlastic">Plastic</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Aluminum" id="backAluminum" name="back_material">
                                    <label class="form-check-label" for="backAluminum">Aluminum</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Glass" id="backGlass" name="back_material">
                                    <label class="form-check-label" for="backGlass">Glass</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Ceramic" id="backCeramic" name="back_material">
                                    <label class="form-check-label" for="backCeramic">Ceramic</label>
                                </div>
                            </div>
                        </div>

                        <!-- Platforms Section -->
                        <div class="filter-header mx-1 mb-2">Platforms</div>
                        <!-- Operating System -->
                        <button class="btn btn-toggle w-100 mt-2 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#osCollapse" aria-expanded="false">
                            OS:
                        </button>
                        <div class="collapse" id="osCollapse">
                            <div class="card card-body px-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Feature phones" id="osFeature" name="os">
                                    <label class="form-check-label" for="osFeature">Feature phones</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Android" id="osAndroid" name="os">
                                    <label class="form-check-label" for="osAndroid">Android</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="iOS" id="osiOS" name="os">
                                    <label class="form-check-label" for="osiOS">iOS</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Windows Phone" id="osWindows" name="os">
                                    <label class="form-check-label" for="osWindows">Windows Phone</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Symbian" id="osSymbian" name="os">
                                    <label class="form-check-label" for="osSymbian">Symbian</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="RIM Bada" id="osRimBada" name="os">
                                    <label class="form-check-label" for="osRimBada">RIM Bada</label>
                                </div>
                            </div>
                        </div>

                        <!-- Chipset -->
                        <button class="btn btn-toggle w-100 mt-2 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#chipsCollapse" aria-expanded="false">
                            CHIPSET:
                        </button>
                        <div class="collapse" id="chipsCollapse">
                            <div class="card card-body px-3">
                                <?php foreach ($chipsets as $chipset): ?>
                                    <?php
                                    $chipsetName = $chipset['name'];
                                    if (!empty($chipsetName)): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($chipsetName); ?>" id="chipset<?php echo md5($chipsetName); ?>" name="chipset">
                                            <label class="form-check-label" for="chipset<?php echo md5($chipsetName); ?>"><?php echo htmlspecialchars($chipsetName); ?></label>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Any Dimensity" id="dimensity" name="chipset">
                                    <label class="form-check-label" for="dimensity">Any Dimensity</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Snapdragon 8 Gen 3" id="snap8gen3" name="chipset">
                                    <label class="form-check-label" for="snap8gen3">Snapdragon 8 Gen 3</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Any Helio" id="helio" name="chipset">
                                    <label class="form-check-label" for="helio">Any Helio</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Any Kirin" id="kirin" name="chipset">
                                    <label class="form-check-label" for="kirin">Any Kirin</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Snapdragon 8 Elite" id="snap8elite" name="chipset">
                                    <label class="form-check-label" for="snap8elite">Snapdragon 8 Elite</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Snapdragon 8 Gen 1" id="snap8gen1" name="chipset">
                                    <label class="form-check-label" for="snap8gen1">Snapdragon 8 Gen 1</label>
                                </div>
                            </div>
                        </div>

                        <!-- Sensors Section -->
                        <div class="filter-header mx-1 mb-4">SENSORS</div>
                        <div class="row">
                            <div class="col-lg-6 d-flex align-items-center justify-content-center">
                                <label class="btn w-100 text-start mb-0 fw-bolder">
                                    <input type="checkbox" class="form-check-input me-2 float-end" name="accelerometer" value="1"> ACCELEROMETER
                                </label>
                            </div>
                            <div class="col-lg-6 d-flex align-items-center justify-content-center">
                                <label class="btn w-100 text-start mb-0 fw-bolder">
                                    <input type="checkbox" class="form-check-input me-2 float-end" name="gyro" value="1"> GYRO
                                </label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 d-flex align-items-center mt-2 justify-content-center">
                                <label class="btn w-100 text-start mb-0 fw-bolder">
                                    <input type="checkbox" class="form-check-input me-2 float-end" name="barometer" value="1"> BAROMETER
                                </label>
                            </div>
                            <div class="col-lg-6 d-flex align-items-center mt-2 justify-content-center">
                                <label class="btn w-100 text-start mb-0 fw-bolder">
                                    <input type="checkbox" class="form-check-input me-2 float-end" name="heart_rate" value="1"> HEART RATE
                                </label>
                            </div>
                        </div>

                        <!-- Memory Section -->
                        <div class="filter-header mx-1 mb-2">Memory</div>
                        <div class="filter-box">
                            <span class="filter-label">RAM:</span>
                            <span id="ramValue">Any</span>
                            <input type="range" class="form-range custom-range" min="1" max="32" value="1" id="ramRange" name="ram_min">
                        </div>

                        <!-- Card Slot -->
                        <button class="btn btn-toggle w-100 mt-2 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#cardCollapse" aria-expanded="false">
                            Card Slot:
                        </button>
                        <div class="collapse" id="cardCollapse">
                            <div class="card card-body px-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Yes (any type)" id="cardAnyType" name="card_slot">
                                    <label class="form-check-label" for="cardAnyType">Yes (any type)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Yes (dedicated)" id="cardDedicated" name="card_slot">
                                    <label class="form-check-label" for="cardDedicated">Yes (dedicated)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="No" id="cardNo" name="card_slot">
                                    <label class="form-check-label" for="cardNo">No</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-6 col-12 pt-3">
                <!-- Year and Price Ranges -->
                <div class="filter-box">
                    <span class="filter-label">Year:</span>
                    <span id="yearValue">Min</span>
                    <input type="range" class="form-range custom-range" min="2000" max="2025" value="2000" id="yearRange" name="year_min">
                    <span class="text-muted">Max</span>
                </div>
                <div class="filter-box">
                    <span class="filter-label">Price:</span>
                    <span id="priceValue">Min</span>
                    <input type="range" class="form-range custom-range" min="0" max="2000" value="0" id="priceRange" name="price_min">
                    <span class="text-muted">Max</span>
                </div>

                <!-- 4G and 5G Networks -->
                <div class="row g-2 mt-5">
                    <div class="col-6 mt-3">
                        <button class="btn btn-toggle w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#fourGCollapse" aria-expanded="false">
                            4G
                        </button>
                        <div class="collapse" id="fourGCollapse">
                            <div class="card card-body px-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="LTE 700" id="lte700" name="network_4g">
                                    <label class="form-check-label" for="lte700">LTE 700</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="LTE 850" id="lte850" name="network_4g">
                                    <label class="form-check-label" for="lte850">LTE 850</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="LTE 900" id="lte900" name="network_4g">
                                    <label class="form-check-label" for="lte900">LTE 900</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="LTE 1800" id="lte1800" name="network_4g">
                                    <label class="form-check-label" for="lte1800">LTE 1800</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mt-3">
                        <button class="btn btn-toggle w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#fiveGCollapse" aria-expanded="false">
                            5G
                        </button>
                        <div class="collapse" id="fiveGCollapse">
                            <div class="card card-body px-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="NR 3500" id="nr3500" name="network_5g">
                                    <label class="form-check-label" for="nr3500">NR 3500</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="NR 3600" id="nr3600" name="network_5g">
                                    <label class="form-check-label" for="nr3600">NR 3600</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="NR 3700" id="nr3700" name="network_5g">
                                    <label class="form-check-label" for="nr3700">NR 3700</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="NR 3800" id="nr3800" name="network_5g">
                                    <label class="form-check-label" for="nr3800">NR 3800</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- USB -->
                <button class="btn btn-toggle w-100 mt-3 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#usbCollapse" aria-expanded="false">
                    USB
                </button>
                <div class="collapse" id="usbCollapse">
                    <div class="card card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Any USB-C" id="usbAnyC" name="usb">
                            <label class="form-check-label" for="usbAnyC">Any USB-C</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="USB-C 3.0 and higher" id="usbC3" name="usb">
                            <label class="form-check-label" for="usbC3">USB-C 3.0 and higher</label>
                        </div>
                    </div>
                </div>

                <!-- Battery Section -->
                <div class="filter-header mt-1 mb-2">Battery</div>
                <div class="filter-box">
                    <span class="filter-label">CAPACITY</span>
                    <span id="batteryValue">Any</span>
                    <input type="range" class="form-range custom-range" min="1000" max="10000" value="1000" id="batteryRange" name="battery_min">
                </div>
                <div class="filter-box mt-1">
                    <span class="filter-label">WIRED CHARGING:</span>
                    <span id="wiredChargingValue">min</span>
                    <input type="range" class="form-range custom-range" min="5" max="200" value="5" id="wiredChargingRange" name="wired_charging_min">
                    <span class="text-muted">max</span>
                </div>

                <!-- Misc Section -->
                <div class="filter-header mt-3 mb-3">Misc</div>
                <div class="filter-box">
                    <span class="filter-label">Free Text</span>
                    <input type="text" class="flex-grow-1 life" id="freeText" name="free_text" placeholder="Search specifications...">
                </div>

                <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2 mx-1">
                    <label class="btn w-100 text-start mb-0 fw-bolder">
                        <input type="checkbox" class="form-check-input me-2 float-end" name="review_only" value="1"> Review Only
                    </label>
                </div>

                <!-- Audio Section -->
                <div class="filter-header mT-3 mb-2">Audio</div>
                <div class="row">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2">
                        <label class="btn w-100 text-start mb-0 fw-bolder">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="headphone_jack" value="1"> 3.5MM JACK
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2">
                        <label class="btn w-100 text-start mb-0 fw-bolder">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="dual_speaker" value="1"> DUAL SPEAKER
                        </label>
                    </div>
                </div>
            </div>

            <!-- Display and Camera Section -->
            <div class="col-lg-6 mt-3 py-3">
                <!-- Display Size -->
                <div class="filter-box">
                    <span class="filter-label">Size:</span>
                    <span id="displaySizeValue">min</span>
                    <input type="range" class="form-range custom-range" min="3.0" max="15.0" step="0.1" value="3.0" id="displaySizeRange" name="display_size_min">
                    <span class="text-muted">max</span>
                </div>

                <!-- Notch -->
                <button class="btn w-100 btn-toggle mb-3 mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#notchCollapse" aria-expanded="false">
                    Notch
                </button>
                <div class="collapse" id="notchCollapse">
                    <div class="card card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="No" id="notchNo" name="notch">
                            <label class="form-check-label" for="notchNo">No</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Yes" id="notchYes" name="notch">
                            <label class="form-check-label" for="notchYes">Yes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Punch hole" id="notchPunch" name="notch">
                            <label class="form-check-label" for="notchPunch">Punch hole</label>
                        </div>
                    </div>
                </div>

                <!-- Display Features -->
                <div class="row">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn w-100 text-start mb-0 fw-bolder">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="hdr" value="1"> HDR
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn w-100 text-start mb-0 fw-bolder">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="billion_colors" value="1"> 1B+COLORS
                        </label>
                    </div>
                </div>

                <!-- Camera Features -->
                <div class="row mt-5">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2">
                        <label class="btn w-100 text-start mb-0 fw-bolder">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="cameras" value="1"> Cameras
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2">
                        <label class="btn w-100 text-start mb-0 fw-bolder">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="ios" value="1"> IOS
                        </label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2">
                        <label class="btn w-100 text-start mb-0 fw-bolder">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="telephoto" value="1"> TELEPHOTO
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2">
                        <label class="btn w-100 text-start mb-0 fw-bolder">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="ultrawide" value="1"> ULTRAWIDE
                        </label>
                    </div>
                </div>

                <!-- Flash -->
                <button class="btn w-100 btn-toggle mb-3 mt-1" type="button" data-bs-toggle="collapse" data-bs-target="#flashCollapse" aria-expanded="false">
                    FLASH
                </button>
                <div class="collapse" id="flashCollapse">
                    <div class="card card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="LED" id="flashLED" name="flash">
                            <label class="form-check-label" for="flashLED">LED</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Dual-LED" id="flashDualLED" name="flash">
                            <label class="form-check-label" for="flashDualLED">Dual-LED</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Xenon" id="flashXenon" name="flash">
                            <label class="form-check-label" for="flashXenon">Xenon</label>
                        </div>
                    </div>
                </div>

                <!-- More Camera Features -->
                <div class="row mt-1">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-3">
                        <label class="btn w-100 text-start mb-0 fw-bolder">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="dual_camera" value="1"> DUAL CAMERA
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center mt-3">
                        <label class="btn w-100 text-start mb-0 fw-bolder">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="ois" value="1"> OIS
                        </label>
                    </div>
                </div>
                <div class="col-lg-6 d-flex align-items-center justify-content-center mt-2">
                    <label class="btn w-100 text-start mb-0 fw-bolder">
                        <input type="checkbox" class="form-check-input me-2 float-end" name="under_display_camera" value="1"> UNDER DISPLAY CAMERA
                    </label>
                </div>

                <!-- Bluetooth -->
                <button class="btn btn-toggle w-100 mb-3 mt-5" type="button" data-bs-toggle="collapse" data-bs-target="#bluetoothCollapse" aria-expanded="false">
                    BLUETOOTH
                </button>
                <div class="collapse" id="bluetoothCollapse">
                    <div class="card card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Any Bluetooth" id="bluetoothAny" name="bluetooth">
                            <label class="form-check-label" for="bluetoothAny">Any Bluetooth</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Bluetooth 4.0" id="bluetooth40" name="bluetooth">
                            <label class="form-check-label" for="bluetooth40">Bluetooth 4.0</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Bluetooth 4.1" id="bluetooth41" name="bluetooth">
                            <label class="form-check-label" for="bluetooth41">Bluetooth 4.1</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Bluetooth 4.2" id="bluetooth42" name="bluetooth">
                            <label class="form-check-label" for="bluetooth42">Bluetooth 4.2</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Bluetooth 5.1" id="bluetooth51" name="bluetooth">
                            <label class="form-check-label" for="bluetooth51">Bluetooth 5.1</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Bluetooth 5.2" id="bluetooth52" name="bluetooth">
                            <label class="form-check-label" for="bluetooth52">Bluetooth 5.2</label>
                        </div>
                    </div>
                </div>

                <!-- Connectivity Features -->
                <div class="row" style="margin-top: -7px;">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn w-100 text-start mb-0 fw-bolder">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="infrared" value="1">INFRARED
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn w-100 text-start mb-0 fw-bolder">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="fm_radio" value="1"> FM-RADIO
                        </label>
                    </div>
                </div>
                <div class="row styled">
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn w-100 text-start mb-0 fw-bolder">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="sic" value="1"> SI/C
                        </label>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center justify-content-center">
                        <label class="btn w-100 text-start mb-0 fw-bolder">
                            <input type="checkbox" class="form-check-input me-2 float-end" name="removable" value="1"> REMOVABLE
                        </label>
                    </div>
                </div>

                <!-- Wireless Charging -->
                <div class="filter-box mt-1">
                    <span class="filter-label">WIRELESS CHARGING:</span>
                    <span id="wirelessChargingValue">min</span>
                    <input type="range" class="form-range custom-range" min="5" max="100" value="5" id="wirelessChargingRange" name="wireless_charging_min">
                    <span class="text-muted">max</span>
                </div>

                <!-- Orders -->
                <button class="btn btn-toggle w-100 mb-3 mt-5" type="button" data-bs-toggle="collapse" data-bs-target="#popularCollapse" aria-expanded="false">
                    ORDERS
                </button>
                <div class="collapse" id="popularCollapse">
                    <div class="card card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Popularity" id="orderPopularity" name="order">
                            <label class="form-check-label" for="orderPopularity">Popularity</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Price" id="orderPrice" name="order">
                            <label class="form-check-label" for="orderPrice">Price</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="Camera resolution Battery capacity" id="orderCamera" name="order">
                            <label class="form-check-label" for="orderCamera">Camera resolution Battery capacity</label>
                        </div>
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="apply-filters-btn mt-4">
                    <button type="button" id="applyFilters" class="btn btn-success w-100 btn-lg">
                        <i class="fas fa-search me-2"></i>Apply Filters
                    </button>
                    <button type="button" id="clearFilters" class="btn btn-outline-secondary w-100 mt-2">
                        <i class="fas fa-times me-2"></i>Clear All Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-mobile-alt me-2"></i>Search Results</h5>
                        <span id="resultCount" class="badge bg-light text-dark">Loading...</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="results-container" id="resultsContainer">
                            <div class="text-center p-4">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Loading devices...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Device data for JavaScript -->
    <script>
        const deviceData = <?php echo json_encode($devices); ?>;
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript for filtering -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize range sliders
            const ranges = {
                year: {
                    input: 'yearRange',
                    display: 'yearValue',
                    suffix: ''
                },
                price: {
                    input: 'priceRange',
                    display: 'priceValue',
                    prefix: '$',
                    suffix: ''
                },
                height: {
                    input: 'heightRange',
                    display: 'heightValue',
                    suffix: 'mm'
                },
                thickness: {
                    input: 'thicknessRange',
                    display: 'thicknessValue',
                    suffix: 'mm'
                },
                ram: {
                    input: 'ramRange',
                    display: 'ramValue',
                    suffix: 'GB'
                },
                displaySize: {
                    input: 'displaySizeRange',
                    display: 'displaySizeValue',
                    suffix: '"'
                },
                battery: {
                    input: 'batteryRange',
                    display: 'batteryValue',
                    suffix: 'mAh'
                },
                wiredCharging: {
                    input: 'wiredChargingRange',
                    display: 'wiredChargingValue',
                    suffix: 'W'
                },
                wirelessCharging: {
                    input: 'wirelessChargingRange',
                    display: 'wirelessChargingValue',
                    suffix: 'W'
                }
            };

            // Setup range sliders
            Object.keys(ranges).forEach(key => {
                const range = ranges[key];
                const input = document.getElementById(range.input);
                const display = document.getElementById(range.display);

                if (input && display) {
                    input.addEventListener('input', function() {
                        const prefix = range.prefix || '';
                        const suffix = range.suffix || '';
                        display.textContent = prefix + this.value + suffix;
                    });

                    // Initialize display
                    const prefix = range.prefix || '';
                    const suffix = range.suffix || '';
                    display.textContent = prefix + input.value + suffix;
                }
            });

            // Filter and display devices
            function filterDevices() {
                const formData = new FormData(document.getElementById('filterForm'));
                const filters = {};

                // Collect all form data
                for (let [key, value] of formData.entries()) {
                    if (!filters[key]) {
                        filters[key] = [];
                    }
                    filters[key].push(value);
                }

                // Get range values
                filters.year_min = document.getElementById('yearRange').value;
                filters.price_min = document.getElementById('priceRange').value;
                filters.height_min = document.getElementById('heightRange').value;
                filters.thickness_min = document.getElementById('thicknessRange').value;
                filters.ram_min = document.getElementById('ramRange').value;
                filters.display_size_min = document.getElementById('displaySizeRange').value;
                filters.battery_min = document.getElementById('batteryRange').value;
                filters.free_text = document.getElementById('freeText').value;

                // Filter devices
                let filteredDevices = deviceData.filter(device => {
                    // Device Type filter
                    if (filters.device_type && filters.device_type.length > 0) {
                        const deviceType = device.device_type || device.type || 'Phone'; // Default to Phone if not specified
                        if (!filters.device_type.includes(deviceType)) return false;
                    }

                    // Brand filter
                    if (filters.brand && filters.brand.length > 0) {
                        const deviceBrand = device.brand_name || device.brand;
                        if (!filters.brand.includes(deviceBrand)) return false;
                    }

                    // Availability filter
                    if (filters.availability && filters.availability.length > 0) {
                        if (!filters.availability.includes(device.availability)) return false;
                    }

                    // Year filter
                    if (filters.year_min && device.year) {
                        if (parseInt(device.year) < parseInt(filters.year_min)) return false;
                    }

                    // Price filter
                    if (filters.price_min && device.price) {
                        const price = parseFloat(device.price.replace(/[^0-9.]/g, ''));
                        if (price < parseFloat(filters.price_min)) return false;
                    }

                    // Operating System filter
                    if (filters.os && filters.os.length > 0) {
                        if (!filters.os.some(os => device.os && device.os.toLowerCase().includes(os.toLowerCase()))) return false;
                    }

                    // Chipset filter
                    if (filters.chipset && filters.chipset.length > 0) {
                        const deviceChipset = device.chipset_name || device.chipset || '';
                        if (!filters.chipset.some(chipset => {
                                if (chipset.startsWith('Any ')) {
                                    const chipsetType = chipset.replace('Any ', '');
                                    return deviceChipset.toLowerCase().includes(chipsetType.toLowerCase());
                                }
                                return deviceChipset.toLowerCase().includes(chipset.toLowerCase());
                            })) return false;
                    }

                    // Network filters
                    if (filters.network_2g && filters.network_2g.length > 0) {
                        const device2G = device.network_2g || '';
                        if (!filters.network_2g.some(band => device2G.includes(band))) return false;
                    }

                    if (filters.network_3g && filters.network_3g.length > 0) {
                        const device3G = device.network_3g || '';
                        if (!filters.network_3g.some(band => device3G.includes(band))) return false;
                    }

                    if (filters.network_4g && filters.network_4g.length > 0) {
                        const device4G = device.network_4g || '';
                        if (!filters.network_4g.some(band => device4G.includes(band))) return false;
                    }

                    if (filters.network_5g && filters.network_5g.length > 0) {
                        const device5G = device.network_5g || '';
                        if (!filters.network_5g.some(band => device5G.includes(band))) return false;
                    }

                    // Form factor filter
                    if (filters.form_factor && filters.form_factor.length > 0) {
                        if (!filters.form_factor.includes(device.form_factor)) return false;
                    }

                    // Back material filter
                    if (filters.back_material && filters.back_material.length > 0) {
                        if (!filters.back_material.includes(device.back_material)) return false;
                    }

                    // Dual SIM filter
                    if (filters.dual_sim && filters.dual_sim.includes('1')) {
                        if (!device.dual_sim) return false;
                    }

                    // eSIM filter
                    if (filters.esim && filters.esim.includes('1')) {
                        if (!device.esim) return false;
                    }

                    // Display size filter
                    if (filters.display_size_min && device.display_size) {
                        const displaySize = parseFloat(device.display_size);
                        if (displaySize < parseFloat(filters.display_size_min)) return false;
                    }

                    // Battery capacity filter
                    if (filters.battery_min && device.battery_capacity) {
                        const batteryCapacity = parseInt(device.battery_capacity.replace(/[^0-9]/g, ''));
                        if (batteryCapacity < parseInt(filters.battery_min)) return false;
                    }

                    // Free text search
                    if (filters.free_text && filters.free_text.trim()) {
                        const searchText = filters.free_text.toLowerCase();
                        const deviceText = JSON.stringify(device).toLowerCase();
                        if (!deviceText.includes(searchText)) return false;
                    }

                    return true;
                });

                // Display results
                displayResults(filteredDevices);
            }

            function displayResults(devices) {
                const container = document.getElementById('resultsContainer');
                const countDisplay = document.getElementById('resultCount');

                countDisplay.textContent = devices.length + ' devices found';

                if (devices.length === 0) {
                    container.innerHTML = `
                        <div class="no-results">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h4>No devices found</h4>
                            <p>Try adjusting your filters to see more results.</p>
                        </div>
                    `;
                    return;
                }

                let html = '<div class="row g-3 p-3">';
                devices.forEach((device, index) => {
                    // Handle database structure for images
                    let imageUrl = 'https://via.placeholder.com/200x200?text=No+Image';
                    if (device.images && device.images.length > 0) {
                        imageUrl = device.images[0];
                    } else if (device.image) {
                        imageUrl = device.image;
                    }

                    const deviceName = device.name || 'Unknown Device';
                    const deviceBrand = device.brand_name || device.brand || 'Unknown Brand';
                    const deviceChipset = device.chipset_name || device.chipset;
                    const price = device.price ? `$${device.price}` : 'Price not available';
                    const availability = device.availability || 'Unknown';

                    html += `
                        <div class="col-md-6 col-lg-4">
                            <div class="card device-card h-100">
                                <img src="${imageUrl}" class="card-img-top device-image" alt="${deviceName}" onerror="this.src='https://via.placeholder.com/200x200?text=No+Image'">
                                <div class="card-body">
                                    <h6 class="card-title text-truncate">${deviceName}</h6>
                                    <p class="card-text">
                                        <small class="text-muted">${deviceBrand}</small><br>
                                        <strong class="text-success">${price}</strong><br>
                                        <span class="badge bg-${availability === 'Available' ? 'success' : availability === 'Coming Soon' ? 'warning' : 'secondary'}">${availability}</span>
                                    </p>
                                    ${deviceChipset ? `<p class="small text-muted mb-1">Chipset: ${deviceChipset}</p>` : ''}
                                    ${device.os ? `<p class="small text-muted mb-1">OS: ${device.os}</p>` : ''}
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="btn-group w-100" role="group">
                                        <a href="device.php?id=${device.id || index}" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="compare_phones.php?device1=${encodeURIComponent(deviceName)}&brand1=${encodeURIComponent(deviceBrand)}" class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-exchange-alt"></i> Compare
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';

                container.innerHTML = html;
            }

            // Event listeners
            document.getElementById('applyFilters').addEventListener('click', filterDevices);

            document.getElementById('clearFilters').addEventListener('click', function() {
                document.getElementById('filterForm').reset();

                // Reset range displays
                Object.keys(ranges).forEach(key => {
                    const range = ranges[key];
                    const input = document.getElementById(range.input);
                    const display = document.getElementById(range.display);

                    if (input && display) {
                        const prefix = range.prefix || '';
                        const suffix = range.suffix || '';
                        display.textContent = prefix + input.value + suffix;
                    }
                });

                // Reset free text
                document.getElementById('freeText').value = '';

                // Show all devices
                displayResults(deviceData);
            });

            // Initial load - show all devices
            displayResults(deviceData);
        });
    </script>
</body>

</html>