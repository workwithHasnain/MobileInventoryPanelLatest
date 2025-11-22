<?php
require_once 'auth.php';
require_once 'filter_config.php';

// Require login
requireLogin();

// Check if user is admin (you may need to adjust based on your auth system)
// For now, we'll just require login

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'load') {
    // Load and display filter settings form
    loadFilterSettingsForm();
} elseif ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save filter settings
    saveFilterSettings();
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function loadFilterSettingsForm()
{
    try {
        $filterConfig = FilterConfig::get();
        ob_start();
?>
        <form id="filterSettingsForm">
            <div class="row">
                <!-- Numerical Sliders Section -->
                <div class="col-md-6">
                    <h6 class="mb-3 border-bottom pb-2">
                        <i class="fas fa-sliders-h me-2"></i>Price & Range Filters
                    </h6>

                    <!-- Price -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Price Range</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min ($)</label>
                                <input type="number" class="form-control form-control-sm" name="price_min"
                                    value="<?php echo htmlspecialchars($filterConfig['price']['min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max ($)</label>
                                <input type="number" class="form-control form-control-sm" name="price_max"
                                    value="<?php echo htmlspecialchars($filterConfig['price']['max']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Step</label>
                                <input type="number" class="form-control form-control-sm" name="price_step"
                                    value="<?php echo htmlspecialchars($filterConfig['price']['step']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Year -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Year Range</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" class="form-control form-control-sm" name="year_min"
                                    value="<?php echo htmlspecialchars($filterConfig['year']['min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" class="form-control form-control-sm" name="year_max"
                                    value="<?php echo htmlspecialchars($filterConfig['year']['max']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- RAM -->
                    <div class="mb-3">
                        <label class="form-label"><strong>RAM (GB)</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" class="form-control form-control-sm" name="ram_min"
                                    value="<?php echo htmlspecialchars($filterConfig['ram']['min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" class="form-control form-control-sm" name="ram_max"
                                    value="<?php echo htmlspecialchars($filterConfig['ram']['max']); ?>">
                            </div>
                            <div class="col-12">
                                <label class="small">Step</label>
                                <input type="number" class="form-control form-control-sm" name="ram_step"
                                    value="<?php echo htmlspecialchars($filterConfig['ram']['step']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Storage -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Storage (GB)</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" class="form-control form-control-sm" name="storage_min"
                                    value="<?php echo htmlspecialchars($filterConfig['storage']['min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" class="form-control form-control-sm" name="storage_max"
                                    value="<?php echo htmlspecialchars($filterConfig['storage']['max']); ?>">
                            </div>
                            <div class="col-12">
                                <label class="small">Step</label>
                                <input type="number" class="form-control form-control-sm" name="storage_step"
                                    value="<?php echo htmlspecialchars($filterConfig['storage']['step']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Display Size -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Display Size (inches)</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" step="0.1" class="form-control form-control-sm" name="display_size_min"
                                    value="<?php echo htmlspecialchars($filterConfig['display_size']['min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" step="0.1" class="form-control form-control-sm" name="display_size_max"
                                    value="<?php echo htmlspecialchars($filterConfig['display_size']['max']); ?>">
                            </div>
                            <div class="col-12">
                                <label class="small">Step</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" name="display_size_step"
                                    value="<?php echo htmlspecialchars($filterConfig['display_size']['step']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Display Resolution -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Display Resolution (pixels)</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" class="form-control form-control-sm" name="display_res_min"
                                    value="<?php echo htmlspecialchars($filterConfig['display_resolution']['min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" class="form-control form-control-sm" name="display_res_max"
                                    value="<?php echo htmlspecialchars($filterConfig['display_resolution']['max']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Refresh Rate -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Refresh Rate (Hz)</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" class="form-control form-control-sm" name="refresh_rate_min"
                                    value="<?php echo htmlspecialchars($filterConfig['refresh_rate']['min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" class="form-control form-control-sm" name="refresh_rate_max"
                                    value="<?php echo htmlspecialchars($filterConfig['refresh_rate']['max']); ?>">
                            </div>
                            <div class="col-12">
                                <label class="small">Step</label>
                                <input type="number" class="form-control form-control-sm" name="refresh_rate_step"
                                    value="<?php echo htmlspecialchars($filterConfig['refresh_rate']['step']); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Options Lists Section -->
                <div class="col-md-6">
                    <h6 class="mb-3 border-bottom pb-2">
                        <i class="fas fa-list me-2"></i>Options & Specifications
                    </h6>

                    <!-- Colors -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Colors</strong></label>
                        <textarea class="form-control form-control-sm" name="colors" rows="3" placeholder="One per line">
<?php echo implode("\n", $filterConfig['colors']); ?>
                        </textarea>
                        <small class="form-text text-muted">Enter one color per line</small>
                    </div>

                    <!-- Frame Materials -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Frame Materials</strong></label>
                        <textarea class="form-control form-control-sm" name="frame_materials" rows="3" placeholder="One per line">
<?php echo implode("\n", $filterConfig['frame_materials']); ?>
                        </textarea>
                        <small class="form-text text-muted">Enter one material per line</small>
                    </div>

                    <!-- Back Materials -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Back Materials</strong></label>
                        <textarea class="form-control form-control-sm" name="back_materials" rows="3" placeholder="One per line">
<?php echo implode("\n", $filterConfig['back_materials']); ?>
                        </textarea>
                        <small class="form-text text-muted">Enter one material per line</small>
                    </div>

                    <!-- Display Technologies -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Display Technologies</strong></label>
                        <textarea class="form-control form-control-sm" name="display_techs" rows="3" placeholder="One per line">
<?php echo implode("\n", $filterConfig['display_technologies']); ?>
                        </textarea>
                        <small class="form-text text-muted">Enter one technology per line</small>
                    </div>

                    <!-- WiFi Versions -->
                    <div class="mb-3">
                        <label class="form-label"><strong>WiFi Versions</strong></label>
                        <textarea class="form-control form-control-sm" name="wifi_versions" rows="3" placeholder="One per line">
<?php echo implode("\n", $filterConfig['wifi_versions']); ?>
                        </textarea>
                        <small class="form-text text-muted">Enter one version per line</small>
                    </div>

                    <!-- Bluetooth Versions -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Bluetooth Versions</strong></label>
                        <textarea class="form-control form-control-sm" name="bluetooth_versions" rows="3" placeholder="One per line">
<?php echo implode("\n", $filterConfig['bluetooth_versions']); ?>
                        </textarea>
                        <small class="form-text text-muted">Enter one version per line</small>
                    </div>

                    <!-- OS Families -->
                    <div class="mb-3">
                        <label class="form-label"><strong>OS Families</strong></label>
                        <textarea class="form-control form-control-sm" name="os_families" rows="3" placeholder="One per line">
<?php echo implode("\n", $filterConfig['os_families']); ?>
                        </textarea>
                        <small class="form-text text-muted">Enter one OS family per line</small>
                    </div>
                </div>
            </div>

            <!-- More options in a second row -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <h6 class="mb-3 border-bottom pb-2">
                        <i class="fas fa-microchip me-2"></i>Performance Specs
                    </h6>

                    <!-- CPU Clock -->
                    <div class="mb-3">
                        <label class="form-label"><strong>CPU Clock (GHz)</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" step="0.1" class="form-control form-control-sm" name="cpu_clock_min"
                                    value="<?php echo htmlspecialchars($filterConfig['cpu_clock']['min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" step="0.1" class="form-control form-control-sm" name="cpu_clock_max"
                                    value="<?php echo htmlspecialchars($filterConfig['cpu_clock']['max']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- OS Version -->
                    <div class="mb-3">
                        <label class="form-label"><strong>OS Version</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" step="0.5" class="form-control form-control-sm" name="os_version_min"
                                    value="<?php echo htmlspecialchars($filterConfig['os_version']['min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" step="0.5" class="form-control form-control-sm" name="os_version_max"
                                    value="<?php echo htmlspecialchars($filterConfig['os_version']['max']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Main Camera MP -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Main Camera (MP)</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" class="form-control form-control-sm" name="main_camera_min"
                                    value="<?php echo htmlspecialchars($filterConfig['main_camera_mp']['min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" class="form-control form-control-sm" name="main_camera_max"
                                    value="<?php echo htmlspecialchars($filterConfig['main_camera_mp']['max']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- F-Number -->
                    <div class="mb-3">
                        <label class="form-label"><strong>F-Number (Aperture)</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" step="0.1" class="form-control form-control-sm" name="f_number_min"
                                    value="<?php echo htmlspecialchars($filterConfig['f_number']['min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" step="0.1" class="form-control form-control-sm" name="f_number_max"
                                    value="<?php echo htmlspecialchars($filterConfig['f_number']['max']); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h6 class="mb-3 border-bottom pb-2">
                        <i class="fas fa-battery-half me-2"></i>Battery & Power
                    </h6>

                    <!-- Battery Capacity -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Battery Capacity (mAh)</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" class="form-control form-control-sm" name="battery_cap_min"
                                    value="<?php echo htmlspecialchars($filterConfig['battery_capacity']['min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" class="form-control form-control-sm" name="battery_cap_max"
                                    value="<?php echo htmlspecialchars($filterConfig['battery_capacity']['max']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Wired Charging -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Wired Charging (W)</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" class="form-control form-control-sm" name="wired_charge_min"
                                    value="<?php echo htmlspecialchars($filterConfig['wired_charging']['min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" class="form-control form-control-sm" name="wired_charge_max"
                                    value="<?php echo htmlspecialchars($filterConfig['wired_charging']['max']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Wireless Charging -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Wireless Charging (W)</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" class="form-control form-control-sm" name="wireless_charge_min"
                                    value="<?php echo htmlspecialchars($filterConfig['wireless_charging']['min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" class="form-control form-control-sm" name="wireless_charge_max"
                                    value="<?php echo htmlspecialchars($filterConfig['wireless_charging']['max']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Body Dimensions -->
                    <h6 class="mb-2 mt-4 pt-3 border-top">
                        <i class="fas fa-ruler me-2"></i>Body Dimensions
                    </h6>

                    <!-- Height -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Height (mm)</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" class="form-control form-control-sm" name="height_min"
                                    value="<?php echo htmlspecialchars($filterConfig['dimensions']['height_min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" class="form-control form-control-sm" name="height_max"
                                    value="<?php echo htmlspecialchars($filterConfig['dimensions']['height_max']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Width -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Width (mm)</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" class="form-control form-control-sm" name="width_min"
                                    value="<?php echo htmlspecialchars($filterConfig['dimensions']['width_min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" class="form-control form-control-sm" name="width_max"
                                    value="<?php echo htmlspecialchars($filterConfig['dimensions']['width_max']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Thickness -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Thickness (mm)</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" step="0.1" class="form-control form-control-sm" name="thickness_min"
                                    value="<?php echo htmlspecialchars($filterConfig['dimensions']['thickness_min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" step="0.1" class="form-control form-control-sm" name="thickness_max"
                                    value="<?php echo htmlspecialchars($filterConfig['dimensions']['thickness_max']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Weight -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Weight (g)</strong></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small">Min</label>
                                <input type="number" class="form-control form-control-sm" name="weight_min"
                                    value="<?php echo htmlspecialchars($filterConfig['dimensions']['weight_min']); ?>">
                            </div>
                            <div class="col-6">
                                <label class="small">Max</label>
                                <input type="number" class="form-control form-control-sm" name="weight_max"
                                    value="<?php echo htmlspecialchars($filterConfig['dimensions']['weight_max']); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
<?php
        $html = ob_get_clean();
        http_response_code(200);
        echo $html;
    } catch (Exception $e) {
        http_response_code(500);
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

function saveFilterSettings()
{
    try {
        // Get current config
        $filterConfig = FilterConfig::get();

        // Update numerical values
        $filterConfig['price']['min'] = (int)$_POST['price_min'];
        $filterConfig['price']['max'] = (int)$_POST['price_max'];
        $filterConfig['price']['step'] = (int)$_POST['price_step'];

        $filterConfig['year']['min'] = (int)$_POST['year_min'];
        $filterConfig['year']['max'] = (int)$_POST['year_max'];

        $filterConfig['ram']['min'] = (int)$_POST['ram_min'];
        $filterConfig['ram']['max'] = (int)$_POST['ram_max'];
        $filterConfig['ram']['step'] = (int)$_POST['ram_step'];

        $filterConfig['storage']['min'] = (int)$_POST['storage_min'];
        $filterConfig['storage']['max'] = (int)$_POST['storage_max'];
        $filterConfig['storage']['step'] = (int)$_POST['storage_step'];

        $filterConfig['display_size']['min'] = (float)$_POST['display_size_min'];
        $filterConfig['display_size']['max'] = (float)$_POST['display_size_max'];
        $filterConfig['display_size']['step'] = (float)$_POST['display_size_step'];

        $filterConfig['display_resolution']['min'] = (int)$_POST['display_res_min'];
        $filterConfig['display_resolution']['max'] = (int)$_POST['display_res_max'];

        $filterConfig['refresh_rate']['min'] = (int)$_POST['refresh_rate_min'];
        $filterConfig['refresh_rate']['max'] = (int)$_POST['refresh_rate_max'];
        $filterConfig['refresh_rate']['step'] = (int)$_POST['refresh_rate_step'];

        $filterConfig['cpu_clock']['min'] = (float)$_POST['cpu_clock_min'];
        $filterConfig['cpu_clock']['max'] = (float)$_POST['cpu_clock_max'];

        $filterConfig['os_version']['min'] = (float)$_POST['os_version_min'];
        $filterConfig['os_version']['max'] = (float)$_POST['os_version_max'];

        $filterConfig['main_camera_mp']['min'] = (int)$_POST['main_camera_min'];
        $filterConfig['main_camera_mp']['max'] = (int)$_POST['main_camera_max'];

        $filterConfig['f_number']['min'] = (float)$_POST['f_number_min'];
        $filterConfig['f_number']['max'] = (float)$_POST['f_number_max'];

        $filterConfig['battery_capacity']['min'] = (int)$_POST['battery_cap_min'];
        $filterConfig['battery_capacity']['max'] = (int)$_POST['battery_cap_max'];

        $filterConfig['wired_charging']['min'] = (int)$_POST['wired_charge_min'];
        $filterConfig['wired_charging']['max'] = (int)$_POST['wired_charge_max'];

        $filterConfig['wireless_charging']['min'] = (int)$_POST['wireless_charge_min'];
        $filterConfig['wireless_charging']['max'] = (int)$_POST['wireless_charge_max'];

        // Update dimensions
        $filterConfig['dimensions']['height_min'] = (int)$_POST['height_min'];
        $filterConfig['dimensions']['height_max'] = (int)$_POST['height_max'];
        $filterConfig['dimensions']['width_min'] = (int)$_POST['width_min'];
        $filterConfig['dimensions']['width_max'] = (int)$_POST['width_max'];
        $filterConfig['dimensions']['thickness_min'] = (float)$_POST['thickness_min'];
        $filterConfig['dimensions']['thickness_max'] = (float)$_POST['thickness_max'];
        $filterConfig['dimensions']['weight_min'] = (int)$_POST['weight_min'];
        $filterConfig['dimensions']['weight_max'] = (int)$_POST['weight_max'];

        // Update text options (textarea input, one per line)
        $filterConfig['colors'] = array_filter(array_map('trim', explode("\n", $_POST['colors'])));
        $filterConfig['frame_materials'] = array_filter(array_map('trim', explode("\n", $_POST['frame_materials'])));
        $filterConfig['back_materials'] = array_filter(array_map('trim', explode("\n", $_POST['back_materials'])));
        $filterConfig['display_technologies'] = array_filter(array_map('trim', explode("\n", $_POST['display_techs'])));
        $filterConfig['wifi_versions'] = array_filter(array_map('trim', explode("\n", $_POST['wifi_versions'])));
        $filterConfig['bluetooth_versions'] = array_filter(array_map('trim', explode("\n", $_POST['bluetooth_versions'])));
        $filterConfig['os_families'] = array_filter(array_map('trim', explode("\n", $_POST['os_families'])));

        // Re-index arrays to make them proper JSON arrays
        $filterConfig['colors'] = array_values($filterConfig['colors']);
        $filterConfig['frame_materials'] = array_values($filterConfig['frame_materials']);
        $filterConfig['back_materials'] = array_values($filterConfig['back_materials']);
        $filterConfig['display_technologies'] = array_values($filterConfig['display_technologies']);
        $filterConfig['wifi_versions'] = array_values($filterConfig['wifi_versions']);
        $filterConfig['bluetooth_versions'] = array_values($filterConfig['bluetooth_versions']);
        $filterConfig['os_families'] = array_values($filterConfig['os_families']);

        // Write to file
        $configPath = __DIR__ . '/filter_config.json';
        $jsonContent = json_encode($filterConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($jsonContent === false) {
            throw new Exception('Failed to encode JSON: ' . json_last_error_msg());
        }

        if (file_put_contents($configPath, $jsonContent) === false) {
            throw new Exception('Failed to write to filter_config.json');
        }

        // Clear the cached config
        FilterConfig::reload();

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Filter settings updated successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>