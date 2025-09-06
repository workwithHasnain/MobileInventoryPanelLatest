<?php
require_once 'auth.php';
require_once 'brand_data.php';
requireAdmin();

// Handle brand operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_brand') {
        $name = trim($_POST['brand_name']);
        $description = trim($_POST['brand_description']);
        
        if (!empty($name)) {
            $brand_data = [
                'id' => time() . '_' . rand(1000, 9999),
                'name' => $name,
                'description' => $description,
                'created_at' => time()
            ];
            if (addBrand($brand_data)) {
                $success_message = "Brand added successfully!";
            } else {
                $error_message = "Brand already exists!";
            }
        } else {
            $error_message = "Brand name is required!";
        }
    } elseif ($_POST['action'] === 'delete_brand') {
        $id = $_POST['brand_id'];
        if (deleteBrand($id)) {
            $success_message = "Brand deleted successfully!";
        } else {
            $error_message = "Error deleting brand!";
        }
    } elseif ($_POST['action'] === 'add_chipset') {
        $name = trim($_POST['chipset_name']);
        $description = trim($_POST['chipset_description']);
        
        if (!empty($name)) {
            $chipset_data = [
                'id' => time() . '_' . rand(1000, 9999),
                'name' => $name,
                'description' => $description,
                'created_at' => time()
            ];
            if (addChipset($chipset_data)) {
                $success_message = "Chipset added successfully!";
            } else {
                $error_message = "Chipset already exists!";
            }
        } else {
            $error_message = "Chipset name is required!";
        }
    } elseif ($_POST['action'] === 'delete_chipset') {
        $id = $_POST['chipset_id'];
        if (deleteChipset($id)) {
            $success_message = "Chipset deleted successfully!";
        } else {
            $error_message = "Error deleting chipset!";
        }
    }
}

$brands = getAllBrands();
$chipsets = getAllChipsets();
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-cogs"></i> Data Management</h2>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs" id="managementTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="brands-tab" data-bs-toggle="tab" data-bs-target="#brands" type="button" role="tab" aria-controls="brands" aria-selected="true">
                        <i class="fas fa-tag"></i> Brands
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="chipsets-tab" data-bs-toggle="tab" data-bs-target="#chipsets" type="button" role="tab" aria-controls="chipsets" aria-selected="false">
                        <i class="fas fa-microchip"></i> Chipsets
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="managementTabsContent">
                <!-- Brands Tab -->
                <div class="tab-pane fade show active" id="brands" role="tabpanel" aria-labelledby="brands-tab">
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Manage Brands</h5>
                        </div>
                        <div class="card-body">
                            <!-- Add Brand Form -->
                            <form method="POST" class="mb-4">
                                <input type="hidden" name="action" value="add_brand">
                                <div class="row">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" name="brand_name" placeholder="Brand Name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="brand_description" placeholder="Description (optional)">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Brands List -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Brand Name</th>
                                            <th>Description</th>
                                            <th>Created</th>
                                            <th width="100">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($brands)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No brands found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($brands as $brand): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($brand['name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($brand['description'] ?? 'No description'); ?></td>
                                                    <td><?php 
                                                        $timestamp = $brand['created_at'] ?? time();
                                                        if (!is_numeric($timestamp)) {
                                                            $timestamp = strtotime($timestamp) ?: time();
                                                        }
                                                        echo date('M j, Y', $timestamp); 
                                                    ?></td>
                                                    <td>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Are you sure you want to delete this brand?');">
                                                            <input type="hidden" name="action" value="delete_brand">
                                                            <input type="hidden" name="brand_id" value="<?php echo $brand['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete Brand">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chipsets Tab -->
                <div class="tab-pane fade" id="chipsets" role="tabpanel" aria-labelledby="chipsets-tab">
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Manage Chipsets</h5>
                        </div>
                        <div class="card-body">
                            <!-- Add Chipset Form -->
                            <form method="POST" class="mb-4">
                                <input type="hidden" name="action" value="add_chipset">
                                <div class="row">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" name="chipset_name" placeholder="Chipset Name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="chipset_description" placeholder="Description (optional)">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Chipsets List -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Chipset Name</th>
                                            <th>Description</th>
                                            <th>Created</th>
                                            <th width="100">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($chipsets)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No chipsets found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($chipsets as $chipset): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($chipset['name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($chipset['description'] ?? 'No description'); ?></td>
                                                    <td><?php 
                                                        $timestamp = $chipset['created_at'] ?? time();
                                                        if (!is_numeric($timestamp)) {
                                                            $timestamp = strtotime($timestamp) ?: time();
                                                        }
                                                        echo date('M j, Y', $timestamp); 
                                                    ?></td>
                                                    <td>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Are you sure you want to delete this chipset?');">
                                                            <input type="hidden" name="action" value="delete_chipset">
                                                            <input type="hidden" name="chipset_id" value="<?php echo $chipset['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete Chipset">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>

<?php include 'includes/footer.php'; ?>