<?php
require_once 'phone_data.php';
require_once 'database_functions.php';

// Public compare phones page - no authentication required

// Get all phones from database
$phones = getAllPhones();

// Get selected phone IDs from URL parameters
$phone1_id = isset($_GET['phone1']) ? $_GET['phone1'] : '';
$phone2_id = isset($_GET['phone2']) ? $_GET['phone2'] : '';
$phone3_id = isset($_GET['phone3']) ? $_GET['phone3'] : '';

// Handle device pre-selection from device page (device1, brand1 parameters)
if (isset($_GET['device1']) && ($phone1_id === '' || $phone1_id === null)) {
    $device_name = urldecode($_GET['device1']);
    $device_brand = isset($_GET['brand1']) ? urldecode($_GET['brand1']) : '';

    // Find the device in our phones array by name (and brand if provided)
    foreach ($phones as $phone) {
        $name_match = isset($phone['name']) && strtolower(trim($phone['name'])) === strtolower(trim($device_name));
        $brand_match = empty($device_brand) || (isset($phone['brand']) && strtolower(trim($phone['brand'])) === strtolower(trim($device_brand)));

        if ($name_match && $brand_match) {
            $phone1_id = $phone['id'];
            break;
        }
    }

    // If still not found and brand is empty, try searching by name only
    if (!$phone1_id && empty($device_brand)) {
        foreach ($phones as $phone) {
            if (isset($phone['name']) && strtolower(trim($phone['name'])) === strtolower(trim($device_name))) {
                $phone1_id = $phone['id'];
                break;
            }
        }
    }
}

// Helper function to find phone by ID
function findPhoneById($phones, $phoneId)
{
    if ($phoneId === '' || $phoneId === null || $phoneId === 'undefined' || $phoneId === '-1') {
        return null;
    }

    // First try to find by database ID
    foreach ($phones as $phone) {
        if (isset($phone['id']) && $phone['id'] == $phoneId) {
            return $phone;
        }
    }

    // Fallback: try to find by array index for backward compatibility
    if (is_numeric($phoneId)) {
        $index = (int)$phoneId;
        return (isset($phones[$index])) ? $phones[$index] : null;
    }

    return null;
}

// Get selected phones data
$phone1 = findPhoneById($phones, $phone1_id);
$phone2 = findPhoneById($phones, $phone2_id);
$phone3 = findPhoneById($phones, $phone3_id);

// Check if at least one phone is selected
$has_selection = ($phone1 !== null || $phone2 !== null || $phone3 !== null);

// Helper function to display availability
function displayAvailability($phone)
{
    if (isset($phone['availability'])) {
        if (is_array($phone['availability'])) {
            // Handle old format (array of checkboxes)
            $availability_options = [];
            foreach ($phone['availability'] as $option => $value) {
                if ($value) {
                    $availability_options[] = htmlspecialchars(ucfirst($option));
                }
            }
            return implode(', ', $availability_options);
        } else {
            // Handle new format (string from dropdown)
            return htmlspecialchars($phone['availability']);
        }
    }
    return '<span class="text-muted">Not specified</span>';
}

// Helper function to display network capabilities
function displayNetworkCapabilities($phone)
{
    $networks = [];
    if (isset($phone['3g']) && $phone['3g']) $networks[] = '3G';
    if (isset($phone['4g']) && $phone['4g']) $networks[] = '4G';
    if (isset($phone['5g']) && $phone['5g']) $networks[] = '5G';
    return !empty($networks) ? implode(', ', $networks) : '<span class="text-muted">None</span>';
}
?>

<?php
// Create a simple public header for compare phones page
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compare Phones - Mobile Device Catalog</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css2/styles.css">
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
                        <a class="nav-link active" href="compare_phones.php">Compare Devices</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="phone_finder.php">Phone Finder</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <a href="login.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-in-alt me-1"></i>
                        Admin Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main content container -->
    <main>

        <div class="container-fluid py-4">
            <div class="row mb-4">
                <div class="col">
                    <h1>Compare Phones</h1>
                    <p class="text-muted">Select up to three phones to compare their specifications</p>
                </div>
                <div class="col-auto">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Home
                    </a>
                </div>
            </div>

            <!-- Phone Selection Form -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Select Phones to Compare</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                        <div class="col-md-4">
                            <label for="phone1" class="form-label">Phone 1</label>
                            <input type="hidden" id="phone1" name="phone1" value="<?php echo $phone1_id; ?>">
                            <input type="text" class="form-control phone-search" id="phone1_search"
                                placeholder="Search for a phone..."
                                value="<?php echo ($phone1) ? htmlspecialchars($phone1['name'] . ' - ' . $phone1['brand'] . ($phone1['year'] ? ' (' . $phone1['year'] . ')' : '')) : ''; ?>">
                            <div class="search-results" id="phone1_results"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="phone2" class="form-label">Phone 2</label>
                            <input type="hidden" id="phone2" name="phone2" value="<?php echo $phone2_id; ?>">
                            <input type="text" class="form-control phone-search" id="phone2_search"
                                placeholder="Search for a phone..."
                                value="<?php echo ($phone2) ? htmlspecialchars($phone2['name'] . ' - ' . $phone2['brand'] . ($phone2['year'] ? ' (' . $phone2['year'] . ')' : '')) : ''; ?>">
                            <div class="search-results" id="phone2_results"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="phone3" class="form-label">Phone 3</label>
                            <input type="hidden" id="phone3" name="phone3" value="<?php echo $phone3_id; ?>">
                            <input type="text" class="form-control phone-search" id="phone3_search"
                                placeholder="Search for a phone..."
                                value="<?php echo ($phone3) ? htmlspecialchars($phone3['name'] . ' - ' . $phone3['brand'] . ($phone3['year'] ? ' (' . $phone3['year'] . ')' : '')) : ''; ?>">
                            <div class="search-results" id="phone3_results"></div>
                        </div>

                        <!-- Phone data for JavaScript -->
                        <script>
                            const phoneData = <?php
                                                $phoneDataForJS = [];
                                                foreach ($phones as $phone) {
                                                    $phoneDataForJS[] = [
                                                        'id' => $phone['id'] ?? 0,
                                                        'name' => $phone['name'] ?? '',
                                                        'brand' => $phone['brand'] ?? '',
                                                        'year' => $phone['year'] ?? '',
                                                        'image' => $phone['image'] ?? ''
                                                    ];
                                                }
                                                echo json_encode($phoneDataForJS);
                                                ?>;
                        </script>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-exchange-alt"></i> Compare
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!$has_selection): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Please select at least one phone to compare.
                </div>
            <?php else: ?>
                <!-- Comparison Results -->
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Comparison Results</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered comparison-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 20%">Specification</th>
                                        <?php if ($phone1): ?>
                                            <th style="width: 26.7%">
                                                <?php echo htmlspecialchars($phone1['name'] . ' - ' . $phone1['brand']); ?>
                                            </th>
                                        <?php endif; ?>

                                        <?php if ($phone2): ?>
                                            <th style="width: 26.7%">
                                                <?php echo htmlspecialchars($phone2['name'] . ' - ' . $phone2['brand']); ?>
                                            </th>
                                        <?php endif; ?>

                                        <?php if ($phone3): ?>
                                            <th style="width: 26.7%">
                                                <?php echo htmlspecialchars($phone3['name'] . ' - ' . $phone3['brand']); ?>
                                            </th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Image Row -->
                                    <tr>
                                        <td class="fw-bold">Image</td>
                                        <?php if ($phone1): ?>
                                            <td class="text-center">
                                                <?php if (!empty($phone1['image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($phone1['image']); ?>"
                                                        alt="<?php echo htmlspecialchars($phone1['name']); ?>"
                                                        class="img-fluid phone-comparison-img" style="max-height: 150px;">
                                                <?php elseif (!empty($phone1['images']) && !empty($phone1['images'][0])): ?>
                                                    <img src="<?php echo htmlspecialchars($phone1['images'][0]); ?>"
                                                        alt="<?php echo htmlspecialchars($phone1['name']); ?>"
                                                        class="img-fluid phone-comparison-img" style="max-height: 150px;">
                                                <?php else: ?>
                                                    <span class="text-muted">No image</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if ($phone2): ?>
                                            <td class="text-center">
                                                <?php if (!empty($phone2['image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($phone2['image']); ?>"
                                                        alt="<?php echo htmlspecialchars($phone2['name']); ?>"
                                                        class="img-fluid phone-comparison-img" style="max-height: 150px;">
                                                <?php elseif (!empty($phone2['images']) && !empty($phone2['images'][0])): ?>
                                                    <img src="<?php echo htmlspecialchars($phone2['images'][0]); ?>"
                                                        alt="<?php echo htmlspecialchars($phone2['name']); ?>"
                                                        class="img-fluid phone-comparison-img" style="max-height: 150px;">
                                                <?php else: ?>
                                                    <span class="text-muted">No image</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if ($phone3): ?>
                                            <td class="text-center">
                                                <?php if (!empty($phone3['image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($phone3['image']); ?>"
                                                        alt="<?php echo htmlspecialchars($phone3['name']); ?>"
                                                        class="img-fluid phone-comparison-img" style="max-height: 150px;">
                                                <?php elseif (!empty($phone3['images']) && !empty($phone3['images'][0])): ?>
                                                    <img src="<?php echo htmlspecialchars($phone3['images'][0]); ?>"
                                                        alt="<?php echo htmlspecialchars($phone3['name']); ?>"
                                                        class="img-fluid phone-comparison-img" style="max-height: 150px;">
                                                <?php else: ?>
                                                    <span class="text-muted">No image</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>

                                    <!-- Device Name Row -->
                                    <tr>
                                        <td class="fw-bold">Device Name</td>
                                        <?php if ($phone1): ?>
                                            <td><?php echo htmlspecialchars($phone1['name'] ?? 'Not specified'); ?></td>
                                        <?php endif; ?>

                                        <?php if ($phone2): ?>
                                            <td><?php echo htmlspecialchars($phone2['name'] ?? 'Not specified'); ?></td>
                                        <?php endif; ?>

                                        <?php if ($phone3): ?>
                                            <td><?php echo htmlspecialchars($phone3['name'] ?? 'Not specified'); ?></td>
                                        <?php endif; ?>
                                    </tr>

                                    <!-- Brand Row -->
                                    <tr>
                                        <td class="fw-bold">Brand</td>
                                        <?php if ($phone1): ?>
                                            <td><?php echo htmlspecialchars($phone1['brand'] ?? 'Not specified'); ?></td>
                                        <?php endif; ?>

                                        <?php if ($phone2): ?>
                                            <td><?php echo htmlspecialchars($phone2['brand'] ?? 'Not specified'); ?></td>
                                        <?php endif; ?>

                                        <?php if ($phone3): ?>
                                            <td><?php echo htmlspecialchars($phone3['brand'] ?? 'Not specified'); ?></td>
                                        <?php endif; ?>
                                    </tr>

                                    <!-- Year Row -->
                                    <tr>
                                        <td class="fw-bold">Release Year</td>
                                        <?php if ($phone1): ?>
                                            <td><?php echo htmlspecialchars($phone1['year'] ?? 'Not specified'); ?></td>
                                        <?php endif; ?>

                                        <?php if ($phone2): ?>
                                            <td><?php echo htmlspecialchars($phone2['year'] ?? 'Not specified'); ?></td>
                                        <?php endif; ?>

                                        <?php if ($phone3): ?>
                                            <td><?php echo htmlspecialchars($phone3['year'] ?? 'Not specified'); ?></td>
                                        <?php endif; ?>
                                    </tr>

                                    <!-- Price Row -->
                                    <tr>
                                        <td class="fw-bold">Price</td>
                                        <?php if ($phone1): ?>
                                            <td><?php echo $phone1['price'] ? '$' . htmlspecialchars(number_format($phone1['price'], 2)) : 'Not specified'; ?></td>
                                        <?php endif; ?>

                                        <?php if ($phone2): ?>
                                            <td><?php echo $phone2['price'] ? '$' . htmlspecialchars(number_format($phone2['price'], 2)) : 'Not specified'; ?></td>
                                        <?php endif; ?>

                                        <?php if ($phone3): ?>
                                            <td><?php echo $phone3['price'] ? '$' . htmlspecialchars(number_format($phone3['price'], 2)) : 'Not specified'; ?></td>
                                        <?php endif; ?>
                                    </tr>

                                    <!-- Availability Row -->
                                    <tr>
                                        <td class="fw-bold">Availability</td>
                                        <?php if ($phone1): ?>
                                            <td><?php echo displayAvailability($phone1); ?></td>
                                        <?php endif; ?>

                                        <?php if ($phone2): ?>
                                            <td><?php echo displayAvailability($phone2); ?></td>
                                        <?php endif; ?>

                                        <?php if ($phone3): ?>
                                            <td><?php echo displayAvailability($phone3); ?></td>
                                        <?php endif; ?>
                                    </tr>

                                    <!-- Network Capabilities Row -->
                                    <tr>
                                        <td class="fw-bold">Network</td>
                                        <?php if ($phone1): ?>
                                            <td><?php echo displayNetworkCapabilities($phone1); ?></td>
                                        <?php endif; ?>

                                        <?php if ($phone2): ?>
                                            <td><?php echo displayNetworkCapabilities($phone2); ?></td>
                                        <?php endif; ?>

                                        <?php if ($phone3): ?>
                                            <td><?php echo displayNetworkCapabilities($phone3); ?></td>
                                        <?php endif; ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php include 'includes/footer.php'; ?>